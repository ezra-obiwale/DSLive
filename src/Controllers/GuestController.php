<?php

namespace dsLive\Controllers;

use dScribe\Core\AController;

class GuestController extends AController {

	/**
	 *
	 * @var \dsLive\Services\GuestService
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
		$this->register();
		return $this->view->file('d-scribe/ds-live/src/View/views/misc/form', true);
	}

	public function signUpAction() {
		return $this->registerAction();
	}

	protected function register() {
		$form = $this->service->getRegisterForm();
		if ($this->request->isPost()) {
			$form->setData($this->request->getPost());
			if ($form->isValid() && $this->service->register($this->view, array(
						'module' => $this->getModule(),
						'controller' => $this->getClassName(),
							), $form, $this->setup)) {
				$this->flash()->setSuccessMessage('Registration successful. Please check your email account to confirm your registration');
				$this->redirect($this->getModule(), $this->getClassName(), $this->loginAction);
			}
			if ($this->service->getErrors())
					$this->flash()
						->setErrorMessage('Registration failed. Please check your entries and try again');
			$this->flash()->addErrorMessage($this->service->getErrors());
			$form->setData(array('confirm' => ''));
		}
		$mediaForm = new \dsLive\Forms\MediaSignUpForm();
		$mediaForm->setAttribute('action', $this->view->url($this->getModule(), $this->getClassName(), 'media-signup', array(
					$module, $controller, $action, $parrams)));
		$this->view->variables(array(
			'title' => 'Sign Up',
			'form' => $form,
			'mediaForm' => $mediaForm,
		))->file('misc', 'form');

		return $this->request->isAjax() ? $this->view->partial() :
				$this->view;
	}

	public function resendConfirmationAction($id) {
		$model = $this->service->getRepository()->findOneWhere(array(array(
				'id' => $id,
		)));
		if (!$model) {
			throw new \Exception('Invalid action');
		}

		if ($model->getActive()) {
			$this->flash()->setMessage('This account has already been confirmed')
					->addMessage('Please ' . str_replace('-', ' ', \Util::camelToHyphen($this->loginAction)));
		}
		else if ($this->service->sendEmail($model)) {
			$this->flash()->setSuccessMessage('Confirmation email sent successfully');
		}
		else {
			$this->flash()->setErrorMessage('Failed to send confirmation email')
					->addErrorMessage('Please refresh this page to retry sending');
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
				$this->redirect($module, $controller, $action ? $action : $this->userIdentity()->getUser()->getRole(), $params);
			}
			else $this->redirect('in', 'dashboard', $this->userIdentity()->getUser()->getRole());
		}

		$form = $this->service->getLoginForm($this->view->url($this->getModule(), $this->getClassName(), 'reset-password'));
		if ($this->request->isPost()) {
			$form->setData($this->request->getPost());
			if ($form->isValid() && $model = $this->service->login($form->getModel())) {
				$this->loginSuccess($model, $module, $controller, $action, $params);
			}
			$this->flash()->setErrorMessage('Login failed. Please check your entries and try again')
					->addErrorMessage($this->service->getErrors());
		}
		$mediaForm = new \dsLive\Forms\MediaLoginForm();
		$mediaForm->setAttribute('action', $this->view->url($this->getModule(), $this->getClassName(), 'media-login', array(
					$module, $controller, $action, $params)));
		$this->view->variables(array(
			'title' => 'Login',
			'form' => $form,
			'mediaForm' => $mediaForm,
		))->file('d-scribe/ds-live/src/View/views/misc/form', true);

		return $this->request->isAjax() ? $this->view->partial() :
				$this->view;
	}

	protected function loginSuccess(\dsLive\Models\User $model, $module = null, $controller = null,
								 $action = null, $params = null) {
		$this->resetUserIdentity($model);
		$redirect = \Session::fetch('redirect');
		$module = ($redirect['module']) ? $redirect['module'] : $module;
		$controller = ($redirect['controller']) ? $redirect['controller'] : $controller;
		$action = ($redirect['action']) ? $redirect['action'] : $action;
		$params = ($redirect['params']) ? $redirect['params'] : explode(':', $params);

		$this->redirect($module ? $module : $this->getModule(), $controller ?
						$controller : 'dashboard', $action ? $action : $model->getRole(), $params, null, true);
	}

	public function mediaSignupAction() {
		if ($this->request->isPost()) {
			$form = new \dsLive\Forms\MediaSignUpForm();
			$form->setData($this->request->getPost());
			if ($form->isValid() && $this->service->mediaSignup($form->getData(), $this->view, array(
						'module' => $this->module(),
						'controller' => $this->getClassName(),
					))) {
				$this->flash()->setSuccessMessage('Registration successful. Please check your email account to confirm your registration');
				$this->redirect($this->getModule(), $this->getClassName(), $this->loginAction);
			}
			$this->flash()->setErrorMessage('Registration failed.')->addErrorMessage($this->service->getErrors());
		}
		$this->redirect($this->getModule(), $this->getClassName(), 'sign-up');
	}

	public function mediaLoginAction($module = null, $controller = null, $action = null, $params = null) {
		if ($this->request->isPost()) {
			$form = new \dsLive\Forms\MediaLoginForm();
			$form->setData($this->request->getPost());
			if ($form->isValid() && $model = $this->service->mediaLogin($form->getData())) {
				$this->loginSuccess($model, $module, $controller, $action, $params);
			}
			$this->flash()->setErrorMessage('Login Failed')->addErrorMessage($this->service->getErrors());
		}
		$this->redirect($this->getModule(), $this->getClassName(), 'login', array($module, $controller, $action,
			$params));
	}

	public function resetPasswordAction($id = null, $resetId = null) {
		$form = $this->service->getResetPasswordForm();
		if ($id === null || $resetId === null) { // remove password and confirm fields just get email to send notification to
			$form->remove('password')->remove('confirm');
		}
		else {
			if (!$this->service->getRepository()->findOneWhere(array(array('id' => $id,
							'reset' => $resetId)))) {
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
				$this->redirect($this->getModule(), $this->getClassName(), $this->loginAction);
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
		$this->redirect($this->getModule(), $this->getClassName(), $this->loginAction);
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
	 * @return \dScribe\View\View
	 */
	public function setupAction() {
		if ($this->service->getRepository()->findOneBy('role', 'admin')) {
			$this->redirect($this->getModule(), $this->getClassName(), $this->loginAction);
		}
		$this->setup = true;
		$return = $this->registerAction();
		$return->getVariables('form')->get('submit')->options->value = 'Done';
		return $return->variables(array('title' => 'Setup'));
	}

}
