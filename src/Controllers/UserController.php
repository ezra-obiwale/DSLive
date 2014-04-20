<?php

namespace DSLive\Controllers;

class UserController extends SuperController {

    /**
     * @var \DSLive\Services\UserService
     */
    protected $service;

    /**
     *
     * @var array
     */
    protected $guest;

    public function init() {
        parent::init();
        $this->guest['module'] = 'guest';
        $this->guest['controller'] = 'index';
    }

    public function noCache() {
        return array_merge(parent::noCache(), array('edit-password', 'profile', 'edit-profile'));
    }

    public function accessRules() {
        return array(
            array('allow', array(
                    'role' => array('admin'),
                )),
            array('allow',
                array(
                    'role' => '@',
                    'actions' => array('profile', 'edit-profile', 'edit-password'),
                )),
            array('deny'),
        );
    }

    public function indexAction() {
        $this->order = 'firstName';
        return parent::indexAction();
    }

    public function editAction($id) {
        return parent::editAction($id, array(), array(
                    'elements' => array('email', 'password', 'confirm', 'firstName', 'lastName', 'picture')
        ));
    }

    public function profileAction() {
        return array('model' => $this->service->findOne($this->currentUser->getId()));
    }

    public function editProfileAction() {
        $model = $this->service->findOne($this->currentUser->getId());

        $this->service->setModel($model);
        $form = $this->service->getForm()
                ->remove('password')
                ->remove('confirm')
                ->remove('role')
                ->setModel($model);
        $form->get('email')->attributes->add(array('readonly' => 'readonly'));
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && $this->service->save($form->getModel(), $this->request->getFiles())) {
                $this->resetUserIdentity($this->service->getModel());
                $this->flash()->setSuccessMessage('Profile saved successfully');
                $this->redirect($this->getModule(), 'user', 'profile');
            }
            else {
                $this->flash()->setErrorMessage('Save profile failed');
            }
        }
        return $this->view->variables(array(
                    'model' => $model,
                    'form' => $form,
        ));
    }

    public function editPasswordAction() {
        $model = $this->service->findOne($this->currentUser->getId());
        $this->service->setModel($model);
        $form = $this->service->getPasswordForm();
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && $this->service->changePassword($form->getData())) {
                $this->flash()->setSuccessMessage('Password changed successfully. Please login to continue');
                $this->redirect($this->guest['module'], $this->guest['controller'], 'logout', array('in', 'user', 'profile'));
            }
            else {
                $this->flash()->setErrorMessage('Change password failed');
            }
        }
        return $this->view->variables(array(
                    'model' => $model,
                    'form' => $form,
        ));
    }

}
