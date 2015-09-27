<?php

use Jagusiak\JSONSimpleStorage;

class EmailStorage extends JSONSimpleStorage\JSONSimpleStorage {
    
    public static function createEmail($email, $message) {
        return [
            'email' => $email,
            'message' => $message,
        ];
    }
    
    public static function formatEmail($data) {
        return $data['email'] . (empty($data['message']) ? '' : (' "' . $data['message'] . '"'));
    }
    
    public function getDir() {
        return dirname(__FILE__) . '/../store';
    }
    
}