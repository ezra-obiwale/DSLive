<?php

namespace DSLive\Controllers;

use DScribe\Core\AController;

class GuestController extends AController {

    /**
     *
     * @var \DSLive\Services\GuestService
     */
    protected $service;

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

    public function confirmRegistrationAction($id, $email) {
        return $this->view->variables(array(
                    'user' => $this->service->confirmRegistration($id, $email)
        ));
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

    public function resetPasswordAction($id = null, $password = null) {
        $form = $this->service->getForgotPasswordForm();
        if ($id === null || $password === null) {
            $form->remove('password')->remove('confirm');
        }
        else {
            if (!$this->service->getRepository()->findOneWhere(array(array('id' => $id, 'password' => $password)))) {
                throw new \Exception('The page you\'re looking for does not exist');
            }
            $form->remove('email');
        }
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && ($this->service->resetPassword($form->getModel(), $id, $password))) {
                $this->flash()->setSuccessMessage((isset($id) && isset($password)) ?
                                'Password reset successfully. You may now login' :
                                'Check your email address for further instructions');
                $this->redirect('guest', 'index', 'login');
            }
            $this->flash()->setErrorMessage('Password reset failed.');
        }
        return $this->view->variables(array(
                    'title' => 'Reset Password',
                    'form' => $form,
                ))->file('misc', 'form');
    }

    public function logoutAction($module = null, $controller = null, $action = null, $params = null) {
        $this->service->doBeforeLogout();
        $this->resetUserIdentity();
        if ($module !== null) {
            $params = ($params === null) ? array() : explode(':', $params);
            $this->redirect($module, $controller, $action, $params);
        }
        $this->redirect('guest', 'index', 'login');
    }

}
