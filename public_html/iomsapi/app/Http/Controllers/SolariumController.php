<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SolariumController extends Controller
{
    protected $destination;

    protected $voertuigen_table;

    protected $autobedrijven_table;

    protected $client;

    public function __construct(\Solarium\Client $client)
    {
        $this->middleware('auth');

        $this->client = $client;
    }

    /**
     * Import for solr core
     * Import table voertuigen_global into $destination core
     */
    public function import($destination)
    {
        set_time_limit(600);

        $countUpdated = 0;

        $this->destination = $destination;
        $this->initialize();

        // set latlong values in autobedrijven table
//        $this->setLatLong();

        // get inventory from voertuigen table
        $columns = [
            'hexonnr',
            'voertuigen.klantnr',
            'kleur',
            'bijtelling_pct',
            'bouwjaar',
            'brandstof',
            'btw_marge',
            'carrosserie',
            'demovoertuig',
            'energielabel',
            'geplaatst',
            'kenteken',
            'massa',
            'max_trekgewicht',
            'merk',
            'model',
            'nieuw_voertuig',
            'type',
            'opties',
            'tellerstand',
            'transmissie',
            'verkoopprijs_particulier',
            'vermogen_motor_pk',
            'voertuigsoort',
            'bedrijven.merkdealer',
            'bedrijven.lat',
            'bedrijven.lon',
            'bedrijven.adres',
            'bedrijven.postcode',
            'bedrijven.plaats',
            'bedrijven.provincie'
        ];

        if ($this->destination == 'smileycar') {
            $columns[] = 'images_ready';
            $columns[] = 'gaspedaal';
        }

        if ($this->destination == 'autovoorraad24' || $this->destination == 'digifresh') {
            $columns[] = 'verkoopprijs_handel';
        }

        // get an update query instance to delete all documents
        $delete = $this->client->createUpdate();
        $delete->addDeleteQuery('*:*');
        $delete->addCommit();
        $deleteResult = $this->client->update($delete);

        DB::table($this->voertuigen_table . ' as voertuigen')
            ->leftJoin($this->autobedrijven_table . ' as bedrijven', 'voertuigen.klantnr', '=', 'bedrijven.klantnr')
            ->select($columns)
            ->orderBy('hexonnr', 'asc')
            ->chunk(250, function ($vehicles) use (&$countUpdated) {
                // init the docs array
                $docs = [];

                // get an update query instance
                $update = $this->client->createUpdate();

                foreach ($vehicles as $vehicle) {
                    $doc = $update->createDocument();

                    $doc->id = $vehicle->hexonnr;
                    $doc->hexonnr = $vehicle->hexonnr;
                    $doc->aantal_deuren = $vehicle->aantal_deuren;
                    $doc->aantal_zitplaatsen = $vehicle->aantal_zitplaatsen;
                    $doc->basiskleur = $vehicle->basiskleur;
                    $doc->bijtelling_pct = $vehicle->bijtelling_pct;
                    $doc->bouwjaar = $vehicle->bouwjaar;
                    $doc->brandstof = $vehicle->brandstof;
                    $doc->btw_marge = $vehicle->btw_marge;
                    $doc->carrosserie = $vehicle->carrosserie;
                    $doc->demovoertuig = $vehicle->demovoertuig;
                    $doc->energielabel = $vehicle->energielabel;
                    $doc->geplaatst = gmdate('Y-m-d\TH:i:s\Z', strtotime($vehicle->geplaatst));
                    $doc->kenteken = $vehicle->kenteken;
                    $doc->klantnr = $vehicle->klantnr;
                    $doc->latlong = ($vehicle->lat && $vehicle->lon ? "$vehicle->lat,$vehicle->lon" : "");
                    $doc->massa = $vehicle->massa;
                    $doc->max_trekgewicht = $vehicle->max_trekgewicht;
                    $doc->merk = $vehicle->merk;
                    $doc->merkdealer = $vehicle->merkdealer;
                    $doc->provincie = $vehicle->provincie;
                    $doc->model = $vehicle->model;
                    $doc->nieuw_voertuig = $vehicle->nieuw_voertuig;
                    $doc->opties = $vehicle->merk . ' ' . $vehicle->model . ' ' . $vehicle->type . ' ' . $vehicle->opties;
                    $doc->tellerstand = $vehicle->tellerstand;
                    $doc->transmissie = $vehicle->transmissie;
                    $doc->type = $vehicle->type;
                    $doc->verkoopprijs_particulier = $vehicle->verkoopprijs_particulier ?? 0;
                    $doc->vermogen_motor_pk = $vehicle->vermogen_motor_pk;
                    $doc->voertuigsoort = $vehicle->voertuigsoort;

                    if ($this->destination == 'smileycar') {
                        $doc->images_ready = $vehicle->images_ready;
                        $doc->gaspedaal = $vehicle->gaspedaal;
                    }
                    if ($this->destination == 'autovoorraad24' || $this->destination == 'digifresh') {
                        $doc->verkoopprijs_handel = $vehicle->verkoopprijs_handel ?? 0;
                    }

                    $docs[] = $doc;
                }

                $countUpdated += count($docs);

                // add the documents and a commit command to the update query
                $update->addDocuments($docs);
                $update->addCommit();

                // this executes the query and returns the result
                $result = $this->client->update($update);
            });

        echo('Vehicles imported: ' . $countUpdated);
    }

    private function initialize()
    {
        switch ($this->destination) {
            case 'autovoorraad24':
                $endPoint = 'autovoorraad24';
                $this->voertuigen_table = 'voertuigen_global';
                $this->autobedrijven_table = 'autobedrijven';
                break;
            case 'autovoorraad24_strato':
                $endPoint = 'autovoorraad24_strato';
                $this->voertuigen_table = 'voertuigen_global';
                $this->autobedrijven_table = 'autobedrijven';
                break;
            default:
                $endPoint = $this->destination;
                $this->destination = str_replace('_strato', '', $this->destination);
                $this->voertuigen_table = 'voertuigen_' . $this->destination;
                $this->autobedrijven_table = 'autobedrijven_' . $this->destination;
        }

        $this->client->setDefaultEndpoint($endPoint);

    }

    public function setLatLong()
    {
        $autobedrijven = DB::table($this->autobedrijven_table)->get();

        foreach ($autobedrijven as $autobedrijf) {

            if (empty($autobedrijf->lat) || empty($autobedrijf->lon)) {

                $apiResult = $this->getLatLong($autobedrijf->adres, $autobedrijf->postcode, $autobedrijf->plaats);

                if (isset($apiResult["data"])) {

                    $latlong = [
                        'lat' => $apiResult["data"][0]["latitude"] ?? null,
                        'lon' => $apiResult["data"][0]["longitude"] ?? null
                    ];
                    DB::table($this->autobedrijven_table)->where('klantnr', $autobedrijf->klantnr)->update($latlong);
                }
            }
        }
    }

    private function getLatLong($adres, $zipcode, $city)
    {
        $queryString = http_build_query([
            'access_key' => '30cd4d3c5fa4142b1b61836885285e22',
            'query' => "$adres $zipcode $city",
            'country' => 'NL',
            'output' => 'json',
            'limit' => 1,
        ]);

        $ch = curl_init(sprintf('%s?%s', 'http://api.positionstack.com/v1/forward', $queryString));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $json = curl_exec($ch);

        curl_close($ch);

        return json_decode($json, true);
    }

    public function ping()
    {
        // create a ping query
        $ping = $this->client->createPing();

        // execute the ping query
        try {
            $this->client->ping($ping);
            return response()->json('OK');
        } catch (\Solarium\Exception $e) {
            return response()->json('ERROR', 500);
        }
    }
}
