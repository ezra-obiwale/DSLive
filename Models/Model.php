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
class Model extends AModel {

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
     * @return \DScribe\Core\AModel
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Method called before inserts and updates
     */
    public function preSave() {
        if (empty($this->id)) {
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

    /**
     * Parses a string date to another string format
     * @param string|array $property If string, the property value is used. If array,
     * the values of the array elements as properties are joined together with space and
     * used.
     * @param string $property
     * @return string
     * @throws \Exception
     */
    public function parseDate($property, $format = 'F j, Y H:i:s') {
        if (is_array($property)) {
            $dateString = '';
            foreach ($property as $ppt) {
                if (!property_exists($this, $ppt))
                    throw new \Exception('Parse Date Error: Property "' . $ppt .
                    '" does not exist in class "' . get_called_class() . '"');
                $dateString .= ' ' . $this->$ppt;
            }
        }
        else {
            if (!property_exists($this, $property))
                throw new \Exception('Parse Date Error: Property "' . $property .
                '" does not exist in class "' . get_called_class() . '"');
            $dateString = $this->$property;
        }

        return Util::createTimestamp($dateString, $format);
    }

}
