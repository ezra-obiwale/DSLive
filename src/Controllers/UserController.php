<?php

namespace dsLive\Controllers;

use \dsLive\Services\UserService;

class UserController extends DataTableController {

	/**
	 * @var UserService
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
					'actions' => array('profile', 'edit-profile', 'edit-password',
						'delete-account'),
				)),
			array('deny'),
		);
	}

	public function noCache() {
		return true;
	}

	public function dataTableAction($columns, $orderColumn = null) {
		$this->service->getRepository()->notEqual(array(array(
						'id' => $this->currentUser->getId(),
			)))
				->isNull('super');
		parent::dataTableAction($columns, $orderColumn);
	}

	public function editAction($id, array $redirect = array()) {
		$form = parent::editAction($id, $redirect)->getVariables('form');
		$form->remove('password')->remove('confirm');
		return $this->view;
	}

	public function profileAction($id = null) {
		return $id ? $this->viewAction($id) : $this->view->variables(array('model' => $this->currentUser));
	}

	public function editProfileAction() {
		$model = $this->currentUser;

		$this->service->setModel($model);
		$form = $this->service->getForm()
				->remove('password')
				->remove('confirm')
				->remove('role')
				->remove('active')
				->remove('guarantors')
				->remove('modeOfId')
				->remove('sourceOfFunds')
				->setModel($model);
		$form->get('email')->attributes->add(array('readonly' => 'readonly'));
		$form->get('accountNumber')->attributes->add(array('readonly' => 'readonly'));
		if ($this->request->isPost()) {
			$form->setData($this->request->getPost());
			if ($form->isValid() && $this->service->save($form->getModel(), $this->request->getFiles())) {
				$this->resetUserIdentity($this->service->getModel());
				$this->flash()->setSuccessMessage('Profile saved successfully');
				$this->redirect($this->getModule(), $this->getClassName(), 'profile');
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

	public function editPasswordAction(array $redirect = array()) {
		$redirect['module'] = !empty($redirect['module']) ? $redirect['module'] : 'guest';
		$redirect['controller'] = !empty($redirect['controller']) ? $redirect['controller'] : 'index';
		$redirect['action'] = !empty($redirect['action']) ? $redirect['action'] : 'logout';
		$model = $this->currentUser;
		$this->service->setModel($model);
		$form = $this->service->getPasswordForm();
		if ($this->request->isPost()) {
			$form->setData($this->request->getPost());
			if ($form->isValid() && $this->service->changePassword($form->getData())) {
				$this->flash()->setSuccessMessage('Password changed successfully. Please login with your new password');
				$this->redirect($redirect['module'], $redirect['controller'], $redirect['action'], array($this->getModule(),
					$this->getClassName(), 'profile'), $redirect['hash']);
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

	public function resetPasswordAction($id) {
		if ($this->service->resetPassword($id)) {
			$this->flash()->setSuccessMessage('Password reset successfully');
		}
		$this->flash()->setErrorMessage('Password reset failed');
		$this->redirect($this->getModule(), $this->getClassName(), 'index');
	}

}
