<?php


namespace App\V1;


use App\Apikey;
use App\Calculations\Lease;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    private const IMAGE_URL = 'https://www.digifreshmedia.nl/digiauto/vehicleImages/';

    protected $table;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['images', 'json_props', 'apk', 'asconfiguratie', 'calculated_leasetermijn', 'wegenbelasting_kwartaal', 'accessoires', 'accessoiregroepen', 'zoekaccessoires', 'videos'];

    protected $hidden = ['json', 'xml'];

    public function __construct()
    {
        $this->table = env('INVENTORY_TABLE_V1', 'voertuigen');
    }

    public function getInventory(
        $clientNrs = [],
        $limit = 20,
        $offset = 0,
        $search = false,
        $filters = array(),
        $filtersBetween = array(),
        $orderBy = 'merk',
        $order = 'asc',
        $includeAllImages = false
    ) {
        $query = self::select('*');

        $query->whereIn('klantnr', $clientNrs);

        foreach ($filters as $filter => $value) {
            $query->where($filter, '=' , $value);
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

        $query->orderBy($orderBy ?? 'merk', $order ?? 'asc' );
        $query->limit($limit)->offset($offset);

        return $query->get();
    }

    public function getInventoryCount(
        $clientNrs = [],
        $search = false,
        $filters = array(),
        $filtersBetween = array()
    ) {
        $query = DB::table($this->table)
            ->select(DB::raw('voertuigsoort, merk, model, brandstof, kleur, carrosserie, transmissie, bouwjaar, nieuw_voertuig, verkoopprijs_particulier, leasetermijn_digifresh, tellerstand, max_trekgewicht_ongeremd'))
            ->whereIn('klantnr', $clientNrs);

        foreach ($filters as $filter => $value) {
            $query->where($filter, '=' , $value);
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

        return $query->get();
    }

    public function getImagesAttribute()
    {
        $json = json_decode($this->attributes['json']);

        $images = [];
        if ($json !== null) {
            if (property_exists($json, 'afbeeldingen')) {
                $i=0;
                if (is_array($json->afbeeldingen->afbeelding)) {
                    foreach ($json->afbeeldingen->afbeelding as $afbeelding) {
//                        $parts = explode('-', basename($afbeelding->url));
//                        $baseName = $parts[0];
//                        $ext = explode('.', $parts[1])[1];
//
//                        $images[] = self::IMAGE_URL . $baseName . '-' . $i . '.' . $ext;
//                        $i++;

                        $oldName = basename($afbeelding->url);
                        $newName = preg_replace_callback("/(\d+-)(\d+)/", function ($matches) {
                            return $matches[1] . ($matches[2] - 1);
                        }, $oldName);
                        $images[] = self::IMAGE_URL . $newName;
                    }
                } else {
//                    $parts = explode('-', basename($json->afbeeldingen->afbeelding->url));
//                    $baseName = $parts[0];
//                    $ext = explode('.', $parts[1])[1];
//                    $images[] = self::IMAGE_URL . $baseName . '-' . $i . '.' . $ext;
                    $oldName = basename($json->afbeeldingen->afbeelding->url);
                    $newName = preg_replace_callback("/(\d+-)(\d+)/", function ($matches) {
                        return $matches[1] . ($matches[2] - 1);
                    }, $oldName);
                    $images[] = self::IMAGE_URL . $newName;
                }
            }
        }
        return $images;
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

    public function getAsconfiguratieAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $asconfiguratie = [];
        if ($json !== null) {
            if (array_key_exists('asconfiguratie', $json)) {
                if (array_key_exists('as', $json['asconfiguratie']) && is_array($json['asconfiguratie']['as'])) {
                    $i = 1;
                    foreach ($json['asconfiguratie']['as'] as $as) {
                        if (is_array($as)) {
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
        if ($json !== null) {
            if (array_key_exists('accessoires', $json)) {
                if (array_key_exists('accessoire',
                        $json['accessoires']) && is_array($json['accessoires']['accessoire'])) {
                    $i = 1;
                    foreach ($json['accessoires']['accessoire'] as $accessoire) {
                        if (is_array($accessoire)) {
                            foreach ($accessoire as $key => $value) {
                                if (!is_array($value)) {
                                    $accessoires[$i][$key] = $value;
                                }
                            }
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
            if (array_key_exists('accessoiregroepen', $json)) {
                if (array_key_exists('accessoiregroep',
                        $json['accessoiregroepen']) && is_array($json['accessoiregroepen']['accessoiregroep'])) {

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
                            $name = $accessoiregroep['@attributes']['naam'];
                            if (is_array($accessoiregroep['accessoire'])) {
                                foreach ($accessoiregroep['accessoire'] as $value) {
                                    $accessoiregroepen[$name][] = $value;
                                }
                            } else {
                                $accessoiregroepen[$name][] = $accessoiregroep['accessoire'];
                            }
                        }
                    }
                }
            }
        }

        return $accessoiregroepen;
    }

    public function getZoekaccessoiresAttribute()
    {
        $json = json_decode($this->attributes['json'], true);

        $zoekaccessoires = [];
        if ($json !== null) {
            if (array_key_exists('zoekaccessoires', $json)) {
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
            if (array_key_exists('videos', $json)) {
                if (array_key_exists('video', $json['videos']) && is_array($json['videos']['video'])) {
                    $i = 1;
                    foreach ($json['videos']['video'] as $as) {
                        if (is_array($as)) {
                            foreach ($as as $key => $value) {
                                if (!is_array($value)) {
                                    $videos[$i][$key] = $value;
                                }
                            }
                            $i++;
                        }
                    }
                }
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

        $price = ($this->verkoopprijs_particulier) ?: 15000;
        $looptijd = $this->getMaxLoopTijd($this->bouwjaar);
        $getParams = [
            'aanschafwaarde' => $price,
            'aanbetaling' => round(($price * 0.2), -2),
            'looptijd' => $looptijd,
            'slottermijn' => $this->getSlotTermijn($price, $looptijd),
            'datum_deel_1' => $this->datum_deel_1
        ];

        $downPayment = $apiKey->lease_down_payment;
        $interestRate = $apiKey->lease_interest_rate;

        $request = new Request($getParams);

        $lease = (new Lease($downPayment, $interestRate))->calculate($request);

        return $this->attributes['calculated_leasetermijn'] = $lease['leasetermijn'];
//        return $this->attributes['calculated_leasetermijn'] === $lease['leasetermijn'];
    }

    /**
     * Get the max looptijd
     *
     * @param $occassionDetails
     *
     * @return float|int
     */
    public static function getMaxLoopTijd($year)
    {
        $age = date('Y') - $year;
        $looptijd = min(max(10 - $age, 1), 5);
        return $looptijd * 12; // in months
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
}
