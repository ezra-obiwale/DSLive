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
     * @DBS\String (size="50")
     */
    protected $password;

    /**
     * File class
     * @var File
     */
    private $stdFile;

    public function __construct() {
        $this->setTableName('user');
        $this->stdFile = new \DSLive\Stdlib\File;
        $this->stdFile->setExtensions('picture', array('jpg', 'jpeg', 'png', 'gif', 'bmp'));
        $this->stdFile->setMaxSize('500kb');
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
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
        $hashed = ($password) ? md5(md5($this->email) . $password) : md5(md5($this->email) . $this->password);
        if (!$password) {
            $this->password = $hashed;
            return $this;
        }

        return $hashed;
    }

    public function uploadFiles($files) {
        if (!$this->stdFile)
            $this->__construct();

        if ($path = $this->stdFile->uploadFiles($files)) {
            if (!is_bool($path))
                $this->setPicture($path);
            return true;
        }

        return false;
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

}
