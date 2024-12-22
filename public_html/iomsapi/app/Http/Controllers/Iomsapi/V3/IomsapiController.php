<?php

namespace App\Http\Controllers\Iomsapi\V3;

use App\Apikey;
use App\Http\Controllers\Controller;
use App\V3\Inventory;
use App\Calculations\Lease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\Debugbar\Facade as Debugbar;

class IomsapiController extends Controller
{
    const MAX_RESULTS = 1000000;

    protected $apiKey;

    protected $clientNrs = [];

    protected $exclude = [];

    public function __construct(Request $request)
    {
        $token = $request->header('X-API-KEY') ?: $request->input('apikey');
        $this->apiKey = Apikey::where('api_key', $token)->first();
        if ($this->apiKey) {
            $tableData = json_decode($this->apiKey->tables);

            if (is_array($tableData)) {
                foreach ($tableData as $table) {
                    if ($table->tableName == 'voertuigen') {
                        $this->clientNrs = explode(',', $table->clientNrs);
                    }else if ($table->tableName == 'voertuigen_global') {
                        $this->clientNrs = explode(',', $table->clientNrs);
                    }
                    if (count($this->clientNrs) === 1 && trim($this->clientNrs[0]) === "") {
                        $this->clientNrs = [];
                    }
                }
            }

            if ($token == 'kxlS5yqdfHCfqbpgOFkkwY19Apd4X8U493xOxVz6') {
                $this->exclude = ['locatie_voertuig' => 'Haarlem'];
            }
        }
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function getInventory(Request $request)
    {
        $filters = $this->getFilters($request);

        $filtersBetween = $this->getFilterRanges($request);

        list($orderBy, $order) = $this->getOrderParams($request);

        // Search
        $search = $request->input('search');

        $perPage = $request->input('perPage', 20);
        $perPage = ($perPage <= self::MAX_RESULTS ? $perPage : self::MAX_RESULTS);
        $page = $request->input('page', 1);
        $limit = $request->input('perPage', 20);
        $limit = ($limit <= self::MAX_RESULTS ? $limit : self::MAX_RESULTS);
        $offset = $limit * ($page - 1);

        // Get the model
        $inventory = new Inventory();

        $items = $inventory->getInventory($this->clientNrs, $limit, $offset, $search, $filters, $filtersBetween, $orderBy, $order, false, $this->exclude);

//        $itemsCollection = $items->get();

        $itemsCount = $inventory->getInventoryCount($this->clientNrs, $search, $filters, $filtersBetween, $this->exclude);

        $response['options'] = $this->setOptions($itemsCount);

        $response['total'] = $itemsCount->count();

        $itemsWithJson = $items->toArray();

        $response['items'] = [];
        foreach ($itemsWithJson as $itemWithJson) {
            $responseItem = array_merge($itemWithJson, $itemWithJson['json_props']);
            unset($responseItem['json_props']);
            $response['items'][] = $responseItem;
        }

        return response()->json($response);
    }

    /**
     * Set the option fields
     *
     * @param $itemsCollection
     *
     * @return array
     */
    protected function setOptions($itemsCollection)
    {
        $options = [];

        $options['opties_soort'] = $itemsCollection->pluck('voertuigsoort');
        $options['opties_merk'] = $itemsCollection->pluck('merk');
        $options['opties_model'] = $itemsCollection->pluck('model');
        $options['opties_brandstof'] = $itemsCollection->pluck('brandstof');
        $options['opties_kleur'] = $itemsCollection->pluck('kleur');
        $options['opties_carrosserie'] = $itemsCollection->pluck('carrosserie');
        $options['opties_btwmarge'] = $itemsCollection->pluck('btw_marge');
        $options['opties_transmissie'] = $itemsCollection->pluck('transmissie');
        $options['opties_bouwjaar'] = $itemsCollection->pluck('bouwjaar');
        $options['opties_nieuw'] = $itemsCollection->pluck('nieuw_voertuig');
        $options['opties_aanschafprijs'] = $itemsCollection->pluck('verkoopprijs_particulier');
        $options['opties_leaseprijs'] = $itemsCollection->pluck('leasetermijn_digifresh');
        $options['opties_tellerstand'] = $itemsCollection->pluck('tellerstand');
        $options['opties_trekgewicht'] = $itemsCollection->pluck('max_trekgewicht_ongeremd');

        foreach ($options as &$option) {
            $option = $option->filter()->unique()->values()->all();
            sort($option);
        }
        return $options;
    }

    /**
     * Get an inventory item
     *
     * @param $hexonId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventoryItem($hexonId)
    {
        //whereIn('klantnr', $this->clientNrs)->
        $query = Inventory::where('hexonnr', $hexonId);

        foreach ($this->exclude as $column => $value) {
            $query->where($column, '!=' , $value);
        }

        $item = $query->first();

        $itemWithJson = [];
        if (!is_null($item)) {
            $itemWithJson = $item->toArray();
            $itemWithJson = array_merge($itemWithJson, $itemWithJson['json_props']);
            unset($itemWithJson['json_props']);
        }

        return response()->json($itemWithJson);
    }

    /**
     * Get the filters from the request
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getFilters(Request $request): array
    {
        // Get filters
        $filterFields = array(
            'voertuigsoort'  => 'soort',
            'merk'           => 'merk',
            'model'          => 'model',
            'brandstof'      => 'brandstof',
            'kleur'          => 'kleur',
            'btw_marge'      => 'btw_marge',
            'carrosserie'    => 'carrosserie',
            'transmissie'    => 'transmissie',
            'bouwjaar'       => 'bouwjaar',
            'nieuw_voertuig' => 'nieuw',
        );

        $filters = [];
        foreach ($filterFields as $key => $filterField) {
            $filters[$key] = $request->input($filterField);
        }
        $filters = array_filter($filters);

        return $filters;
    }

    /**
     * Get the filter ranges from the request
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getFilterRanges(Request $request): array
    {
        // Get range filters
        $filterRanges = array(
            'bouwjaar'                 => 'bouwjaar_range',
            'verkoopprijs_particulier' => 'aanschafprijs_range',
            'leasetermijn_digifresh'   => 'leaseprijs_range',
            'tellerstand'              => 'tellerstand_range',
            'max_trekgewicht_ongeremd' => 'trekgewicht_range'
        );

        $filtersBetween = [];
        foreach ($filterRanges as $key => $filterRange) {
            if ($request->input($filterRange)) {
                $filtersBetween[$key] = explode('|', $request->input($filterRange));
            }
        }
        return $filtersBetween;
    }

    /**
     * Get the order by and order directions from the request
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getOrderParams(Request $request): array
    {
        // Get the sort order
        $orderValues = [
            'price'       => 'verkoopprijs_particulier',
            'prijs'       => 'verkoopprijs_particulier',
            'make'        => 'merk',
            'merk'        => 'merk',
            'tellerstand' => 'tellerstand',
            'geplaatst'   => 'geplaatst',
        ];
        $orderBy     = $request->input('orderBy') ?? 'merk';
        $orderBy     = $orderValues[$orderBy] ?? $orderBy;
        $order       = $request->input('order') ?? 'asc';

        return array($orderBy, $order);
    }

    /**
     * Get the lease termijn
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLease(Request $request)
    {
        $calculator = $request->input('calculator');

        $downPayment = $this->apiKey->lease_down_payment;
        $interestRate = ($calculator == 'general' ? $this->apiKey->lease_interest_rate_general : $this->apiKey->lease_interest_rate);

        $lease = (new Lease($downPayment, $interestRate))->calculate($request);

        return response()->json($lease);
    }

    public function getInventoryList(Request $request)
    {
        $query = DB::table('voertuigen')
                    ->select('*');

        $query->whereIn('klantnr', $this->clientNrs);

        $filters = $this->getFilters($request);
        $filtersBetween = $this->getFilterRanges($request);

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

        $inventory = $query->get();

        return response()->json($inventory);

    }
}
