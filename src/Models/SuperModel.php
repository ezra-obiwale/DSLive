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
class SuperModel extends AModel {

    /**
     * Parses a string date to another string format
     * @param string|array $property If string, the property value is used. If array,
     * the values of the array elements as properties are joined together with space and
     * used.
     * @param string $format
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

        if (stristr($dateString, '0000'))
            return '';
        return Util::createTimestamp($dateString, $format);
    }

    public function parseBoolean($property, $on = 'Yes', $off = 'No') {
        return ($this->$property == 1) ? $on : $off;
    }

    public function getArray($property) {
        if (!$this->$property) {
            return array();
        }
        elseif (!is_array($this->$property)) {
            return array($this->$property);
        }
        return $this->$property;
    }
    
    public function postFetch($property = null) {
        parent::postFetch($property);
    }

}
