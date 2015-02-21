<?php

namespace DSLive\Models;

use DScribe\Core\AUser;

abstract class SuperUser extends AUser {

    /**
     * @DBS\String (size="40", nullable=true)
     */
    protected $firstName;

    /**
     * @DBS\String (size="40", nullable=true)
     */
    protected $lastName;

    /**
     * @DBS\String (size="256")
     */
    protected $password;

    /**
     * @DBS\String (size="6", nullable=true)
     */
    protected $salt;

    /**
     * @DBS\int (size=1)
     */
    protected $mode;

    /**
     * File class
     * @var File
     */
    private $stdFile;

    /**
     * @DBS\String (nullable=true)
     */
    protected $mime;

    /**
     * @DBS\String (size=36, nullable=true)
     */
    protected $reset;

    public function __construct() {
        $this->setTableName('user');
        $this->stdFile = new \DSLive\Stdlib\File;
        $this->stdFile->setExtensions('picture', array('jpg', 'jpeg', 'png', 'gif'))
                ->setMaxSize('500kb')
                ->withThumbnails('picture', 50)
                ->setOverwrite(true);
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }

    public function getSalt() {
        return $this->salt;
    }

    public function getMode() {
        return $this->mode;
    }

    public function setSalt($salt) {
        $this->salt = $salt;
        return $this;
    }

    public function setMode($mode) {
        $this->mode = $mode;
        return $this;
    }

    public function setFirstName($firstName) {
        $this->firstName = $firstName;
        return $this;
    }

    public function getFirstName() {
        return $this->firstName;
    }

    public function setLastName($lastName) {
        $this->lastName = $lastName;
        return $this;
    }

    public function getLastName() {
        return $this->lastName;
    }

    abstract public function setPicture($picture);

    public function setMime($mime) {
        $this->mime = $mime;
        return $this;
    }

    public function getMime() {
        return $this->mime;
    }

    public function getReset() {
        return $this->reset;
    }

    public function setReset($reset) {
        $this->reset = $reset;
        return $this;
    }

    // ------- helpers -----------

    public function getFullName($withEmail = false) {
        $fullName = $this->firstName . ' ' . $this->lastName;
        return ($withEmail) ? $fullName . ' (' . @$this->email . ')' : $fullName;
    }

    /**
     * Hashes the password
     * @param mixed $password Password to hash.
     * @return m
     */
    public function hashPassword($password = null) {
        if (function_exists('password_hash')) {
            if (!$password) {
                $this->mode = 1;
                $this->password = password_hash($this->password, PASSWORD_DEFAULT);
                return $this;
            }

            return password_hash($password, PASSWORD_DEFAULT);
        }
        else if (defined("CRYPT_BLOWFISH") && CRYPT_BLOWFISH) {
            $salt = '$2y$11$' . substr(md5(uniqid(rand(), true)), 0, 22);

            if (!$password) {
                $this->mode = 2;
                $this->password = crypt($this->password, $salt);
                return $this;
            }
            return crypt($password, $salt);
        }
        else {
            $intermediateSalt = md5(uniqid(rand(), true));
            $salt = substr($intermediateSalt, 0, 6);
            if (!$password) {
                $this->mode = 3;
                $this->password = hash("sha256", $password . $salt);
                $this->salt = $salt;
                return $this;
            }
            return hash("sha256", $password . $salt);
        }
    }

    public function verifyPassword($password) {
        switch ($this->mode) {
            case 1:
                $valid = password_verify($password, $this->password);
                if ($valid && password_needs_rehash($this->password, PASSWORD_DEFAULT)) {
                    $this->password = password_hash($password, PASSWORD_DEFAULT);
                }
                return $valid;
            case 2:
                return crypt($password, $this->password) == $this->password;
            case 3:
                return (hash("sha256", $password . $this->salt) === $this->password);
        }
    }

    public function uploadFiles($files) {
        if (!$this->stdFile)
            $this->__construct();

        if ($this->stdFile->uploadFiles($files)) {
            foreach ($this->stdFile->getProperties(false) as $property => $value) {
                $this->$property = $value;
            }
            $this->mime = $this->stdFile->getMime();
            return $this;
        }
    }

    public function unlink() {
        if (!$this->stdFile)
            $this->__construct();

        return $this->stdFile->unlink($this->picture);
    }

    public function setMaxSize($size) {
        $this->stdFile->setMaxSize($size);
        return $this;
    }

    public function getMaxSize() {
        return $this->stdFile->getMaxSize();
    }

    protected function getStdFile() {
        return $this->stdFile;
    }

    /**
     * @return array
     */
    public function getErrors() {
        return $this->stdFile->getErrors();
    }

    public function getThumbnails($property) {
        if (!$this->stdFile)
            $this->__construct();
        $this->stdFile->$property = $this->$property;
        return $this->stdFile->getThumbnails($property, 0);
    }

    public function parseBoolean($property, $on = 'Yes', $off = 'No') {
        return ($this->$property == 1) ? $on : $off;
    }

    public function prepareEmail($label = null, $class = null) {
        $label = $label ? $label : $this->email;
        return '<a title="send email" class="' . $class . '" href="mailto:' . $this->email . '">' . $label . '</a>';
    }

}
