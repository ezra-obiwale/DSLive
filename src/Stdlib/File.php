<?php

/*
 */

namespace dsLive\Stdlib;

use dsLive\Models\File as DMF;

/**
 * Description of File
 *
 * @author topman
 */
class File extends DMF {

    public function __construct() {
    }

    public function setBadExtensions($property, array $extensions) {
        $this->setupProperty($property);
        return parent::setBadExtensions($property, $extensions);
    }

    public function setExtensions($property, array $extensions) {
        $this->setupProperty($property);
        return parent::setExtensions($property, $extensions);
    }

    public function addBadExtension($property, $ext) {
        $this->setupProperty($property);
        parent::addBadExtension($property, $ext);
    }

    public function addExtension($property, $ext) {
        $this->setupProperty($property);
        parent::addExtension($property, $ext);
    }

    private function setupProperty($property) {
        if (!$this->getProperty($property)) {
            $this->setProperty($property, NULL);
        }
    }

    public function __get($name) {
        if (!property_exists($this, $name)) {
            return $this->getProperty($name);
        }
    }

    public function _set($name, $value) {
        if (!property_exists($this, $name)) {
            $this->setProperty($name, $value);
        }
    }

    protected function _preCall(&$name, array &$args) {
        if (!method_exists($this, $name)) {
            $property = strtolower(substr($name, 3));
			if (property_exists($this, $property)) {
	            if (substr($name, 0, 3) === 'get') {
    	            return $this->getProperty($property);
        	    }
            	else if (substr($name, 0, 3) === 'set') {
                	$this->setProperty($property, $args[0]);
	                return $this;
    	        }
    	    }
        }

        return parent::_preCall($name, $args);
    }

}
