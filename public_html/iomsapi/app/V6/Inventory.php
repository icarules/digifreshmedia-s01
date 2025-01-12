<?php


namespace App\V6;


use App\Apikey;
use App\Calculations\Helpers;
use App\Calculations\Lease;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\Debugbar\Facade as Debugbar;

class Inventory extends Model
{
    private const IMAGE_URL = 'https://www.digifresh-media.nl/digiauto/vehicleImages/';

    protected $table;
    protected $autobedrijvenTable;
    protected $onlyActiveClients = false;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'images',
//        'panoramas',
        'json_props',
//        'apk',
//        'asconfiguratie',
        'calculated_leasetermijn',
//        'wegenbelasting_kwartaal',
//        'accessoires',
//        'accessoiregroepen',
//        'zoekaccessoires',
//        'optie_pakketten',
        'videos',
//        'klantgegevens',
//        'maatwerk_velden'
    ];

    protected $hidden = ['json', 'xml'];

    public function __construct()
    {
        $token = $_SERVER["HTTP_X_API_KEY"] ?: $_GET('apikey');
        $apiKey = Apikey::where('api_key', $token)->first();
        $table = json_decode($apiKey->tables)[0]->tableName;

        $this->table = $table;

        switch ($apiKey->api_key) {
            case 'CRuBIwJJbl0mp84u0yyRzZIQhj3LtzshCw8r6wfh': // carhotspot
                $this->autobedrijvenTable = 'autobedrijven_carhotspot';
                $this->onlyActiveClients = false;
                break;
            default:
                $this->autobedrijvenTable = 'autobedrijven';
                $this->onlyActiveClients = true;
        }
    }

    public function getInventory($hexonNrs = [], $order)
    {
        $query = self::select('*');

        foreach ($order as $orderBy => $orderDirection) {
            $query->orderBy($orderBy, $orderDirection);
        }

        if (count($hexonNrs) > 0) {
            $query->whereIn($this->table . ".hexonnr", $hexonNrs);


            if ($this->autobedrijvenTable) {
                $query->leftjoin($this->autobedrijvenTable, $this->table . '.klantnr', '=',
                    $this->autobedrijvenTable . '.klantnr');
            }

            return $query->get();
        } else {
            return collect([]);
        }
    }

    public function getInventoryCount(
        $clientNrs = [],
        $search = false,
        $filters = array(),
        $filtersBetween = array(),
        $filtersList = array(),
        $orFilters = array()
    ) {
        $query = DB::table($this->table)
            ->select('*');
//            ->select(DB::raw('voertuigsoort, merk, model, brandstof, basiskleur, carrosserie, transmissie, bouwjaar, nieuw_voertuig, verkoopprijs_particulier, tellerstand, bijtelling_pct, klantnr, btw_marge'));

        foreach ($filters as $filter => $value) {
            if ($filter == 'voordeelpercentage') {
                $query->whereRaw("ROUND((1 - (verkoopprijs_particulier/(nieuwprijs + 950))) * 100) >= " . $value);
            } else if ($filter == 'kenteken') {
                $value = str_replace('-', '', $value);
                $query->whereRaw("REPLACE(".$filter.", '-', '') = '".$value."'");
            } else {
                $query->where($filter, '=', $value);
            }
        }

        if (count($orFilters) > 0) {
            $query->where(function ($query) use ($orFilters) {
                foreach ($orFilters as $key => $orFilter) {
                    $query->orWhere($key, '=' , $orFilter);
                }
            });
        }

        foreach ($filtersBetween as $filterName => $range) {
            if ($range[0] == 0) {
                $query->where(function ($query) use($filterName, $range) {
                    $query->whereNull($filterName)
                        ->orWhere($filterName, '<=', $range[1]);
                });
            } else {
                $query->whereBetween($filterName, $range);
            }
        }

        $serverSideClientFilter = true;
        foreach ($filtersList as $filterName => $list) {
            if ($filterName = "klantnr") {
                $serverSideClientFilter = false;
            }
            $query->whereIn($filterName, $list);
        }

        if ($serverSideClientFilter && count($clientNrs) > 0) {
//            $clientNrs[] = null;
//            $clientNrs[] = 0;
            $query->whereIn('klantnr', $clientNrs);
        }

        if ($search) {
            if (strstr($search, '-') !== false) {
                $search = '"' . $search . '"';
            }
            $query->whereRaw("MATCH (`merk`,`model`,`type`,`opties`) AGAINST (? IN BOOLEAN MODE)" , $search);
        }

        return $query->get();
    }


    public function getInventoryList(
        $clientNrs = [],
        $limit = 20,
        $offset = 0,
        $filters = array(),
        $filtersBetween = array(),
        $filtersList = array(),
        $orFilters = array(),
        $orderBy = 'merk',
        $order = 'asc'
    ) {
        $query = DB::table($this->table)->select('hexonnr', 'merk', 'model');

        foreach ($filters as $filter => $value) {
            $query->where($filter, '=', $value);
        }

        foreach ($filtersBetween as $filterName => $range) {
            if ($range[0] == 0) {
                $query->where(function ($query) use($filterName, $range) {
                    $query->whereNull($filterName)
                        ->orWhere($filterName, '<=', $range[1]);
                });
            } else {
                $query->whereBetween($filterName, $range);
            }
        }

        if (count($orFilters) > 0) {
            $query->where(function ($query) use ($orFilters) {
                foreach ($orFilters as $key => $orFilter) {
                    $query->orWhere($key, '=' , $orFilter);
                }
            });
        }

        $serverSideClientFilter = true;
        foreach ($filtersList as $filterName => $list) {
            if ($filterName = "klantnr") {
                $serverSideClientFilter = false;
            }
            $query->whereIn($this->table.".".$filterName, $list);
        }

        if ($serverSideClientFilter && count($clientNrs) > 0) {
            $query->whereIn($this->table.'.klantnr', $clientNrs);
        }

        $query->orderBy($orderBy ?? 'merk', $order ?? 'asc' );
        $query->limit($limit)->offset($offset);

        return $query->get();
    }

    public function getImagesAttribute()
    {
        $imgField = $this->attributes['images'];
        $imgArray = $imgField ? explode(",", $imgField) : [];

        $images = [];
        foreach ($imgArray as $img) {
            $images[] = self::IMAGE_URL . $img;
        }

        return $images;
    }

    public function getPanoramasAttribute()
    {
        $pnrField = $this->attributes['panoramas'];
        $pnrArray = $pnrField ? explode(",", $pnrField) : [];

        $panoramas = [];
        foreach ($pnrArray as $pnr) {
            $panoramas[] = self::IMAGE_URL . $pnr;
        }

        return $panoramas;
    }

    public function getApkAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $apk = [];
        if ($json !== null) {
            if (array_key_exists('apk', $json)) {
                if (array_key_exists('@attributes', $json['apk']) && is_array($json['apk']['@attributes'])) {
                    foreach ($json['apk']['@attributes'] as $key => $value) {
                        $apk[$key] = $value;
                    }
                }
            }
        }
        return $apk;
    }

    public function getKlantgegevensAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $klantgegevens = [];
        if ($json !== null) {
            if (array_key_exists('klantgegevens', $json) && is_array($json['klantgegevens'])) {
                $klantgegevens = $json['klantgegevens'];
            }
        }

        return $klantgegevens;
    }

    public function getAsconfiguratieAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $asconfiguratie = [];
        if ($json !== null) {
            if (array_key_exists('asconfiguratie', $json) && is_array($json['asconfiguratie'])) {
                if (array_key_exists('as', $json['asconfiguratie']) && is_array($json['asconfiguratie']['as'])) {
                    $i = 1;
                    foreach ($json['asconfiguratie']['as'] as $as) {
                        if (is_array($as) && array_key_exists('nr', $as) && array_key_exists('value', $as)) {
                            $asconfiguratie[$as['nr']] = $as['value'];
                        } elseif (is_array($as)) {
                            foreach ($as as $key => $value) {
                                if (!is_array($value)) {
                                    $asconfiguratie[$i][$key] = $value;
                                }
                            }
                            $i++;
                        }
                    }
                }
            }
        }

        return $asconfiguratie;
    }

    public function getAccessoiresAttribute()
    {
        $json = json_decode($this->attributes['json'], true);
        $accessoires = [];
        if ($json !== null && is_array($json)) {
            if (array_key_exists('accessoires', $json) && is_array($json['accessoires'])) {
                if (array_key_exists('accessoire', $json['accessoires']) && is_array($json['accessoires']['accessoire'])) {
                    $i = 1;
                    foreach ($json['accessoires']['accessoire'] as $accessoire) {
                        if (is_array($accessoire)) {
                            foreach ($accessoire as $key => $value) {
                                if (!is_array($value)) {
                                    $accessoires[$i][$key] = $value;
                                }
                            }
                            $i++;
                        } else {
                            $accessoires[$i]["naam"] = $accessoire;
                            $accessoires[$i]['prioriteit'] = "2";
                            $accessoires[$i]['volgorde'] = $i;
                            $i++;
                        }
                    }
                }
            }
        }
        return $accessoires;
    }

    public function getAccessoiregroepenAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $accessoiregroepen = [];
        if ($json !== null) {
            if (array_key_exists('accessoiregroepen', $json) && $json['accessoiregroepen'] !== null) {
                if (array_key_exists('accessoiregroep', $json['accessoiregroepen'])) {
                    if (is_array($json['accessoiregroepen']['accessoiregroep'])) {
                        if (array_key_exists('@attributes', $json['accessoiregroepen']['accessoiregroep'])) {
                            $name = $json['accessoiregroepen']['accessoiregroep']['@attributes']['naam'];
                            if (is_array($json['accessoiregroepen']['accessoiregroep']['accessoire'])) {
                                foreach ($json['accessoiregroepen']['accessoiregroep']['accessoire'] as $value) {
                                    $accessoiregroepen[$name][] = $value;
                                }
                            } else {
                                $accessoiregroepen[$name][] = $json['accessoiregroepen']['accessoiregroep']['accessoire'];
                            }
                        } else {
                            foreach ($json['accessoiregroepen']['accessoiregroep'] as $accessoiregroep) {
                                if (array_key_exists('@attributes', $accessoiregroep) && array_key_exists('naam', $accessoiregroep['@attributes'])) {
                                    $name = $accessoiregroep['@attributes']['naam'];
                                    if (is_array($accessoiregroep['accessoire'])) {
                                        foreach ($accessoiregroep['accessoire'] as $value) {
                                            $accessoiregroepen[$name][] = $value;
                                        }
                                    } else {
                                        $accessoiregroepen[$name][] = $accessoiregroep['accessoire'];
                                    }
                                } else {
                                    if (array_key_exists('naam', $accessoiregroep) && array_key_exists('value', $accessoiregroep)) {
                                        $name = $accessoiregroep['naam'];
                                        if (is_array($accessoiregroep['value'])) {
                                            foreach ($accessoiregroep['value'] as $accessoires) {
                                                if (is_array($accessoires)) {
                                                    foreach ($accessoires as $accessoire) {
                                                        $accessoiregroepen[$name][] = $accessoire['value'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                foreach (self::getAccessoiresAttribute() as $accessoire) {
                    $accessoiregroepen["Accessoires"][] = $accessoire["naam"];
                }
            }
        }

        return array_map('array_values', array_map('array_unique', $accessoiregroepen));
    }

    public function getZoekaccessoiresAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $zoekaccessoires = [];
        if ($json !== null) {
            if (array_key_exists('zoekaccessoires', $json) && $json['zoekaccessoires'] !== null) {
                if (array_key_exists('accessoire',
                        $json['zoekaccessoires']) && is_array($json['zoekaccessoires']['accessoire'])) {
                    foreach ($json['zoekaccessoires']['accessoire'] as $accessoire) {
                        $zoekaccessoires[] = $accessoire;
                    }
                }
            }
        }

        return $zoekaccessoires;
    }

    public function getVideosAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $videos = [];
        if ($json !== null) {
            if (array_key_exists('videos', $json) && is_array($json['videos'])) {
                $videos = $json['videos'];
            }
        }

        return $videos;
    }

    public function getWegenbelastingKwartaalAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $wegenbelasting_kwartaal = [];
        if ($json !== null) {
            if (array_key_exists('wegenbelasting_kwartaal', $json)) {
                if (array_key_exists('@attributes',
                        $json['wegenbelasting_kwartaal']) && is_array($json['wegenbelasting_kwartaal']['@attributes'])) {
                    foreach ($json['wegenbelasting_kwartaal']['@attributes'] as $key => $value) {
                        $wegenbelasting_kwartaal[$key] = $value;
                    }
                } else {
                    $wegenbelasting_kwartaal = $json['wegenbelasting_kwartaal'];
                }
            }
        }

        return $wegenbelasting_kwartaal;
    }

    public function getJsonPropsAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        return is_array($json) ? array_filter($json, 'is_scalar') : [];
    }

    public function getCalculatedLeasetermijnAttribute()
    {
        $token = $_SERVER["HTTP_X_API_KEY"] ?: $_GET('apikey');
        $apiKey = Apikey::where('api_key', $token)->first();

        $downPaymentPct = $apiKey->lease_down_payment;
        $finalPaymentPct = $apiKey->lease_final_payment;
        $interestRate = $apiKey->lease_interest_rate;

        $price = $this->verkoopprijs_particulier ?: 15000;

        if ($apiKey->lease_max_age) {
            $looptijd = Helpers::calculateMaxLoopTijd($this->bouwjaar, $apiKey->lease_max_term, $apiKey->lease_min_term, $apiKey->lease_default_term, $apiKey->lease_max_age);
        } else {
            $looptijd = 60;
        }

        $getParams = [
            'aanschafwaarde' => $price,
            'btw' => $this->btw_marge == 'B' ? 1 : 0,
            'looptijd' => $looptijd,
            'voertuigsoort' => $this->voertuigsoort,
        ];

        $request = new Request($getParams);

        $lease = (new Lease($downPaymentPct, $interestRate, $finalPaymentPct))->calculate($request);

        return $lease['leasetermijn'];
    }

    public function getMaatwerkVeldenAttribute()
    {
        $json = json_decode($this->attributes['json'], true);
        $maatwerk = [];
        if ($json !== null && is_array($json)) {
            if (array_key_exists('Maatwerk', $json) && is_array($json['Maatwerk'])) {
                foreach ($json['Maatwerk'] as $maatwerkveld) {
                    $maatwerk[] = (array) $maatwerkveld;
                }
            }
        }
        return $maatwerk;
    }

    /**
     * Get the slot termijn
     *
     * @param $price
     * @param $months
     *
     * @return float|int
     */
    public static function getSlotTermijn($price, $months)
    {
        $mult = 0.7 - ($months/120);
        return ceil($mult * $price);
    }

    public function getCompanyNames($clientNrs)
    {
        if ($this->autobedrijvenTable) {
            $query = DB::table($this->autobedrijvenTable)
                ->select(DB::raw("klantnr, merkdealer, bedrijfsnaam, adres, postcode, plaats, telefoon, email_algemeen, email_leads, website, whatsapp, instagram, facebook, linkedin, youtube, twitter, tiktok, kvk, rating, review_count, provincie, googlemaps, tijden_maandag, tijden_dinsdag, tijden_woensdag, tijden_donderdag, tijden_vrijdag, tijden_zaterdag, tijden_zondag"))
                ->when($this->onlyActiveClients, function ($query) {
                    return $query->where('actief', '=', 'J');
                })
                ->when(count($clientNrs) > 0, function ($query) use ($clientNrs) {
                    return $query->whereIn('klantnr', $clientNrs);
                })
                ->orderBy('Bedrijfsnaam', 'asc');

            return $query->get();
        }

        return collect([]);
    }

    public function getCompanyNamesNew($clientNrs)
    {
        $fields = "abt.klantnr, abt.merkdealer, abt.bedrijfsnaam, abt.plaats, abt.telefoon, abt.email_algemeen, abt.email_leads, abt.website, abt.whatsapp, abt.instagram, abt.facebook, abt.linkedin, abt.youtube, abt.twitter, abt.tiktok, abt.kvk, abt.rating, abt.review_count, abt.provincie, abt.googlemaps, abt.tijden_maandag, abt.tijden_dinsdag, abt.tijden_woensdag, abt.tijden_donderdag, abt.tijden_vrijdag, abt.tijden_zaterdag, abt.tijden_zondag";
        $query = DB::table($this->autobedrijvenTable . ' as abt')
            ->select(DB::raw($fields . ", COUNT(vt.hexonnr) aantal_voertuigen"))
            ->where('abt.actief', '=', 'J')
            ->when(count($clientNrs) > 0, function ($query) use ($clientNrs) {
                return $query->whereIn('abt.klantnr', $clientNrs);
            })
            ->leftJoin($this->table . ' as vt', 'abt.klantnr', '=', 'vt.klantnr')
            ->groupBy(DB::raw($fields))
            ->orderBy('abt.bedrijfsnaam', 'asc');

        return $query->get();
    }

    public function getOptiePakkettenAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $optiepakketten = [];
        if ($json !== null) {
            if (array_key_exists('optiepakketten', $json) && $json['optiepakketten'] !== null) {
                if (array_key_exists('optiepakket', $json['optiepakketten']) &&
                    is_array($json['optiepakketten']['optiepakket'])) {
                    foreach ($json['optiepakketten']['optiepakket'] as $optiepakket) {
                        if (is_array($optiepakket['opties']) && array_key_exists('optie', $optiepakket['opties'])) {
                            $optiepakket['opties'] = $optiepakket['opties']['optie'];
                        }
                        $optiepakketten[] = $optiepakket;
                    }
                }
            }
        }

        return $optiepakketten;
    }
}
