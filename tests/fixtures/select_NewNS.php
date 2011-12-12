<?php

namespace App;

class Builder extends \Select {
    const DEFAULT_NAMESPACE = 'App\\';
    
    static protected $_namespace = self::DEFAULT_NAMESPACE;
}

class Numbers {
    
    public $series;
    
    public $domain;
    
    public function __construct() {
        $this->series = func_get_args();
    }
    
    public function reset() {
        $this->domain = '';
        $this->series = array();
    }
    
    public function setDomain($domain) {
        $this->domain = $domain;
    }
}