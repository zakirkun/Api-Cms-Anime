<?php
namespace App\Helpers\V1\Adfly;

use Carbon\Carbon;
use Illuminate\Support\Str;

class Http_Multiple_Error {
    private $_status = null;
    private $_type = null;
    private $_url = null;
    private $_params = null;
    
    function __construct($status, $type, $url, $params) {
        $this->_status = $status;
        $this->_type = $type;
        $this->_url = $url;
        $this->_params = $params;
    }
    
    function getStatus() {
        return $this->_status;
    }
    
    function getType() {
        return $this->_type;
    }
    
    function getUrl() {
        return $this->_url;
    }
    
    function getParams() {
        return $this->_params;
    }
}