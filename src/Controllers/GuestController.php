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
    protected $loginAction = 'login';

    public function noCache() {
        return false;
    }

    public function indexAction() {
        if ($this->request->isAjax()) {
            $this->view->partial();
        }
    }

    public function registerAction() {
        return $this->register();
    }

    private function register() {
        $form = $this->service->getRegisterForm();
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && $this->service->register($this->view, array(
                        'module' => $this->module(),
                        'controller' => $this->getClassName(),
                            ), $form, $this->setup)) {
                $this->flash()->setSuccessMessage('Registration successful. Please check your email account to confirm your registration');
                $this->redirect($this->getModule(), $this->getClassName(), $this->loginAction);
            }
            $this->flash()
                    ->setErrorMessage('Registration failed. Please check your entries and try again')
                    ->addErrorMessage($this->service->getErrors());
            $form->setData(array('confirm' => ''));
        }
        $this->view->variables(array(
            'title' => 'Register',
            'form' => $form,
        ))->file('misc', 'form');

        return $this->request->isAjax() ? $this->view->partial() :
                $this->view;
    }

    public function resendConfirmationAction($id) {
        $model = $this->service->getRepository()->findOneWhere(array(array(
                'id' => $id,
                'active' => 0
        )));
        if (!$model) {
            throw new \Exception('Invalid action');
        }

        if ($this->service->sendEmail($model)) {
            $this->flash()->setSuccessMessage('Confirmation email sent successfully');
        }
        else {
            $this->flash()->setSuccessMessage('Failed to send confirmation email. Please refresh this page to retry sending.');
        }
    }

    public function confirmRegistrationAction($id, $email) {
        if ($this->service->confirmRegistration($id, $email)) {
            $this->flash()->setSuccessMessage('Your registration has been confirmed. You may now log in to continue');
        }
        else {
            $this->flash()->setErrorMessage('Confirm registration failed');
        }
        $this->redirect($this->getModule(), $this->getClassName(), $this->loginAction);
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
            $this->flash()->setErrorMessage('Login failed. Please check your entries and try again')
                    ->addErrorMessage($this->service->getErrors());
        }
        $this->view->variables(array(
            'title' => 'Login',
            'form' => $form,
        ))->file('misc', 'form');

        return $this->request->isAjax() ? $this->view->partial() :
                $this->view;
    }

    public function resetPasswordAction($id = null, $resetId = null) {
        $form = $this->service->getResetPasswordForm();
        if ($id === null || $resetId === null) { // remove password and confirm fields just get email to send notification to
            $form->remove('password')->remove('confirm');
        }
        else {
            if (!$this->service->getRepository()->findOneWhere(array(array('id' => $id, 'reset' => $resetId)))) {
                throw new \Exception('The page you\'re looking for does not exist');
            }
            $form->remove('email');
        }
        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());
            if ($form->isValid() && $this->service->resetPassword($form->getModel(), $id, $resetId)) {
                $this->flash()->setSuccessMessage((isset($id) && isset($resetId)) ?
                                'Password reset successfully. You may now login' :
                                'Password reset initiated. Please check your email account for further instructions');
                $this->redirect($this->getModule(), $this->getClassName(), 'login');
            }
            $this->flash()->setErrorMessage('Password reset failed.')
                    ->addErrorMessage($this->service->getErrors());
        }
        $this->view->variables(array(
            'title' => 'Reset Password',
            'form' => $form,
        ))->file('misc', 'form');

        return $this->request->isAjax() ? $this->view->partial() :
                $this->view;
    }

    public function logoutAction($module = null, $controller = null, $action = null, $params = null) {
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
                $form->setData(array(
                    'title' => '',
                    'message' => ''
                ));
            }
            else {
                $this->flash()->setErrorMessage('Send message failed.')
                        ->addErrorMessage($this->service->getErrors());
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

    /**
     * Name of action that overrides method loginAction
     * @param string $loginAction
     * @return \DScribe\View\View
     */
    public function setupAction() {
        if ($this->service->getRepository()->limit(1)->select()->execute()->first()) {
            $this->redirect($this->getModule(), $this->getClassName(), $this->loginAction);
        }
        $this->setup = true;
        $return = $this->registerAction();
        $return->getVariables('form')->get('submit')->options->value = 'Done';
        return $return->variables(array('title' => 'Setup'));
    }

}
