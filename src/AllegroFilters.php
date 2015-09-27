<?php

class AllegroFilters {
    
    const ITEM_DELIMTER = ';';
    const VALUES_DELIMITER = ',';
    const NAME_DELIMITER = '=';
    const RANGE_START = '[';
    const RANGE_STOP = ']';
    
    public static function formatFilters($filterString) {
        $data = [];
        
        foreach (explode(self::ITEM_DELIMTER, $filterString) as $item) {
            static::formatFilter($data, $item);
        }
        
        return $data;
        
    }
    
    private static function formatFilter(&$filters, $filter) {
        list($name, $values) = explode(self::NAME_DELIMITER, $filter);

        $filter = [
            'filterId' => $name,
        ];
        
        // check if it is range
        if (0 === strpos($values, self::RANGE_START) && (strlen($values) - 1 === strpos($values, self::RANGE_STOP))) {
            $range = explode(self::VALUES_DELIMITER, str_replace([self::RANGE_START, self::RANGE_STOP], ['', ''], $values));
            
            if (isset($range[0])) {
                $filter['filterValueRange'] = [
                    'rangeValueMin' => $range[0]
                ];
            }
            
            if (isset($range[1])) {
                $filter['filterValueRange']['rangeValueMax']= $range[1];
            }
        } else {
            $filter['filterValueId'] = explode(self::VALUES_DELIMITER, $values);
        }
        
        $filters[] = $filter;
    }
}