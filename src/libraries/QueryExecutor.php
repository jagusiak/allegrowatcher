<?php

/**
 * Executes queries
 * 
 * @author Seweryn Jagusik <jagusiak@gmail.com>
 */
class QueryExecutor {
    
    private $query;
    private $emails;
    private $responses;
    private $queryId;
    
    /**
     * Create new query exector
     * 
     * @param mixed $id Qeery id
     * @throws Exception
     */
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
    
    /**
     * Return all possible filters for query
     * 
     * @param AllegroApi $api
     * @param string $query
     * @param string $filters
     * @return array
     */
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
    
    /**
     * Executes query
     * 
     * @param AllegroApi $api
     * @return int
     */
    public function execute(AllegroApi $api) {
        $result = $api->searchItems($this->query['query'], isset($this->query['filters']) ? $this->query['filters'] : null);
        
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
        
        $mail = AllegroHelper::createEmail($filteredData);
        
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
    
    
}