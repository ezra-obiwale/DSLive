<?php

namespace DSLive\Models;

use DBScribe\Util;

class User extends SuperUser {

    /**
     * @DBS\String (size=36, primary=true)
     */
    protected $id;

    /**
     * @DBS\String (size="100")
     */
    protected $email;

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
     * @DBS\Boolean (default=1, nullable=true)
     */
    protected $active;

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
            $this->registerDate = Util::createTimestamp();
    }

    /**
     * updates necessary user properties at login, e.g. lastLogin
     */
    public function update() {
        $this->lastLogin = Util::createTimestamp();
    }

}
