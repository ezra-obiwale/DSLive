<?php

namespace dsLive\Models;

use dbScribe\Util;

class User extends SuperUser {

    /**
     * @DBS\String (size=36, primary=true)
     */
    protected $id;

    /**
     * @DBS\String (size="200")
     */
    protected $email;

    /**
     * @DBS\Timestamp
     */
    protected $registerDate;

    /**
     * @DBS\Timestamp (nullable=true)
     */
    protected $lastLogin;

    /**
     * @DBS\String (size="20")
     */
    protected $role;

    /**
     * @DBS\Boolean (nullable=true, default=false)
     */
    protected $super;

    /**
     * @DBS\String (size="300", nullable=true)
     */
    protected $picture;

    /**
     * @DBS\Boolean (nullable=true)
     */
    protected $active;

    /**
     * Indicates whether to generate id if none exists
     * @param bool $generate
     * @return string
     */
    public function getId($generate = false) {
        if (!$this->id && $generate) $this->id = Util::createGUID();
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getPictureName() {
        return $this->getId(true);
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getRegisterDate() {
        return $this->registerDate;
    }

    public function setRegisterDate($registerDate) {
        $this->registerDate = $registerDate;
    }

    public function setLastLogin($lastLogin) {
        $this->lastLogin = $lastLogin;
    }

    public function getLastLogin() {
        return $this->lastLogin;
    }

    public function setRole($role) {
        $this->role = $role;
        return $this;
    }

    public function getRole() {
        return $this->role;
    }

    public function getSuper() {
        return $this->super;
    }

    public function setSuper($super) {
        $this->super = $super;
        return $this;
    }

    public function getPicture($returnSingleValue = false) {
        if ($returnSingleValue && is_array($this->picture)) {
            $pictures = array_values($this->picture);
            return $pictures[0];
        }
        return $this->picture;
    }

    public function setPicture($picture) {
        $this->picture = $picture;
        return $this;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
        return $this;
    }

    // ------- helpers -----------

    /**
     * Parses a string date to another string format
     * @param string|array $property If string, the property value is used. If array,
     * the values of the array elements as properties are joined together with space and
     * used.
     * @param string $format
     * @return string
     * @throws \Exception
     */
    public function parseDate($property, $format = 'F j, Y') {
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

    public function preSave() {
        if (is_array($this->picture)) {
            $pictures = array_values($this->picture);
            $this->picture = $pictures[0];
        }

        if (is_array($this->mime)) {
            $mime = array_values($this->mime['picture']);
            $this->mime = $mime[0];
        }

        if ($this->role === null) throw new \Exception('Role not set for user');

        if ($this->id === null) $this->id = Util::createGUID();

        if ($this->registerDate === null)
                $this->registerDate = Util::createTimestamp();

        parent::preSave();
    }

    /**
     * updates necessary user properties at login, e.g. lastLogin
     */
    public function update() {
        $this->lastLogin = Util::createTimestamp();
    }

    /**
     * Parses a string, injecting into it values of the user's properties.
     * @param string $string String to parse
     * @param string $prefix Prefix of each property
     * @param string $suffix Suffix of each property
     * @return string
     */
    public function parseString($string, $prefix = '{', $suffix = '}') {
        foreach ($this->toArray(true) as $property => $value) {
            $string = str_replace($prefix . $property . $suffix, $value, $string);
        }
        return $string;
    }

    public function postFetch($property = null) {
        parent::postFetch();

        $this->fullName = $this->getFullName();
    }

    public function getNameOrEmail() {
        return $this->getFirstName() ? $this->getFirstName() : $this->getEmail();
    }

}
