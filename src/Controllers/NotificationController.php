<?php

namespace dsLive\Controllers;

class NotificationController extends SuperController {

    public function noCache() {
        return true;
    }

    public function accessRules() {
        return array(
            array('allow', array(
                    'role' => 'admin'
                )),
            array('deny'),
        );
    }
    
    public function indexAction() {
        $this->order = 'name';
        return parent::indexAction();
    }

    public function editAction($id) {
        $model = $this->service->findOne($id);
        $form = parent::editAction($model)->getVariables('form');
        if ($model->getRequired()) {
            $form->get('name')->attributes->readonly = 'readonly';
            $form->remove('type');
        }
    }

}
