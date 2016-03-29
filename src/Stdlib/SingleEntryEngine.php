<?php

namespace dsLive\Stdlib;

use dScribe\Core\Engine;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SingleEntryEngine
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
abstract class SingleEntryEngine extends Engine {

    protected static function moduleIsActivated() {
        
    }
    
    public static function getParams() {
        return static::getUrls();
    }

    final protected static function getControllerClass($live = true) {
        $controller = static::getSingleEntryController();
        return ($live) ? new $controller() : $controller;
    }

    final public static function getAction($exception = true) {
        return static::getSingleEntryAction();
    }
    
    /**
     * @return string Fully Qualified Class Name of the class extending \dsLive\Controllers\SingleEntryController
     */
    protected abstract static function getSingleEntryController();

    /**
     * @return string Action to call from within the controller
     */
    protected abstract static function getSingleEntryAction();
}
