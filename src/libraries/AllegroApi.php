<?php

/**
 * Connects with allegro and sends requests
 * 
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
     * 
     * @param string $webApiKey Key configuration
     */
    public function __construct($webApiKey, $countryCode) {
        // store key
        $this->webApiKey = $webApiKey;
        $this->countryCode = $countryCode;
    }

    /**
     * Gets country codes
     * 
     * @return array
     */
    public function getCountryCodes() {
        $this->initClient();
        return $this->client->doGetCountries($this->countryCode, $this->webApiKey);
    }

    /**
     * Gets all allegro categories
     * 
     * @return array
     */
    public function getCategories() {
        $this->initClient();
        return $this->client->doGetCatsData($this->countryCode, 0, $this->webApiKey)['cats-list'];
    }

    /**
     * Perform search
     * 
     * @param string $query
     * @param array $filters
     * @return array
     */
    public function getFilters($query, $filters = null) {
        $this->initClient(true);
        
        // call api
        $data = $this->client->doGetItemsList([
            'webapiKey' => $this->webApiKey,
            'countryId' => $this->countryCode,
            'resultScope' => 6,
            'filterOptions' => $this->initFilters($query, $filters),
        ]);
        
        return $data->filtersList->item;
    }

    public function searchItems($query, $filters = null) {
        $this->initClient(true);

        // initial params (first query)
        $params = [
            'webapiKey' => $this->webApiKey,
            'countryId' => $this->countryCode,
            'filterOptions' => $this->initFilters($query, $filters),
            'sortOptions' => [
                'sortType' => 'price',
                'sortOrder' => 'asc'
            ],
            'resultSize' => 1000,
            'resultOffset' => 0,
            'resultScope' => 3
        ];

        // returned data
        $data = [];

        // current offsert
        $offset = 0;

        do {
            // set offset
            $params['resultOffset'] = $offset;

            // call api
            $response = $this->client->doGetItemsList($params);

            // count size
            $size = $response->itemsCount;

            // get item list
            if (!empty($response->itemsList)) {
                foreach ($response->itemsList->item as $item) {
                    $data[$item->itemId] = [
                        'title' => $item->itemTitle,
                        'price' => $item->priceInfo->item[0]->priceValue,
                    ];
                }
            }

            // next offset
            $offset += self::QUERY_SIZE;
        } while ($offset < $size);

        return $data;
    }

    /**
     * Init api
     * 
     * @param bool $newVersion Determines if new api is used
     */
    private function initClient($newVersion = false) {
        // intitialize and store client;
        $this->client = new SoapClient($newVersion ? static::URL_NEW : static::URL, [
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        ]);
    }

    /**
     * Initializes filters
     * 
     * @param string $query Query to search
     * @param string $filters
     * @return array
     */
    private function initFilters($query, $filters = null) {
        // intialize filters
        if (empty($filters)) {
            $filters = [];
        } else {
            $filters = AllegroHelper::createFilters($filters);
        }
        // query search
        $filters[] = [
            'filterId' => 'search',
            'filterValueId' => [$query],
        ];
        
        return $filters;
    }

}