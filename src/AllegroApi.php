<?php

/**
 * @author Seweryn Jagusiak <jagusiak@gmail.com>
 */
class AllegroApi {

    const URL = 'http://webapi.allegro.pl/uploader.php?wsdl';
    const URL_NEW = 'https://webapi.allegro.pl/service.php?wsdl';
    
    const QUERY_SIZE = 1000;
    
    private $webApiKey;
    private $countryCode;
    private $client;

    /**
     * Deafult constructor
     * @param string $webApiKey Key configuration
     */
    public function __construct($webApiKey, $countryCode) {
        // store key
        $this->webApiKey = $webApiKey;
        $this->countryCode = $countryCode;
    }
    
    public function getCountryCodes() {
        $this->initClient();
        return $this->client->doGetCountries($this->countryCode, $this->webApiKey);
    }
    
    public function getCategories() {
        $this->initClient();
        return $this->client->doGetCatsData($this->countryCode, 0, $this->webApiKey)['cats-list'];
    }
    
    public function getFilters($query, $filters = null) {
        $this->initClient(true);
        
        if (empty($filters)) {
            $filters = [];
        }
        
        $filters[] = [
                'filterId' => 'search',
                'filterValueId' => ['query'], 
            ];
        
        $data = $this->client->doGetItemsList([
            'webapiKey' => $this->webApiKey,
            'countryId' => $this->countryCode,
            'resultScope' => 6,
            'filterOptions' => $filters,
            ]);
        return $data->filtersList->item;
    }
    
    public function searchItems($query) {
        $this->initClient(true);
        
        $params = $this->createParams($query);
        
        $data = [];
        
        $offset = 0;
        
        do {
            $params['resultOffset'] = $offset;
            
            $response = $this->client->doGetItemsList($params);
            
            $size = $response->itemsCount;
            
            if (!empty($response->itemsList)) {
                foreach ($response->itemsList->item as $item) {
                    $data[$item->itemId] = [
                        'title' => $item->itemTitle,
                        'price' => $item->priceInfo->item[0]->priceValue,
                    ];
                }
            }
            
            $offset += self::QUERY_SIZE;
            
        } while ($offset < $size);
        
        return $data;
    }
    
    private function initClient($newVersion = false) {
        // intitialize and store client;
        $this->client = new SoapClient($newVersion ? static::URL_NEW : static::URL , [
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        ]);
    }
    
    private function createParams($query) {
        return [
            'webapiKey' => $this->webApiKey,
            'countryId' => $this->countryCode,
            'filterOptions' => $this->createFilters($query),
            'sortOptions' => [
                'sortType' => 'price',
                'sortOrder' => 'asc'
            ],
            'resultSize' => 1000,
            'resultOffset' => 0,
            'resultScope' => 3
        ];
    }
    
    private function createFilters($query) {
        
        // create filters
        $filters = [];
        
        $this->filterSearch($filters, $query);
        $this->filterPrice($filters, $query);
        $this->filterCondition($filters, $query);
        $this->filterOfferType($filters, $query);
        
        return $filters;
    }
    
    private function filterSearch(&$filters, $query) {
        // add search
        $filters[] = [
            'filterId' => 'search',
            'filterValueId' => [$query['query']], 
        ];
    }
    
    private function filterPrice(&$filters, $query) {
        // add min/max price
        $minPrice = isset($query['min-price']) ? $query['min-price'] : null;
        $maxPrice = isset($query['max-price']) ? $query['max-price'] : null;
        if (!empty($minPrice) || !empty($maxPrice)) {
            $priceFilter[] = [
                'filterId' => 'price',
                'filterValueRange' => [], 
            ];
            
            if (!empty($minPrice)) {
                $priceFilter['filterValueRange']['rangeValueMin'] = $minPrice;
            }
            
            if (!empty($maxPrice)) {
                $priceFilter['filterValueRange']['rangeValueMax'] = $maxPrice;
            }
            
            $filters[] = $priceFilter;
        }
    }
    
    private function filterCondition(&$filters, $query) {
        // only new 
        if (!empty($query['only-new'])) {
            $filters[] = [
                'filterId' => 'condition',
                'filterValueId' => ['new'], 
            ];
        }
    }
    
    private function filterOfferType(&$filters, $query) {
        // only buy now 
        if (!empty($query['only-buy-now'])) {
            $filters[] = [
                'filterId' => 'offerType',
                'filterValueId' => ['buyNow'], 
            ];
        }
    }

}