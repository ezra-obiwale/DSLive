<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DSLive\Models;

/**
 * Description of Subscription
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
class Notification extends Model {

    /**
     * @DBS\String (size=30, unique=true)
     */
    protected $name;

    /**
     * @DBS\String (size=5)
     */
    protected $type;

    /**
     * @DBS\String (size=100, nullable=true)
     */
    protected $messageTitle;

    /**
     * @DBS\String
     */
    protected $message;

    /**
     * @DBS\Boolean (nullable=true, default=false)
     */
    protected $required;

    /**
     * @DBS\Boolean (nullable=true, default=false)
     */
    protected $getReplies;

    public function getName() {
        return $this->name;
    }

    public function getType() {
        return $this->type;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    public function getMessageTitle() {
        return $this->messageTitle;
    }

    public function setMessageTitle($messageTitle) {
        $this->messageTitle = $messageTitle;
        return $this;
    }

    public function setMessage($message) {
        $this->message = $message;
        return $this;
    }

    public function getRequired() {
        return $this->required;
    }

    public function setRequired($required) {
        $this->required = $required;
        return $this;
    }

    public function getGetReplies() {
        return $this->getReplies;
    }

    public function setGetReplies($getReplies) {
        $this->getReplies = $getReplies;
        return $this;
    }

}
