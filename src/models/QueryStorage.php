<?php

use Jagusiak\JSONSimpleStorage;

class QueryStorage extends JSONSimpleStorage\JSONSimpleStorage {

    public static function createQuery($query, $filters = null) {
        return array_filter([
            'query' => $query,
            'filters' => empty($filters) ? null : $filters,
        ]);
    }

    public static function formatQuery(array $data) {
        return $data['query'] . (empty($data['filters']) ? '' : " {$data['filters']}");
    }
    
    public function getDir() {
        return dirname(__FILE__) . '/../../store';
    }
}