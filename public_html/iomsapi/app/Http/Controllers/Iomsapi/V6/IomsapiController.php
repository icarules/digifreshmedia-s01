<?php

namespace App\Http\Controllers\Iomsapi\V6;

use App\Apikey;
use App\Http\Controllers\Controller;
use App\V6\Inventory;
use App\Calculations\Lease;
use Collator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IomsapiController extends Controller
{
    const MAX_RESULTS = 1000000;

    protected $apiKey;

    protected $inventory;
    protected $table;
    protected $autobedrijvenTable;

    protected $clientNrs = [];
    protected $activeClientNrs = [];
    protected $onlyActiveClients = false;
    protected $filterFields = [];
    protected $multiselect = false;

    protected $solarium;
    protected $solrEndpoint;

    public function __construct(\Solarium\Client $solarium, Request $request)
    {
        $this->solarium = $solarium;
        $this->solarium->getPlugin('postbigrequest');

        $token = $request->header('X-API-KEY') ?: $request->input('apikey');
        $this->apiKey = Apikey::where('api_key', $token)->first();

        $this->initialize();

        Log::channel('iomsapi')->info('ApiKey: ' . $this->apiKey->api_key . PHP_EOL . 'Client: ' . $this->apiKey->client_name . PHP_EOL . 'Url: ' . $request->fullUrl());
    }

    protected function initialize()
    {
        if ($this->apiKey) {

            // Get the model
            $this->inventory = new Inventory();

            $tableData = json_decode($this->apiKey->tables);

            if (is_array($tableData)) {
                foreach ($tableData as $table) {
                    $this->table = $table->tableName;
                }
            }

            switch ($this->apiKey->api_key) {
                case 'CRuBIwJJbl0mp84u0yyRzZIQhj3LtzshCw8r6wfh': // carhotspot
                    $this->autobedrijvenTable = 'autobedrijven_carhotspot';
                    $this->solrEndpoint = 'carhotspot';
                    $this->onlyActiveClients = false;
                    break;
                default:
                    $this->autobedrijvenTable = 'autobedrijven';
                    $this->solrEndpoint = 'autovoorraad24';
                    $this->onlyActiveClients = true;
            }

            if ($this->onlyActiveClients) {
                // get all active clients from autobedrijven tabel
                $autobedrijven = DB::table($this->autobedrijvenTable)
                    ->where('actief', 'J')
                    ->when(count($this->clientNrs) > 0, function ($query) {
                        return $query->whereIn('klantnr', $this->clientNrs);
                    })->pluck('klantnr')->all();

                $this->activeClientNrs = $autobedrijven;
            }
        }

        Log::channel('iomsapi')->info('Solr endpoint: ' . $this->solrEndpoint);

        $this->setFilters();
    }

    protected function setFilters()
    {
        // set filters
        // solr name => posted filter name
        $this->filterFields = array(
            'id'                       => 'hexonnr',
            'voertuigsoort'            => 'soort',
            'merk'                     => 'merk',
            'model'                    => 'model',
            'brandstof'                => 'brandstof',
            'basiskleur'               => 'kleur',
            'carrosserie'              => 'carrosserie',
            'transmissie'              => 'transmissie',
            'bouwjaar'                 => 'bouwjaar',
//            'nieuw_voertuig'           => 'nieuw',
            'verkoopprijs'             => 'prijs',
            'kenteken'                 => 'kenteken',
//            'demovoertuig'             => 'demo',
            'btwmarge'                 => 'btw_marge',
//            'bijtelling_pct'           => 'bijtelling_pct',
//            'voordeelpercentage'       => 'voordeelpercentage',
//            'aantal_zitplaatsen'       => 'aantal_zitplaatsen',
//            'aantal_deuren'            => 'aantal_deuren',
//            'energielabel'             => 'energielabel',
//            'max_trekgewicht'          => 'max_trekgewicht',
            'klantnr'                  => 'autobedrijf',
//            'merkdealer'               => 'merkdealer',
//            'provincie'                => 'provincie',
//            'verkoopprijs_handel'      => 'verkoopprijs_handel',
        );
    }

    /**
     * Create a new controller instance.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventory(Request $request)
    {
        // get solr client instance
        $this->solarium->setDefaultEndpoint($this->solrEndpoint);
        $query = $this->solarium->createSelect();

//        $query->setFields('hexonnr');

        $this->buildQuery($request, $query);

        $search = $request->input('trefwoorden');

        if ($search && strlen(trim($search)) > 0) {
            $searchQuery = 'merk:' . $search;
            $searchQuery .= ' OR model:' . $search;
            $searchQuery .= ' OR internetbenaming:' . $search;
            $query->setQuery($searchQuery);
        } else {
            $query->setQuery('*:*');
        }

//        $zip = $request->input('postcode');
//        $distance = $request->input('afstand');
//        if ($zip && $distance) {
//            // get the lat long coordinates for the zip
//            $latlong = $this->getZipLatLong($zip);
//        }


        $order = $this->getOrderParams($request);

        $page = $request->input('page', 1);
        $limit = $request->input('perPage', 20);
        $limit = (min($limit, self::MAX_RESULTS));
        $offset = $limit * ($page - 1);

        $excludeOptions = ($request->input('exclude_options') == 1);

        if ($request->input('random') != 1) {
            foreach ($order['solr'] as $orderBy => $orderDirection) {
                $query->addSort($orderBy, $orderDirection);
            }
            $query->setStart($offset);
            $query->setRows($limit);

            if (!$excludeOptions) {
                $this->setFacets($query);
            }
        } else {
            $query->addSort('random_' . mt_rand(), $query::SORT_DESC);
            $query->setRows($limit);
        }

        $resultset = $this->solarium->select($query);
        $response['total'] = $resultset->getNumFound();

        if ($request->input('random') != 1 && !$excludeOptions) {
            $facetsSets          = $resultset->getFacetSet();
            $response['options'] = $this->setOptions($facetsSets);
            $response = $this->getFacetPivots($facetsSets, $response);
        }

        $hexonNrs = [];
        foreach ($resultset as $document) {
            $hexonNrs[] = $document->getFields()['id'];
        }

        $items = $this->inventory->getInventory($hexonNrs, $order['db']);

        $itemsWithJson = $items->toArray();

        $response['items'] = [];
        foreach ($itemsWithJson as $itemWithJson) {
            $responseItem = array_merge($itemWithJson['json_props'], $itemWithJson);
            unset($responseItem['json_props']);
            $response['items'][] = $responseItem;
        }
//        $response['items'] = $itemsWithJson;

        return response()->json($response);
    }

    /**
     * Get all options for a single filter
     *
     * @param Request $request
     * @param         $option
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOptions(Request $request, $option)
    {
        // get solr client instance
        $this->solarium->setDefaultEndpoint($this->solrEndpoint);
        $query = $this->solarium->createSelect();

        $this->buildQuery($request, $query);

        $facets = $query->getFacetSet();
        $facets->setSort('index');
        $facets->setLimit(10000);
        $facets->createFacetField($option)->setField($option);

        $resultSet = $this->solarium->select($query);

        $facetSets = $resultSet->getFacetSet();

        $options = [];
        foreach ($facetSets as $facetName => $facet) {
            if (method_exists($facet, 'getValues')) {
                $options = $facet->getValues();
            } else if (method_exists($facet, 'getValue')) {
                $options = $facet->getValue();
            }
        }

        if (count($options) > 0) {
            $options = array_filter($options);
            array_walk($options, function (&$value, $key) {
                $value = Str::slug($key);
            });

            $options = array_flip($options);
            ksort($options);
        }

        return response()->json($options);
    }

    /**
     * Set the option fields
     *
     * @param $itemsCollection
     *
     * @return array
     */
    protected function setOptions($facetSets)
    {
        $options = [];

        foreach ($facetSets as $facetName => $facet) {
            if (method_exists($facet, 'getValues')) {
                $options['opties_' . $facetName] = $facet->getValues();
            } else if (method_exists($facet, 'getValue')) {
                $options['opties_' . $facetName] = $facet->getValue();
            }
        }
        $options['opties_autobedrijf'] = $this->inventory->getCompanyNames($this->clientNrs);

        $collator = new Collator('fr_FR');
        $collator->setAttribute(Collator::CASE_FIRST, Collator::OFF);

        $optionsMerk = array_keys($options['opties_merk']);
        $collator->sort($optionsMerk, Collator::SORT_STRING);
        $keysMerk = array_flip($optionsMerk);
        $options['opties_merk'] = array_merge($keysMerk, $options['opties_merk']);

        $optionsModel = array_keys($options['opties_model']);
        $collator->asort($optionsModel, Collator::SORT_STRING);
        $keysModel = array_flip($optionsModel);
        $options['opties_model'] = array_replace($keysModel, $options['opties_model']);

//        $options['opties_soort_voertuig'] = [];
//        $options['opties_soort_voertuig']['Occasion'] = $options['opties_nieuw_voertuig']['N'] ?? 0;
//        $options['opties_soort_voertuig']['Nieuw'] = $options['opties_nieuw'];
//        $options['opties_soort_voertuig']['Demo'] = $options['opties_demo'];


        return $options;
    }


    private function getFacetPivots($facets, $response)
    {
        $brandPivots = [
            'brandModels',
//            'brandFuels',
//            'brandNieuw',
//            'brandDemo',
            'brandBodies',
            'brandTransmissions',
//            'brandColors',
//            'brandDoors',
//            'brandSeats',
//            'brandEnergieLabels',
            'brandBTWMarges'
        ];

//        if ($this->solrEndpoint == 'smileycar') {
//            $brandPivots[] = 'brandProvincies';
//            $brandPivots[] = 'brandMerkdealer';
//        }

        foreach ($brandPivots as $brandPivot) {
            $response[$brandPivot] = [];

            $pivot = $facets->getFacet($brandPivot);
            foreach ($pivot as $bP) {
                $brand = $bP->getValue();
                $subPivot = $bP->getPivot();

                $response[$brandPivot][$brand] = [];
                foreach ($subPivot as $p) {
                    $response[$brandPivot][$brand][$p->getValue()] = $p->getCount();
                }
            }
        }

        $brandModelPivots = [
//            'brandModelFuels',
//            'brandModelNieuw',
//            'brandModelDemo',
            'brandModelBodies',
            'brandModelTransmissions',
//            'brandModelColors',
//            'brandModelSeats',
//            'brandModelDoors',
//            'brandModelEnergieLabels',
            'brandModelBTWMarges'
        ];

//        if ($this->solrEndpoint == 'smileycar') {
//            $brandModelPivots[] = 'brandModelProvincies';
//            $brandModelPivots[] = 'brandModelMerkdealer';
//        }

        foreach ($brandModelPivots as $brandModelPivot) {
            $response[$brandModelPivot] = [];

            $pivot = $facets->getFacet($brandModelPivot);
            foreach ($pivot as $bP) {
                $brand = $bP->getValue();
                $models = $bP->getPivot();

                $response[$brandModelPivot][$brand] = [];
                foreach ($models as $model) {
                    $subPivots = $model->getPivot();

                    foreach ($subPivots as $subPivot) {
                        $response[$brandModelPivot][$brand][$model->getValue()][$subPivot->getValue()] = $subPivot->getCount();
                    }
                }
            }
        }

//        $response['brandSoorten'] = [];
//        foreach ($response['brandNieuw'] as $brand => $item) {
//            $response['brandSoorten'][$brand]['Occasion'] = $item['N'] ?? 0;
//            $response['brandSoorten'][$brand]['Nieuw'] = $item['J'] ?? 0;
//        }
//        foreach ($response['brandDemo'] as $brand => $item) {
//            $response['brandSoorten'][$brand]['Demo'] = $item['J'] ?? 0;
//        }
//
//        $response['brandModelSoorten'] = [];
//        foreach ($response['brandModelNieuw'] as $brand => $models) {
//            foreach ($models as $model => $item) {
//                $response['brandModelSoorten'][$brand][$model]['Occasion'] = $item['N'] ?? 0;
//                $response['brandModelSoorten'][$brand][$model]['Nieuw'] = $item['J'] ?? 0;
//            }
//        }
//        foreach ($response['brandModelDemo'] as $brand => $models) {
//            foreach ($models as $model => $item) {
//                $response['brandModelSoorten'][$brand][$model]['Demo'] = $item['J'] ?? 0;
//            }
//        }
//
//        unset($response['brandNieuw'], $response['brandDemo'], $response['brandModelNieuw'], $response['brandModelDemo']);

        return $response;
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
        $item = Inventory::select($this->table . '.*', $this->table . '.klantnr')->where('hexonnr', $hexonId)
            ->when($this->autobedrijvenTable, function($query) {
                return $query->addSelect($this->autobedrijvenTable .'.*');
            })
            ->when(count($this->activeClientNrs) > 0, function($query) {
                return $query->whereIn($this->table . '.klantnr', $this->activeClientNrs);
            })
            ->when($this->autobedrijvenTable, function($query) {
                return $query->leftjoin($this->autobedrijvenTable, $this->table.'.klantnr', '=', $this->autobedrijvenTable.'.klantnr');
            })
            ->first();

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
        $this->multiselect = (bool) $request->input('multiselect');

        $filters = [];
        foreach ($this->filterFields as $key => $filterField) {
            $filters[$key] = $request->input($filterField);
            if ($key == 'hexonnr' && !is_numeric($filters[$key])) {
                $filters['hexonnr'] = false;
            }
            if ($key == 'demovoertuig' && $request->input($filterField) == 'J') {
                $filters['merkdealer'] = 'J';
            }
            if ($key == 'nieuw_voertuig' && strtoupper($request->input($filterField)) == 'J') {
                $filters['tellerstand'] = '[* TO 4999]';
            }
        }
        // 0 or "0" are valid filter values, so don't filter them out
        $filters= array_filter($filters, function($val) { return ($val || is_numeric($val));});

        if (array_key_exists('nieuw_voertuig', $filters)) {
            $filters['nieuw_voertuig'] = strtoupper($filters['nieuw_voertuig']);
        }

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
//        $leasePriceRangeKey = 'lease_price_' . $this->apiKey->api_key . '_i';

        // Get range filters
        $filterRanges = array(
            'bouwjaar'                 => 'bouwjaar_range',
//            'nieuwprijs'               => 'nieuwprijs_range',
            'verkoopprijs'             => 'aanschafprijs_range',
//            $leasePriceRangeKey        => 'leaseprijs_range',
            'tellerstand'              => 'tellerstand_range',
//            'max_trekgewicht'          => 'trekgewicht_range',
//            'massa'                    => 'gewicht_range',
//            'vermogen_motor_pk'        => 'vermogen_pk_range',
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
     * Get the filter lists from the request
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getFilterLists(Request $request): array
    {
        // Get list filters
        $filterLists = array(
            'klantnr' => 'klantnrs',
            'hexonnr' => 'hexonnrs',
        );

        $filtersThese = [];
        foreach ($filterLists as $key => $filterList) {
            if ($request->input($filterList)) {
                $filtersThese[$key] = explode(',', $request->input($filterList));
            }
        }

        if (!array_key_exists('klantnr', $filtersThese)) {
            if (count($this->activeClientNrs) > 0) {
                $filtersThese['klantnr'] = $this->activeClientNrs;
            }
        }

        return $filtersThese;
    }

    /**
     * Get the filter lists from the request
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getOrFilters(Request $request): array
    {
        // Get filters
        $filterFields = array_flip($this->filterFields);

        // Get list filters
        $orFilters = $request->input('ofFilters') ?? [];

        $filters = [];
        if (is_array($orFilters)) {
            foreach ($orFilters as $index => $orFilter) {
                $key           = $filterFields[$index] ?? $index;
                $filters[] = $key . ':' . $orFilter;
            }
        }

        return $filters;
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
        // database
        $dbOrderValues = [
            'price'       => 'verkoopprijs',
            'prijs'       => 'verkoopprijs',
            'make'        => 'merk',
            'merk'        => 'merk',
            'model'       => 'model',
            'tellerstand' => 'tellerstand',
            'bouwjaar'    => 'bouwjaar',
            'geplaatst'   => 'datumin',
        ];
        //solr
        $solrOrderValues = [
            'price'       => 'verkoopprijs',
            'prijs'       => 'verkoopprijs',
            'make'        => 'merk_str',
            'merk'        => 'merk_str',
            'model'       => 'model_str',
            'tellerstand' => 'tellerstand',
            'bouwjaar'    => 'bouwjaar',
            'geplaatst'   => 'last_modified',
        ];

        $orderBy        = $request->input('orderBy') ?: 'geplaatst';
        $orderDirection = $request->input('order') ?: 'desc';

        $orderBy = explode(',', $orderBy);
        $orderDirection = explode(',', $orderDirection);

        $arrOrder = [];
        $arrOrder['db'] = [];
        $arrOrder['solr'] = [];
        foreach ($orderBy as $i => $ob) {
            $arrOrder['db'][$dbOrderValues[$ob]] = $orderDirection[$i] ?? 'asc';
            $arrOrder['solr'][$solrOrderValues[$ob]] = $orderDirection[$i] ?? 'asc';
        }

        return $arrOrder;
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
        // get solr client instance
        $this->solarium->setDefaultEndpoint($this->solrEndpoint);
        $query = $this->solarium->createSelect();

        $query->setFields('hexonnr, merk, model');

        $this->buildQuery($request, $query);

        $page = $request->input('page', 1);
        $limit = $request->input('perPage', 100000);
        $offset = $limit * ($page - 1);

        $query->setStart($offset);
        $query->setRows($limit);

        $resultset = $this->solarium->select($query);

        $items = $resultset->getDocuments();
        return response()->json($items);
    }

    /**
     * @param $query
     */
    protected function setFacets($query): void
    {
        $facets = $query->getFacetSet();
        $facets->setSort('index');
        $facets->setLimit(10000);
//        $facets->createFacetField('bijtelling_pct')->setField('bijtelling_pct');
        $facets->createFacetField('bouwjaar')->setField('bouwjaar');
        $facets->createFacetField('btwmarge')->setField('btwmarge'); // has facet counter on smileycar
//        $facets->createFacetField('demovoertuig')->setField('demovoertuig');
//        $facets->createFacetField('energielabel')->setField('energielabel'); // has facet counter on smileycar
        $facets->createFacetField('klantnr')->setField('klantnr');
//        $facets->createFacetField('massa')->setField('massa');
//        $facets->createFacetField('trekgewicht')->setField('max_trekgewicht');
//        $facets->createFacetField('nieuw_voertuig')->setField('nieuw_voertuig');
        $facets->createFacetField('tellerstand')->setField('tellerstand');
        $facets->createFacetField('verkoopprijs')->setField('verkoopprijs');
//        $facets->createFacetField('vermogen_motor_pk')->setField('vermogen_motor_pk');
//        $facets->createFacetQuery('nieuw')->setQuery('nieuw_voertuig:J AND tellerstand:[* TO 4999]');
//        $facets->createFacetQuery('demo')->setQuery('demovoertuig:J AND merkdealer:J');
//        $facets->createFacetField('merkdealer')->setField('merkdealer');

        if ($this->multiselect) {
            $exclude = [
                'merk',
                'model',
                'merkmodel',
                'basiskleur',
                'brandstof',
                'transmissie',
                'voertuigsoort',
//                'aantal_deuren',
//                'aantal_zitplaatsen',
                'bouwjaar',
                'verkoopprijs',
                'tellerstand',
                'carrosserie',
            ];

            $facets->createFacetField(['local_key' => 'merk', 'local_exclude' => $exclude])->setField('merk'); // has facet counter on smileycar
            $facets->createFacetField(['local_key' => 'model', 'local_exclude' => $exclude])->setField('model'); // has facet counter on smileycar
            $facets->createFacetField(['local_key' => 'basiskleur', 'local_exclude' => ['basiskleur']])->setField('basiskleur'); // has facet counter on smileycar
            $facets->createFacetField(['local_key' => 'brandstof', 'local_exclude' => ['brandstof']])->setField('brandstof'); // has facet counter on smileycar
            $facets->createFacetField(['local_key' => 'carrosserie', 'local_exclude' => ['carrosserie']])->setField('carrosserie'); // has facet counter on smileycar
            $facets->createFacetField(['local_key' => 'transmissie', 'local_exclude' => ['transmissie']])->setField('transmissie'); // has facet counter on smileycar
            $facets->createFacetField(['local_key' => 'voertuigsoort', 'local_exclude' => ['voertuigsoort']])->setField('voertuigsoort'); // has facet counter on smileycar
            $facets->createFacetField(['local_key' => 'aantal_deuren', 'local_exclude' => ['aantal_deuren']])->setField('aantal_deuren'); // has facet counter on smileycar
            $facets->createFacetField(['local_key' => 'aantal_zitplaatsen', 'local_exclude' => ['aantal_zitplaatsen']])->setField('aantal_zitplaatsen'); // has facet counter on smileycar

            $facets->createFacetPivot(['local_key' => 'brandModels', 'local_exclude' => $exclude])->setFields('merk, model'); // has facet counter on smileycar

        } else {
            $facets->createFacetField('merk')->setField('merk'); // has facet counter on smileycar
            $facets->createFacetField('model')->setField('model'); // has facet counter on smileycar
            $facets->createFacetField('basiskleur')->setField('basiskleur');
            $facets->createFacetField('brandstof')->setField('brandstof'); // has facet counter on smileycar
            $facets->createFacetField('carrosserie')->setField('carrosserie'); // has facet counter on smileycar
            $facets->createFacetField('transmissie')->setField('transmissie'); // has facet counter on smileycar
            $facets->createFacetField('voertuigsoort')->setField('voertuigsoort');
//            $facets->createFacetField('aantal_deuren')->setField('aantal_deuren'); // has facet counter on smileycar
//            $facets->createFacetField('aantal_zitplaatsen')->setField('aantal_zitplaatsen'); // has facet counter on smileycar

            $facetPivot = $facets->createFacetPivot('brandModels');
            $facetPivot->addFields('merk,model');
        }

//        if ($this->solrEndpoint == 'smileycar') {
//            $facets->createFacetField('provincie')->setField('provincie');
//        }

//        $facetPivot = $facets->createFacetPivot('brandFuels');
//        $facetPivot->addFields('merk,brandstof');
        $facetPivot = $facets->createFacetPivot('brandBodies');
        $facetPivot->addFields('merk,carrosserie');

//        $facetPivot = $facets->createFacetPivot('brandNieuw');
//        $facetPivot->addFields('merk,nieuw_voertuig');
//        $facetPivot = $facets->createFacetPivot('brandDemo');
//        $facetPivot->addFields('merk,demovoertuig');
        $facetPivot = $facets->createFacetPivot('brandTransmissions');
        $facetPivot->addFields('merk,transmissie');
        $facetPivot = $facets->createFacetPivot('brandColors');
        $facetPivot->addFields('merk,basiskleur');
//        $facetPivot = $facets->createFacetPivot('brandDoors');
//        $facetPivot->addFields('merk,aantal_deuren');
//        $facetPivot = $facets->createFacetPivot('brandSeats');
//        $facetPivot->addFields('merk,aantal_zitplaatsen');
//        $facetPivot = $facets->createFacetPivot('brandEnergieLabels');
//        $facetPivot->addFields('merk,energielabel');
        $facetPivot = $facets->createFacetPivot('brandBTWMarges');
        $facetPivot->addFields('merk,btwmarge');

//        if ($this->solrEndpoint == 'smileycar') {
//            $facetPivot = $facets->createFacetPivot('brandProvincies');
//            $facetPivot->addFields('merk,provincie');
//            $facetPivot = $facets->createFacetPivot('brandMerkdealer');
//            $facetPivot->addFields('merk,merkdealer');
//        }

//        $facetPivot = $facets->createFacetPivot('brandModelFuels');
//        $facetPivot->addFields('merk,model,brandstof');
        $facetPivot = $facets->createFacetPivot('brandModelBodies');
        $facetPivot->addFields('merk,model,carrosserie');

//        $facetPivot = $facets->createFacetPivot('brandModelNieuw');
//        $facetPivot->addFields('merk,model,nieuw_voertuig');
//        $facetPivot = $facets->createFacetPivot('brandModelDemo');
//        $facetPivot->addFields('merk,model,demovoertuig');
        $facetPivot = $facets->createFacetPivot('brandModelTransmissions');
        $facetPivot->addFields('merk,model,transmissie');
        $facetPivot = $facets->createFacetPivot('brandModelColors');
        $facetPivot->addFields('merk,model,basiskleur');
//        $facetPivot = $facets->createFacetPivot('brandModelSeats');
//        $facetPivot->addFields('merk,model,aantal_zitplaatsen');
//        $facetPivot = $facets->createFacetPivot('brandModelDoors');
//        $facetPivot->addFields('merk,model,aantal_deuren');
//        $facetPivot = $facets->createFacetPivot('brandModelEnergieLabels');
//        $facetPivot->addFields('merk,model,energielabel');
        $facetPivot = $facets->createFacetPivot('brandModelBTWMarges');
        $facetPivot->addFields('merk,model,btwmarge');

//        if ($this->solrEndpoint == 'smileycar') {
//            $facetPivot = $facets->createFacetPivot('brandModelProvincies');
//            $facetPivot->addFields('merk,model,provincie');
//            $facetPivot = $facets->createFacetPivot('brandModelMerkdealer');
//            $facetPivot->addFields('merk,model,merkdealer');
//        }
    }

    /**
     * @param Request $request
     * @param         $query
     *
     * @return void
     */
    public function buildQuery(Request $request, $query): void
    {
        $usedFilterNames = [];
        $filters         = $this->getFilters($request);
        foreach ($filters as $filter => $value) {
            $filterName        = (in_array($filter, $usedFilterNames)) ? $filter . '1' : $filter;
            $usedFilterNames[] = $filterName;
            if (is_array($value)) {
                if ($this->multiselect) {
                    if ($filterName == 'model') {
                        continue;
                    }
                    if ($filterName == 'merk' && array_key_exists('model', $filters)) {
                        $qpv = $this->solarium->createSelect();
                        $qfs = $qpv->getFacetSet();
                        $qf  = $qfs->createFacetPivot('brandmodel');
                        $qf->addFields('merk,model');
                        $r  = $this->solarium->select($qpv);
                        $fp = $r->getFacetSet()->getFacet('brandmodel');
                        $merkModel = [];
                        foreach ($fp as $bP) {
                            $brand    = $bP->getValue();
                            $subPivot = $bP->getPivot();

                            $merkModel[$brand] = [];
                            foreach ($subPivot as $p) {
                                $merkModel[$brand][] = $p->getValue();
                            }
                        }
                        $aq = [];
                        foreach ($value as $brand) {
                            $tq     = "($filterName:$brand";
                            $models = array_intersect($merkModel[$brand], $filters['model']);
                            if (count($models)) {
                                $tq .= " AND model:(\"" . implode("\" OR \"", $filters['model']) . "\")";
                            }
                            $tq   .= ')';
                            $aq[] = $tq;
                        }
                        $q  = implode(' OR ', $aq);
                        $fq = $query->createFilterQuery('merkmodel')->setQuery($q)->addTag('merkmodel');
                    } else {
                        $fq = $query->createFilterQuery($filterName)->setQuery("$filterName:(\"" . implode('" OR "',
                                $value) . '")')->addTag($filterName);
                    }
                } else {
                    $fq = $query->createFilterQuery($filterName)->setQuery("$filterName:(\"" . implode('" OR "',
                            $value) . '")')->addTag($filterName);
                }
            } else {
                if ($filter == 'tellerstand') {
                    $fq = $query->createFilterQuery($filterName)->setQuery("$filter:$value")->addTag($filterName);
                } else {
                    if (in_array($filter, [
                        'basiskleur',
                        'brandstof',
                        'btw_marge',
                        'carrosserie',
                        'demovoertuig',
                        'energielabel',
                        'kenteken',
                        'merk',
                        'merkdealer',
                        'model',
                        'nieuw_voertuig',
                        'transmissie',
                        'type',
                        'voertuigsoort'
                    ])) {
//                        $fq = $query->createFilterQuery($filter)->setQuery("$filter:\"$value\" OR {$filter}_lc:\"$value\"")->addTag($filterName);
                        $fq = $query->createFilterQuery($filter)->setQuery("$filter:\"$value\"")->addTag($filterName);
                    } else {
                        $fq = $query->createFilterQuery($filter)->setQuery("$filter:\"$value\"")->addTag($filterName);
                    }
                }
            }
        }

        $filtersBetween = $this->getFilterRanges($request);
        foreach ($filtersBetween as $filter => $range) {
            $filterName        = (in_array($filter, $usedFilterNames)) ? $filter . '1' : $filter;
            $usedFilterNames[] = $filterName;
            $from              = is_numeric($range[0]) ? $range[0] : '*';
            $to                = is_numeric($range[1]) ? $range[1] : '*';
            $fq                = $query->createFilterQuery($filterName)->setQuery("$filter:[$from TO $to]")->addTag($filterName);
        }

        $filtersList = $this->getFilterLists($request);
        foreach ($filtersList as $filter => $arrValues) {
            $filterName        = (in_array($filter, $usedFilterNames)) ? $filter . '1' : $filter;
            $usedFilterNames[] = $filterName;
            $fq                = $query->createFilterQuery($filterName)->setQuery("$filter:(" . implode(' OR ',
                    $arrValues) . ')');
        }

        $orFilters = $this->getOrFilters($request);
        if (count($orFilters) > 0) {
            $fq = $query->createFilterQuery('orFilter')->setQuery(implode(' OR ', $orFilters));
        }
    }

    private function getZipLatLong($zipcode)
    {
        $queryString = http_build_query([
            'access_key' => '30cd4d3c5fa4142b1b61836885285e22',
            'query' => "$zipcode heerlen",
            'country' => 'NL',
            'output' => 'json',
            'limit' => 1,
        ]);

        $ch = curl_init(sprintf('%s?%s', 'http://api.positionstack.com/v1/forward', $queryString));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $json = curl_exec($ch);

        $error = curl_error ($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        return json_decode($json, true);
    }
}
