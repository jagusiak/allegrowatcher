<?php

class RunQuery {
    
    private $query;
    private $emails;
    private $responses;
    private $queryId;
    
    public function __construct($id) {
        $queryStorage = QueryStorage::getInstance();
        
        $this->query = $queryStorage->getById($id);
        
        if (empty($this->query)) {
            throw new Exception("Query $id does not exist.");
        }
        
        $this->emails = EmailStorage::getInstance()->getAllWhichHasOne($id, $queryStorage);
        $this->responses = ResponseStorage::getInstance()->getAllWhichHasOne($id, $queryStorage);
        
        $this->queryId = $id;
    }
    
    public function getFilters(AllegroApi $api, $query, $filters = null) {
        // process
        return array_map(function ($filter) {
            return [
                'name' => $filter->filterId,
                'description' => $filter->filterName,
                'range' => $filter->filterIsRange,
                'values' => isset($filter->filterValues) ?
                array_map(function ($item) {
                    return [
                        'value' => $item->filterValueId,
                        'name' => $item->filterValueName,
                    ];
                }, (array)$filter->filterValues->item) : []
            ];
        }, $api->getFilters($query, $filters));
        
    }
    
    public function execute(AllegroApi $api) {
        $result = $api->searchItems($this->query);
        
        $response = ResponseStorage::getInstance();
        
        $allegroIds = array_map(function($item) { return $item['allegroId'];}, $this->responses);
        
        $filteredData = [];
        
        foreach ($result as $allegroId => $content) {
            if (!in_array($allegroId, $allegroIds)) {
                $filteredData[$allegroId] = $content;
                $rid = $response->set(['allegroId' => $allegroId]);
                $response->hasOne($rid, QueryStorage::getInstance(), $this->queryId);
            }
        }
        
        if (empty($filteredData)) {
            return 0;
        }
        
        $response->save();
        
        $mail = $this->createEmail($filteredData);
        
        foreach ($this->emails as $emailData) {
            mail(
                    $emailData['email'], 
                    QueryStorage::formatQuery($this->query), 
                    str_replace('[[MESSAGE]]', isset($emailData['message']) ? $emailData['message'] : '', $mail), 
                    "Content-Type: text/html; charset=ISO-8859-1\r\n"
            );
        }
        
        return count($filteredData);
    }
    
    private function createEmail($data) {
        $headView = file_get_contents(dirname(__FILE__). '/../email/head.html');
        $itemView = file_get_contents(dirname(__FILE__). '/../email/item.html');
        $footerView = file_get_contents(dirname(__FILE__). '/../email/footer.html');
        
        return $headView . implode('', array_map(function($item) use ($itemView) {
            return str_replace(
                    ['[[URL]]', '[[NAME]]', '[[PRICE]]'], 
                    ['http://allegro.pl/listing/listing.php?string=' . urlencode($item['title']), $item['title'], sprintf('%.2f' ,$item['price'])], 
                    $itemView);
        }, $data)) . $footerView;
    }
    
    
}