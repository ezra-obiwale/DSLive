<?php

namespace DSLive\Controllers;

class UserController extends SuperController {

    /**
     * @var \DSLive\Services\UserService
     */
    protected $service;

    public function accessRules() {
        return array(
            array('allow', array(
                    'role' => array('admin'),
                )),
            array('allow',
                array(
                    'role' => '@',
                    'actions' => array('profile', 'edit-profile', 'edit-password', 'preferences', 'set-access-code'),
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
        return array('model' => $this->currentUser);
    }

    public function editProfileAction() {
        $model = $this->currentUser;
        $this->service->setModel($model);
        $form = $this->service->getForm()
                ->remove('password')
                ->remove('confirm')
                ->remove('role')
                ->setModel($model);
        $form->get('email')->attributes->add(array('readonly' => 'readonly'));
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost(), true);
            if ($form->isValid() && $this->service->save($form->getModel(), $this->request->getFiles())) {
                $this->resetUserIdentity($this->service->getModel());
                $this->flash()->setSuccessMessage('Profile saved successfully');
                $this->redirect('in', 'user', 'profile');
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
        $model = $this->currentUser;
        $this->service->setModel($model);
        $form = $this->service->getPasswordForm();
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && $this->service->changePassword($form->getData())) {
                $this->flash()->setSuccessMessage('Password changed successfully. Please login to continue');
                $this->redirect('guest', 'index', 'logout', array('in', 'user', 'profile'));
            }
            else {
                $this->flash()->setErrorMessage('Change password failed');
            }
        }
        return array(
            'model' => $model,
            'form' => $form,
        );
    }

}