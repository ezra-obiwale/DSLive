<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace dsLive\Controllers;

/**
 * Description of SingleEntryController
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
abstract class SingleEntryController extends SuperController {

    public function noCache() {
        return true;
    }

    public function accessRules() {
        return array(
            array('allow'),
        );
    }

}
