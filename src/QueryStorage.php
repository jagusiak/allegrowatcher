<?php

use Jagusiak\JSONSimpleStorage;

class QueryStorage extends JSONSimpleStorage\JSONSimpleStorage {
   
    /**
     * Creates data query
     * 
     * @param string $query
     * @param mixed $onlyNew
     * @param mixed $onlyBuyNow
     * @param mixed $minPrice
     * @param mixed $maxPrice
     * @return array
     */
    public static function createQuery($query, $onlyNew = null, $onlyBuyNow = null, $minPrice = null, $maxPrice = null) {
        return array_filter([
            'query' => $query,
            'only-new' => !empty($onlyNew),
            'only-buy-now' => !empty($onlyBuyNow),
            'min-price' => $minPrice,
            'max-price' => $maxPrice,
        ]);
    }
    
    /**
     * Formats data query
     * 
     * @param array $data
     * @return string
     */
    public static function formatQuery(array $data) {
        // query first
        $description = $data['query'];
        // don't repeat query
        unset($data['query']);
        
        // print rest of data
        foreach ($data as $key => $value) {
            $description .= ' [' . strtoupper(str_replace('-', ' ', $key)) . (true === $value ? '' : ": $value") . ']';
        }
        
        return $description;
    }
    
    public function getDir() {
        return dirname(__FILE__) . '/../store';
    }
}