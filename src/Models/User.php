<?php

namespace DSLive\Models;

use DBScribe\Util,
    DScribe\Core\AUser,
    Exception;

class User extends AUser {

    /**
     * @DBS\String (size=36, primary=true)
     */
    protected $id;

    /**
     * @DBS\String (size="100")
     */
    protected $email;

    /**
     * @DBS\String (size="50")
     */
    protected $password;

    /**
     * @DBS\String (size="40", nullable=true)
     */
    protected $firstName;

    /**
     * @DBS\String (size="40", nullable=true)
     */
    protected $lastName;

    /**
     * @DBS\Timestamp
     */
    protected $registerDate;

    /**
     * @DBS\Timestamp
     */
    protected $lastLogin;

    /**
     * @DBS\String (size="15")
     */
    protected $role;

    /**
     * @DBS\String (size="300", nullable=true)
     */
    protected $picture;

    /**
     * File class
     * @var \DSLive\Stdlib\File
     */
    private $stdFile;

    /**
     * @DBS\Boolean (default=1, nullable=true)
     */
    protected $active;

    public function __construct() {
        $this->setTableName('user');
        $this->stdFile = new \DSLive\Stdlib\File;
        $this->stdFile->setExtensions('picture', array('jpg', 'jpeg', 'png', 'gif', 'bmp'));
        $this->stdFile->setMaxSize('500kb');
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function getEmail() {
        return $this->email;
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

    public function getRegisterDate() {
        return $this->registerDate;
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

    public function getPicture() {
        return $this->picture;
    }

    public function setPicture($picture) {
        $this->picture = $picture;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
        return $this;
    }

    // ------- helpers -----------

    public function getFullName($withEmail = false) {
        $fullName = $this->firstName . ' ' . $this->lastName;
        return ($withEmail) ? $fullName . ' (' . $this->email . ')' : $fullName;
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

    public function preSave() {
        if ($this->role === null)
            throw new Exception('Role not set for user');

        if ($this->id === null)
            $this->id = Util::createGUID();

        if ($this->registerDate === null)
            $this->registerDate = Util::createTimestamp();;
    }

    /**
     * updates necessary user properties at login, e.g. lastLogin
     */
    public function update() {
        $this->lastLogin = Util::createTimestamp();
    }

    public function updateFiles($files) {
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
        return $this->stdFile->unlink($this->picture);
    }
    
    public function setMaxSize($size) {
        $this->stdFile->setMaxSize($size);
        return $this;
    }
    
    public function getMaxSize() {
        return $this->stdFile->getMaxSize();
    }

}
