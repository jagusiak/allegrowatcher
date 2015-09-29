<?php

/**
 * Class used in allegro communcation
 * 
 * @author Seweryn Jagusiak <jagusiak@gmail.com>
 */
class AllegroHelper {
    
    /**
     * Filter constants
     */
    const ITEM_DELIMTER = ';';
    const VALUES_DELIMITER = ',';
    const NAME_DELIMITER = '=';
    const RANGE_START = '[';
    const RANGE_STOP = ']';
    
    
    /**
     * Creates allegro filters from string
     * 
     * @param string $filterString
     * @return array
     */
    public static function createFilters($filterString) {
        // filters array
        $data = [];
        
        // going through all item selemited by ;
        foreach (explode(self::ITEM_DELIMTER, $filterString) as $item) {
            static::createFilter($data, $item);
        }
        
        return $data;
    }
    
    /**
     * Creates single filter
     * 
     * @param array $filters Filter collection
     * @param string $filter
     */
    private static function createFilter(&$filters, $filter) {
        // explode string to name and its values
        list($name, $values) = explode(self::NAME_DELIMITER, $filter);

        // set flter name
        $filter = [
            'filterId' => $name,
        ];
        
        // check if filter it is range, value closed in []
        if (0 === strpos($values, self::RANGE_START) && (strlen($values) - 1 === strpos($values, self::RANGE_STOP))) {
            // strip of and divide range
            $range = explode(self::VALUES_DELIMITER, str_replace([self::RANGE_START, self::RANGE_STOP], ['', ''], $values));
            
            // check if min exists
            if (isset($range[0])) {
                $filter['filterValueRange'] = [
                    'rangeValueMin' => $range[0]
                ];
            }
            
            // set maximum
            if (isset($range[1])) {
                $filter['filterValueRange']['rangeValueMax']= $range[1];
            }
        } else {
            // normal values set (not range)
            $filter['filterValueId'] = explode(self::VALUES_DELIMITER, $values);
        }
        
        // add filter to array
        $filters[] = $filter;
    }
    
    /**
     * Creates email from data
     * 
     * @param array $data Search data
     * @return string
     */    
    public function createEmail($data) {
        $headView = file_get_contents(dirname(__FILE__). '/../../email/head.html');
        $itemView = file_get_contents(dirname(__FILE__). '/../../email/item.html');
        $footerView = file_get_contents(dirname(__FILE__). '/../../email/footer.html');
        
        return $headView . implode('', array_map(function($item) use ($itemView) {
            return str_replace(
                    ['[[URL]]', '[[NAME]]', '[[PRICE]]'], 
                    ['http://allegro.pl/listing/listing.php?string=' . urlencode($item['title']), $item['title'], sprintf('%.2f' ,$item['price'])], 
                    $itemView);
        }, $data)) . $footerView;
    }
}