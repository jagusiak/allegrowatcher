<?php

use Jagusiak\JSONSimpleStorage;

class ResponseStorage extends JSONSimpleStorage\JSONSimpleStorage {
    
    public function getDir() {
        return dirname(__FILE__) . '/../../store';
    }
    
    
}