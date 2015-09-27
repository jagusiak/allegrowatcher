<?php

use Jagusiak\JSONSimpleStorage;

class SettingsStorage extends JSONSimpleStorage\JSONSimpleStorage {
    
    public function getDir() {
        return dirname(__FILE__) . '/../store';
    }
    
}