<?php

namespace DSLive\Models;

use DBScribe\Util,
    DScribe\Core\AModel,
    Exception;

/**
 * Description of Model
 *
 * @author topman
 */
class Settings extends Model {

    /**
     * @DBS\String (size=20, unique=true)
     */
    protected $key;

    /**
     * @DBS\String
     */
    protected $value;

    public function getKey() {
        return $this->key;
    }

    public function getValue() {
        return $this->value;
    }

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

}
