<?php


namespace App\Http\Controllers\Iomsapi;

use App\Apikey;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

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

    public function index()
    {
        // Get all api keys
        $apiKeys = Apikey::orderBy('id','desc')->paginate(8);

        // Show List of api keys
        return view('apikey.index', ['apiKeys' => $apiKeys]);
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

//        $clientNrs = implode(array_filter(array_map('trim', preg_split("/[,:;\.\s]/", $request->clientNrs))), ',');
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
                'lease_interest_rate_general' => $leaseInterestRateGeneral,
                'lease_interest_rate' => $leaseInterestRate
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
//        $Apikey->client_nrs = $request->clientNrs;
        $Apikey->tables = json_encode($tables);
        $Apikey->lease_down_payment = $request->leaseDownPayment;
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
            $clientNrs = implode(array_filter(array_map('trim', preg_split("/[,:;\.\s]/", $request->clientNrs))), ',');
            $tables[]  = ['tableName' => 'voertuigen', 'clientNrs' => $clientNrs];
        }

        if ($request->has('voertuigen_autovooru')) {
            $tables[] = ['tableName' => 'voertuigen_autovooru'];
        }

        if ($request->has('voertuigen_carhotspot')) {
            $tables[] = ['tableName' => 'voertuigen_carhotspot'];
        }

        if ($request->has('voertuigen_global')) {
            $clientNrs = implode(array_filter(array_map('trim', preg_split("/[,:;\.\s]/", $request->clientNrs_global))), ',');
            $tables[]  = ['tableName' => 'voertuigen_global', 'clientNrs' => $clientNrs];
        }
        
        if ($request->has('voertuigen_global_ol')) {
            $clientNrs = implode(array_filter(array_map('trim', preg_split("/[,:;\.\s]/", $request->clientNrs_global))), ',');
            $tables[]  = ['tableName' => 'voertuigen_global_ol', 'clientNrs' => $clientNrs];
        }
        

        return $tables;
}
}
