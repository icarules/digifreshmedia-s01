<?php


namespace App\Http\Controllers\Iomsapi;

use App\Apikey;
use App\Http\Controllers\Controller;
use App\Jobs\SolrUpdateLeasePrice;
use App\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\Debugbar\Facade as Debugbar;
use Illuminate\Support\Facades\Cookie;

class ApikeyController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $resultPerPage = $request->resultsPerPage ?? ($request->cookie('resultsPerPage') ?? 16);
        Cookie::queue('resultsPerPage', $resultPerPage, 60*60*24*365);

        $orderBy = $request->orderBy ?? ($request->cookie('orderBy') ?? 'client_name');
        Cookie::queue('orderBy', $orderBy, 60*60*24*365);

        $sort = $request->sort ?? ($request->cookie('sort') ?? 'asc');
        Cookie::queue('sort', $sort, 60*60*24*365);

        // Get all api keys
        $apiKeys = Apikey::orderBy($orderBy, $sort)->paginate($resultPerPage);

        $vehicleTables = DB::select("SELECT table_name 
                                    FROM information_schema.tables 
                                    WHERE table_schema = 'digifremnl_hexon' 
                                      AND table_type = 'BASE TABLE'
                                      AND table_name LIKE 'voertuigen%' 
                                    ORDER BY table_name;");

        $dealerTables = DB::select("SELECT table_name 
                                    FROM information_schema.tables 
                                    WHERE table_schema = 'digifremnl_hexon' 
                                      AND table_type = 'BASE TABLE'
                                      AND table_name LIKE 'autobedrijven%' 
                                    ORDER BY table_name;");

        // Show List of api keys
        return view('apikey.index', [
            'apiKeys'        => $apiKeys,
            'vehicleTables'  => $vehicleTables,
            'dealerTables'   => $dealerTables,
            'resultsPerPage' => $resultPerPage,
            'orderBy'        => $orderBy,
            'sort'           => $sort
        ]);
    }

    /**
     * Create a new api key.
     *
     * @param  Request  $request
     * @return Response
     */
    public function generate()
    {
        // Generate a new apikey
        do {
            $apikey = Str::random(40);
        } while (self::apiKeyExists($apikey));

        return response(['apikey' => $apikey]);
    }

    /**
     * Store a new api key.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        // Validate the request...

        $tables = $this->getTables($request);

        //$clientNrs = implode(',', array_filter(array_map('trim', preg_split("/[,:;\.\s]/", $request->clientNrs))));
        $leaseInterestRate =  trim(str_replace('%', '', str_replace(',', '.', $request->leaseInterestRate)));
        $leaseInterestRateGeneral =  trim(str_replace('%', '', str_replace(',', '.', $request->leaseInterestRateGeneral)));

        if (empty($leaseInterestRate)) {
            $leaseInterestRate = null;
        }

        if (empty($leaseInterestRateGeneral)) {
            $leaseInterestRateGeneral = null;
        }

        $apikey   =   Apikey::updateOrCreate(
            ['api_key' => $request->apiKey],
            [
                'client_name' => $request->clientName,
                'tables' => json_encode($tables),
                'lease_down_payment' => $request->leaseDownPayment,
                'lease_final_payment' => $request->leaseFinalPayment,
                'lease_default_term' => $request->leaseDefaultTerm,
                'lease_max_age' => $request->leaseMaxAge,
                'lease_min_term' => $request->leaseMinTerm,
                'lease_max_term' => $request->leaseMaxTerm,
                'lease_interest_rate_general' => $leaseInterestRateGeneral,
                'lease_interest_rate' => $leaseInterestRate,
                'use_lease_price_filter' => $request->use_lease_price_filter ?? 0,
            ]
        );

        return response($apikey);
    }

    private function apiKeyExists($apiKey)
    {
        return Apikey::where('api_key', $apiKey)->first();
    }

    /**
     * Edit an api key.
     *
     * @return Response
     */
    public function edit($id) {
        $Apikey = Apikey::find($id);
        return response($Apikey);
    }

    /**
     * Update an api key.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        // Validate the request...
        $leaseInterestRate =  trim(str_replace('%', '', str_replace(',', '.', $request->leaseInterestRate)));
        $leaseInterestRateGeneral =  trim(str_replace('%', '', str_replace(',', '.', $request->leaseInterestRateGeneral)));

        if (empty($leaseInterestRate)) {
            $leaseInterestRate = null;
        }

        if (empty($leaseInterestRateGeneral)) {
            $leaseInterestRateGeneral = null;
        }

        $tables = $this->getTables($request);

        $Apikey = Apikey::find($id);

        $Apikey->client_name = $request->clientName;
        $Apikey->tables = json_encode($tables);
        $Apikey->lease_down_payment = $request->leaseDownPayment;
        $Apikey->lease_final_payment = $request->leaseFinalPayment;
        $Apikey->lease_default_term = $request->leaseDefaultTerm;
        $Apikey->lease_max_age = $request->leaseMaxAge;
        $Apikey->lease_min_term = $request->leaseMinTerm;
        $Apikey->lease_max_term = $request->leaseMaxTerm;
        $Apikey->lease_interest_rate_general = $leaseInterestRateGeneral;
        $Apikey->lease_interest_rate = $leaseInterestRate;
        $Apikey->active = $request->active;

        $Apikey->save();
        return redirect()->route('apikey.index');
    }

    /**
     * Delete an api key.
     *
     * @param  Request  $request
     * @return Response
     */
    public function delete($id)
    {
        // Validate the request...

        $Apikey = Apikey::findOrFail($id);

        return view('apikey.delete', ['apikey' => $Apikey]);
    }

    public function destroy($id) {
        $apikey = Apikey::findOrFail($id)->delete();
        return response(['apikey' => $apikey]);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getTables(Request $request): array
    {
        $tables = [];
        if ($request->has('voertuigen')) {
            $clientNrs = implode(',', array_filter(array_map('trim', preg_split("/[,:;\.\s]/", $request->clientNrs))));
            $tables[]  = ['tableName' => 'voertuigen', 'clientNrs' => $clientNrs];
        }

        if ($request->has('voertuigen_autovooru')) {
            $tables[] = ['tableName' => 'voertuigen_autovooru'];
        }

        if ($request->has('voertuigen_budgetleasenl')) {
            $tables[] = ['tableName' => 'voertuigen_budgetleasenl'];
        }

        if ($request->has('voertuigen_carhotspot')) {
            $tables[] = ['tableName' => 'voertuigen_carhotspot'];
        }

        if ($request->has('voertuigen_carlease4u')) {
            $tables[] = ['tableName' => 'voertuigen_carlease4u'];
        }

        if ($request->has('voertuigen_leaseone')) {
            $tables[] = ['tableName' => 'voertuigen_leaseone'];
        }

        if ($request->has('voertuigen_onlineautoleasen')) {
            $tables[] = ['tableName' => 'voertuigen_onlineautoleasen'];
        }

        if ($request->has('voertuigen_orangelease')) {
            $tables[]  = ['tableName' => 'voertuigen_orangelease'];
        }

        if ($request->has('voertuigen_simpelautolease')) {
            $tables[] = ['tableName' => 'voertuigen_simpelautolease'];
        }

        if ($request->has('voertuigen_smileycar')) {
            $tables[] = ['tableName' => 'voertuigen_smileycar'];
        }

        if ($request->has('voertuigen_global')) {
            $clientNrs = implode(',', array_filter(array_map('trim', preg_split("/[,:;\.\s]/", $request->clientNrs_global))));
            $tables[]  = ['tableName' => 'voertuigen_global', 'clientNrs' => $clientNrs];
        }

        if ($request->has('voertuigen_incrementeel')) {
            $clientNrs = implode(',', array_filter(array_map('trim', preg_split("/[,:;\.\s]/", $request->clientNrs_incrementeel))));
            $tables[]  = ['tableName' => 'voertuigen_incrementeel', 'clientNrs' => $clientNrs];
        }

        return $tables;
    }

//    public function updateLeasePrices($id)
//    {
//        // get the api key
//        $apikey = Apikey::find($id);
//
//        // get the interest vars for the calculation
//
//        // get the solr endpoint
//        switch ($apikey->api_key) {
//            case '4h0FnsBKyLzN1eTIl2dkXhJU9aiAcXkIa1iHrNWq': // autovoorraad24
//                $solrEndpoint = 'autovoorraad24';
//                break;
//            case 'dHiyOLojk2gTR5yzHGpUH8dQXf1yH3h93FWCLsXG': // smileycar
//                $solrEndpoint = 'smileycar';
//                break;
//            case 'qAphYOc304g5GOXh6fYQvnzyTVYKuW0pGVfGdcVw': // leaseone
//                $solrEndpoint = 'leaseone';
//                break;
//            case 'hG3KUNfem1kofXJV45ARNQYlYNv5Fxzq0k0mFMxr': // budgetleasenl
//                $solrEndpoint = 'budgetleasenl';
//                break;
//            case '2Zp2KZGhPy9DbtCAjpEXKYTDH6WO6FVZtRFxjs6B': // carlease4u
//                $solrEndpoint = 'carlease4u';
//                break;
//            case 'N8KIUYx7oqEJDd74WHXIH4k1eZd6g8G4uJynHlgC': // orangelease
//                $solrEndpoint = 'orangelease';
//                break;
//            case 'K19Frbyl0hzDueQTd0lfwLvLypZ5xrRyk8TXL7ck': // digidemo
//                $solrEndpoint = 'autovoorraad24';
//                break;
//            default:
//                $solrEndpoint = 'autovoorraad24';
//        }
//
//        // get the table and all clientnrs
//        $tableData = json_decode($apikey->tables);
//
//        $vehicleTable = null;
//        $clientNrs = [];
//        if (is_array($tableData)) {
//            $clientTables = [
//                'voertuigen',
//                'voertuigen_global',
//                'voertuigen_digifreshmedia',
//                'voertuigen_incrementeel'
//            ];
//            foreach ($tableData as $table) {
//                $vehicleTable = $table->tableName;
//                if (in_array($table->tableName, $clientTables)) {
//                    $clientNrs = explode(',', $table->clientNrs);
//                }
//            }
//        }
//
//        // get all vehicles
//        $vehicles = [];
//        if ($vehicleTable) {
//            $vehicles = new Vehicle();
//            $vehicles = $vehicles->setTable($vehicleTable)
//                ->select(['hexonnr', 'klantnr', 'verkoopprijs_particulier', 'bouwjaar', 'btw_marge', 'voertuigsoort'])
//                ->when(count($clientNrs) > 0, function ($query) use ($clientNrs) {
//                    return $query->whereIn('klantnr', $clientNrs);
//                })->get();
//        }
//
//        // dispatch a leaseprice calculation job for every vehicle
//        foreach ($vehicles as $vehicle) {
//            $this->dispatchUpdateLeasePriceJob($vehicle, $apikey, $solrEndpoint);
//        }
//
//        return response(['status' => true, "josbDispatched" => $vehicles->count()]);
//    }
//
//    protected function dispatchUpdateLeasePriceJob($vehicle, $apikey, $solrEndpoint): void
//    {
//        $solrJob              = new \stdClass();
//        $solrJob->action      = 'update';
//        $solrJob->destination = $solrEndpoint;
//        $solrJob->vehicle     = $vehicle;
//        $solrJob->apikey      = $apikey;
//
//        SolrUpdateLeasePrice::dispatch($solrJob)->onConnection('beanstalkd')->onQueue('solrsync_updateleaseprice');
//    }
}
