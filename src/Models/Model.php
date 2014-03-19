<?php

/*
 */

namespace DSLive\Models;

use DBScribe\Util,
    DScribe\Core\AModel;

/**
 * Description of Model
 *
 * @author topman
 */
class Model extends SuperModel {

    /**
     * @DBS\String (size="36", primary=true)
     */
    protected $id;

    /**
     * Returns the id of the model
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets the id of the model
     * @param mixed $id
     * @return AModel
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Method called before inserts and updates
     * @param boolean $createId Indicates whether to create id if none exists
     */
    public function preSave($createId = true) {
        if (empty($this->id) && $createId) {
            $this->id = Util::createGUID();
        }
        parent::preSave();
    }

    /**
     * Method called after inserts and updates
     * @param mixed $operation
     * @param mixed $result
     * @param mixed $lastInsertId
     * @return mixed
     */
    public function postSave($operation, $result, $lastInsertId) {
        return $this->getId();
    }

}
