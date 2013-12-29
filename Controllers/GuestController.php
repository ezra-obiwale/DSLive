<?php

namespace DSLive\Controllers;

use DScribe\Core\AController;

class GuestController extends AController {

    public function noCache() {
        return array('login', 'register');
    }

    public function indexAction() {
        
    }

    public function registerAction() {
        $form = $this->service->getRegisterForm();
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && ($this->service->register($form->getModel(), $this->view))) {
                $this->flash()->setSuccessMessage('Registration successful. You may now login');
                $this->redirect('guest', 'index', 'login');
            }
            $this->flash()->setErrorMessage('Registration failed. Please check your entries and try again');
        }
        return $this->view->variables(array(
                    'title' => 'Register',
                    'form' => $form,
                ))->file('misc', 'form');
    }

    public function loginAction($module = null, $controller = null, $action = null, $params = null) {
        $form = $this->service->getLoginForm();
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && ($model = $this->service->login($form->getModel()))) {
                $this->resetUserIdentity($model);
                if ($module !== null) {
                    $params = ($params === null) ? array() : explode(':', $params);
                    $this->redirect($module, $controller, $action, $params);
                }
                $this->redirect('in', 'dashboard', $model->getRole());
            }
            $this->flash()->setErrorMessage('Login failed. Please check your entries and try again');
        }
        return $this->view->variables(array(
                    'title' => 'Login',
                    'form' => $form,
                ))->file('misc', 'form');
    }

    public function logoutAction($module = null, $controller = null, $action = null, $params = null) {
        $this->resetUserIdentity();
        if ($module !== null) {
            $params = ($params === null) ? array() : explode(':', $params);
            $this->redirect($module, $controller, $action, $params);
        }
        $this->redirect('guest', 'index', 'login');
    }

}
