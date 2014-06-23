<?php

/*
 */

namespace DSLive\Stdlib;

use DSLive\Models\File as DMF;

/**
 * Description of File
 *
 * @author topman
 */
class File extends DMF {

    private $properties;

    public function __construct() {
        $this->properties = array();
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
        if (!array_key_exists($property, $this->properties)) {
            $this->properties[$property] = null;
        }
    }

    public function __get($name) {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }
    }

    public function _set($name, $value) {
        if (array_key_exists($name, $this->properties)) {
            $this->properties[$name] = $value;
            return $this;
        }
    }

    public function _call(&$name, $args) {
        if (!method_exists($this, $name)) {
            $property = strtolower(substr($name, 3));
            if (array_key_exists($property, $this->properties)) {
                if (substr($name, 0, 3) === 'get') {
                    return $this->properties[$property];
                }
                else if (substr($name, 0, 3) === 'set') {
                    $this->properties[$property] = $arguments[0];
                    return $this;
                }
            }
        }

        return parent::_call($name, $args);
    }

    public function getProperties($preSave = true) {
        if ($preSave)
            $this->preSave();
        return $this->properties;
    }

}
