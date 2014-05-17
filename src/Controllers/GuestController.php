<?php

namespace DSLive\Controllers;

use DScribe\Core\AController;

class GuestController extends AController {

    /**
     *
     * @var \DSLive\Services\GuestService
     */
    protected $service;
    private $setup = false;

    public function noCache() {
        return false;
    }

    public function indexAction() {
        if ($this->request->isAjax()) {
            $this->view->partial();
        }
    }

    public function registerAction() {
        $form = $this->service->getRegisterForm();
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && $this->service->register($form->getModel(), $this->setup)) {
                $this->flash()->setSuccessMessage('Registration successful. Please check your email account to confirm your registration');
                $this->redirect($this->getModule(), $this->getClassName(), 'login');
            }
            $this->flash()->setErrorMessage('Registration failed. Please check your entries and try again');
        }
        $this->view->variables(array(
            'title' => 'Register',
            'form' => $form,
        ))->file('misc', 'form');

        return $this->request->isAjax() ? $this->view->partial() :
                $this->view;
    }

    public function confirmRegistrationAction($id, $email) {
        if ($this->service->confirmRegistration($id, $email)) {
            $this->flash()->setSuccessMessage('Your registration has been confirmed. You may now log in to continue');
        }
        else {
            $this->flash()->setErrorMessage('Confirm registration failed');
        }
        $this->redirect($this->getModule(), $this->getClassName(), 'login');
    }

    public function loginAction($module = null, $controller = null, $action = null, $params = null) {
        if (!$this->userIdentity()->isGuest()) {
            if ($module !== null) {
                $params = ($params === null) ? array() : explode(':', $params);
                $this->redirect($module, $controller, $action, $params);
            }
            else
                $this->redirect('in', 'dashboard', $this->userIdentity()->getUser()->getRole());
        }

        $form = $this->service->getLoginForm();
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && ($model = $this->service->login($form->getModel()))) {
                $this->resetUserIdentity($model);
                $params = ($params === null) ? array() : explode(':', $params);
                $this->redirect($module ? $module : $this->getModule(), $controller ?
                                $controller : 'dashboard', $action ? $action : $model->getRole(), $params);
            }
            $this->flash()->setMessage('Login failed. Please check your entries and try again');
        }
        $this->view->variables(array(
            'title' => 'Login',
            'form' => $form,
        ))->file('misc', 'form');

        return $this->request->isAjax() ? $this->view->partial() :
                $this->view;
    }

    public function resetPasswordAction($id = null, $password = null) {
        $form = $this->service->getResetPasswordForm();
        if ($id === null || $password === null) {
            $form->remove('password')->remove('confirm');
        }
        else {
            if (!$this->service->getRepository()->findOneWhere(array(array('id' => $id, 'reset' => $password)))) {
                throw new \Exception('The page you\'re looking for does not exist');
            }
            $form->remove('email');
        }
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && ($this->service->resetPassword($form->getModel(), $id, $password))) {
                $this->flash()->setSuccessMessage((isset($id) && isset($password)) ?
                                'Password reset successfully. You may now login' :
                                'Password reset initiated. Please check your email address for further instructions');
                $this->redirect($this->getModule(), $this->getClassName(), 'login');
            }
            $this->flash()->setErrorMessage('Password reset failed.');
        }
        $this->view->variables(array(
            'title' => 'Reset Password',
            'form' => $form,
        ))->file('misc', 'form');

        return $this->request->isAjax() ? $this->view->partial() :
                $this->view;
    }

    public function logoutAction($module = null, $controller = null, $action = null, $params = null) {
        $this->service->doBeforeLogout();
        $this->resetUserIdentity();
        if ($module !== null) {
            $params = ($params === null) ? array() : explode(':', $params);
            $this->redirect($module, $controller, $action, $params);
        }
        $this->redirect($this->getModule(), $this->getClassName(), 'login');
    }

    public function contactUsAction() {
        $form = $this->service->getContactUsForm();
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && $this->service->contactUs($form->getData())) {
                $this->flash()->setSuccessMessage('Your message has been sent successfully. Thank you.');
            }
            else {
                $this->flash()->setErrorMessage('Send message failed.');
            }
            $this->redirect($this->getModule(), $this->getClassName(), 'contact-us');
        }
        $this->view->variables(array(
            'title' => 'Contact Us',
            'form' => $form,
        ));

        return $this->request->isAjax() ? $this->view->partial() :
                $this->view;
    }

    public function setupAction() {
        if ($this->service->getRepository()->limit(1)->select()->execute()->first()) {
            $this->redirect($this->getModule(), $this->getClassName(), 'login');
        }
        $this->setup = true;
        return $this->registerAction();
    }

    public function errorAction($code) {
        $this->layout = 'guest-2-columns';
        return $this->view->variables(array(
                    'error' => $this->service->getErrorMessage($code)
        ));
    }

    /**
     * Saves/Fetches a value to/from session. Used for ajax sessioning only
     * @param string $name
     * @param mixed $value
     */
    public function asssnAction($key, $value = null) {
        if ($this->request->isAjax()) {
            if ($value !== null)
                \Session::save($key, $value);
            else {
                die(\Session::fetch($key));
            }
        }
    }

}
