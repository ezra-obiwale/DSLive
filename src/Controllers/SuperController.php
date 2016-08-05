<?php

/*
 */

namespace dsLive\Controllers;

use dbScribe\Repository,
	dScribe\Core\AController,
	dScribe\Core\AModel,
	dScribe\Core\IModel,
	dScribe\View\View,
	dsLive\Models\User,
	dsLive\Services\SuperService,
	Exception,
	Json,
	Util;

/**
 * Description of SuperController
 *
 * @author topman
 */
abstract class SuperController extends AController {

	/**
	 *
	 * @var SuperService
	 */
	protected $service;

	/**
	 * A clone of the current user
	 * @var User
	 */
	protected $currentUser;

	/**
	 * Inidates order which the index action should sort fetched models by. Defaults to id
	 * @var string|array $order As string, should be a property in the model
	 *
	 * As array, if zero-indexed, the default direction would be Repository::ORDER_ASC
	 *
	 * if named-indexes, keys are columns and values are directions which should be
	 *  either Repository::ORDER_ASC  or Repository::ORDER_DESC
	 */
	protected $order;
	private $userIsLive;

	/**
	 * Clones current user into property $currentUser
	 * Sets the layout as the current user's role
	 * Initializes the order for indexAction() to "id"
	 */
	public function init() {
		$this->currentUser = clone($this->userIdentity()->getUser());
		$this->currentUser->setConnection(engineGet('db'));
		$this->layout = $this->currentUser->getRole();
		$this->order = 'id';

		if ($this->request->isAjax()) $this->view->partial();
	}

	/**
	 *
	 * @return User
	 */
	final public function getCurrentUserFromDB() {
		if (!$this->userIsLive && $this->currentUser->getId()) {
			$userRepo = new Repository($this->currentUser, engineGet('db'), true);
			$user = $userRepo->findOne($this->currentUser->getId());
			if ($user) $this->currentUser = $user;

			$this->userIsLive = true;
		}
		return $this->currentUser;
	}

	public function noCache() {
		return true;
	}

	public function accessDenied($action, $args = array(), array $redirect = array()) {
		if ($this->request->isAjax()) {
			$json = new Json(array(
				'status' => 'error',
				'message' => 'You are not allowed to this action/page'
			));
			$json->toScreen(true);
		}

		if (!$this->currentUser->is('guest'))
				throw new Exception('You do not have the required permission to view this page');

		$this->flash()->addMessage('Please login to continue');
		$this->redirect($redirect['module'] ? $redirect['module'] : 'guest', $redirect['controller'] ? $redirect['controller'] : 'index', $redirect['action'] ? $redirect['action'] : 'login', array(
			Util::camelToHyphen($this->getModule()),
			Util::camelToHyphen($this->getClassName()),
			Util::camelToHyphen($action),
			join(':', $args)
				), $redirect['hash'], true);
	}

	public function accessRules() {
		return array(
			array('allow', array(
					'role' => 'subscriber',
					'actions' => $this->getPublicActions(),
				)),
			array('deny', array(
					'role' => 'subscriber'
				)),
			array('allow', array(
					'role' => '@'
				)),
			array('deny'),
		);
	}

	private function getPublicActions() {
		$return = array();
		foreach ($this->getActions() as $action) {
			if (in_array($action, array('index', 'new', 'edit', 'view', 'delete'))) continue;
			$return[] = $action;
		}
		return $return;
	}

	/**
	 * Fetches all models in repository
	 * @return View
	 */
	public function indexAction() {
		$repository = $this->service
				->getRepository();
		$order = $this->order;

		if (!is_array($order)) {
			$order = array($order);
		}
		foreach ($order as $column => $direction) {
			if (is_int($column)) {
				$column = $direction;
				$direction = Repository::ORDER_ASC;
			}

			if (!property_exists($this->service->getModel(), \Util::_toCamel($column))) continue;

			$repository->orderBy($column, $direction);
		}
		return $this->view->variables(array(
					'models' => $repository->fetchAll(),
		));
	}

	/**
	 * Create a new model
	 * @param array $variables Additional variables to send to the view file
	 * @param array $modifyForm May contain the following keys:
	 *
	 * <b>ignoreFilters (array)</b> - Array of filters to ignore when validating the form <br />
	 * <b>removeElements (array)</b> - Array of elements to remove from the form altogether<br />
	 * <b>setElements (array)</b> - Array of keys as property to edit. Use (dot) to indicate
	 *      path to actual property to edit in case value of first property is an object
	 *      e.g. <i>'options.value' => 'no value for the element'</i>
	 *
	 * @param array $redirect May contain any of keys [(string) module, (string)
	 * controller, (string) action, (array) params
	 * @return View
	 */
	public function newAction(array $redirect = array()) {
		$this->view->file('d-scribe/ds-live/src/View/views/misc/form', true);
		$form = $this->service->getForm();

		if ($this->request->isPost()) {
			if ($this->request->isAjax()) $form->remove('csrf');
			$data = $this->request->getPost();
			$this->checkFiles($data);
			$form->setData($data);
			if ($form->isValid() && $model = $this->service->create($form->getModel(), $this->request->getFiles())) {
				if ($this->request->isAjax()) {
					$json = new Json(array(
						'status' => true,
						'message' => 'Save successful',
						'data' => $model,
					));
					$json->toScreen(true);
				}
				$this->flash()->setSuccessMessage('Save successful');

				if (isset($this->request->getPost()->saveAndNew)) {
					$form->reset();
				}
				else {
					$this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array(), (isset($redirect['hash'])) ? $redirect['hash'] : null);
				}
			}
			else {
				if ($this->request->isAjax()) {
					$json = new Json(array(
						'status' => false,
						'message' => '<strong>Save failed!</strong><br /><ul>' . $this->service->prepareErrors() . '</ul>',
					));
					$json->toScreen(true);
				}
				$this->flash()
						->setErrorMessage('Save failed')
						->addErrorMessage($this->service->getErrors());
			}
		}
		return $this->view->variables(array(
					'form' => $form,
		));
	}

	protected function checkFiles(&$data, &$model = null) {
		$model = (!$model) ? $this->service->getModel() : $model;
		foreach ($this->request->getFiles()->toArray() as $name => $dat) {
			if ((!is_array($dat->name) && empty($dat->name)) || (is_array($dat->name) && empty($dat->name[0]))) {
				$this->request->getFiles()->remove($name);
			}
		}
		if ($this->request->getFiles()->notEmpty()) {
			$data->add($this->request->getFiles()->toArray());
		}
	}

	/**
	 * Edit a model
	 * @param string|IModel $id Id of model or a model
	 * @param array $variables Additional variables to send to the view file
	 * @param array $modifyForm May contain the following keys:
	 *
	 * <b>ignoreFilters (array)</b> - Array of filters to ignore when validating the form <br />
	 * <b>removeElements (array)</b> - Array of elements to remove from the form altogether<br />
	 * <b>setElements (array)</b> - Array of keys as property to edit. Use (dot) to indicate
	 *      path to actual property to edit in case value of first property is an object
	 *      e.g. <i>'options.value' => 'no value for the element'</i>
	 *
	 * @param array $redirect May contain any of keys [(string) module, (string)
	 * controller, (string) action, (array) params
	 * @return View
	 */
	public function editAction($id, array $redirect = array()) {
		$this->view->file('d-scribe/ds-live/src/View/views/misc/form', true);
		$model = (is_object($id)) ? $id : $this->service->findOne($id);
		$form = $this->service->getForm();

		$form->setModel(clone $model);
		if ($this->request->isPost()) {
			if ($this->request->isAjax()) $form->remove('csrf');
			$data = $this->request->getPost();
			$this->checkFiles($data, $model);
			$form->setModel(clone $model);
			$form->setData($data);
			if ($form->isValid() && $model = $this->service->save($form->getModel(), $this->request->getFiles())) {
				if ($this->request->isAjax()) {
					$json = new Json(array(
						'status' => true,
						'message' => 'Save successful',
						'data' => $model,
					));
					$json->toScreen(true);
				}
				$this->flash()->setSuccessMessage('Save successful');
				$this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array(), (isset($redirect['hash'])) ? $redirect['hash'] : null);
			}
			else {
				if ($this->request->isAjax()) {
					$json = new Json(array(
						'status' => false,
						'message' => '<strong>Save failed!</strong><br /><ul>' . $this->service->prepareErrors() . '</ul>',
					));
					$json->toScreen(true);
				}
				$this->flash()
						->setErrorMessage('Save failed')
						->addErrorMessage($this->service->getErrors());
			}
		}

		return $this->view->variables(array(
					'model' => $model,
					'form' => $form,
		));
	}

	/**
	 * View a model
	 * @param string $id Id of the model
	 * @return View
	 */
	public function viewAction($id) {
		return $this->view->variables(array(
					'model' => (is_object($id) && is_a($id, 'dbScribe\Row')) ? $id : $this->service->findOne($id),
		));
	}

	/**
	 * Deletes a model from the repository
	 * @param string|AModel $id Id of the model or model to delete
	 * @param int $confirm >= 1 to confirm delete
	 * @return View
	 */
	public function deleteAction($id, $confirm = null, array $redirect = array()) {
		$model = (is_object($id)) ? $id : $this->service->findOne($id);
		if ($confirm == 1) {
			if ($this->service->delete($model)) {
				if ($this->request->isAjax()) {
					$json = new Json(array(
						'status' => true,
						'message' => 'Delete successful',
					));
					$json->toScreen(true);
				}
				$this->flash()->setSuccessMessage('Delete successful');
			}
			else {
				if ($this->request->isAjax()) {
					$json = new Json(array(
						'status' => false,
						'message' => '<strong>Delete failed!</strong><br /><ul>' . $this->service->prepareErrors() . '</ul>',
					));
					$json->toScreen(true);
				}
				$this->flash()
						->setErrorMessage('Delete failed')
						->addErrorMessage($this->service->getErrors());
			}
			$this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array(), (isset($redirect['hash'])) ? $redirect['hash'] : null);
		}

		return $this->view->variables(array(
					'model' => $model,
		));
	}

	public function deleteManyAction($redirect = array()) {
		$this->service->getRepository()->in('id', $this->request->getPost()->ids)
				->delete()->execute();
		$this->service->flush();
		$this->redirect($redirect['module'] ? $redirect['module'] :
						$this->getModule(), $redirect['controller'] ? $redirect['controller'] :
						$this->getClassName(), $redirect['action'] ? $redirect['action'] :
						'index', $redirect['params'] ? $redirect['params'] : array());
	}

	public function importAction($template_link) {
		if (!$template_link)
				$template_link = $this->view->url($this->getModule(), $this->getClassName(), 'download-template-file');
		if ($this->request->isPost()) {
			if ($this->service->import($this->request->getFiles(), $this->request->getPost())) {
				$this->flash()->setSuccessMessage('Upload successful');
				$this->redirect($this->getModule(), $this->getClassName(), 'view-unsaved-imports');
			}
			else
					$this->flash()->setErrorMessage('Upload failed')
						->addErrorMessage($this->service->getErrors());
		}

		return $this->view->variables(array(
					'form' => $this->service->getImportForm(),
					'template_link' => $template_link,
				))->file('d-scribe/ds-live/src/View/views/misc/import', true);
	}

	/**
	 * 
	 * @param string $download_file_name
	 * @param string $rows CSV
	 * @param boolean $forceNew Always create new file
	 */
	public function downloadTemplateFileAction($download_file_name = null, $rows = null,
											$forceNew = false) {
		$headings = array();
		$table = $this->service->getModel()->getTable();
		$path = DATA . 'temp' . DIRECTORY_SEPARATOR;
		if (!is_dir($path)) mkdir($path);
		$file = $download_file_name ?
				$path . $download_file_name :
				$path . str_replace('ds_', '', $table->getName()) . '_template.csv';
		if ($forceNew || !is_readable($file)) {
			$handle = fopen($file, 'w+');
			if (!$rows) {
				foreach ($table->getColumns() as $column => $descArray) {
					$heading = ucwords(str_replace('_', ' ', $column));
					if (strtolower($descArray['nullable']) === 'no') $heading = '*' . $heading;

					$headings[] = $heading;
				}
				fputcsv($handle, $headings);
			}
			else {
				foreach ($rows as $row) {
					fputcsv($handle, $row);
				}
			}
		}

		header('Content-Description: File Download');

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' .
				($download_file_name ? $download_file_name : basename($file)));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		ob_clean();
		flush();
		readfile($file);
	}

	public function exportAction() {
		$this->service->export();
	}

	public function viewUnsavedImportsAction() {
		$importDir = DATA . 'imports' . DIRECTORY_SEPARATOR;
		if (!is_readable($importDir . $this->service->getModel()->getTableName() . '.php')) {
			$imported = array();
		}
		else {
			$imported = include $importDir . $this->service->getModel()->getTableName() . '.php';
		}
		return $this->view->variables(array(
					'imported' => $imported,
					'title' => ucwords(Util::camelToSpace($this->getClassName())),
				))->file('d-scribe' . DIRECTORY_SEPARATOR . 'ds-live'
						. DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
						. 'View' . DIRECTORY_SEPARATOR . 'views'
						. DIRECTORY_SEPARATOR . 'misc' . DIRECTORY_SEPARATOR
						. 'view-unsaved-imports', true);
	}

	/**
	 * @param string $action Redirection action
	 * @param array $params Parameters for redirection
	 */
	public function saveImportsAction($action = 'import', $params = array()) {
		if ($this->service->saveImports($this->request->getPost())) {
			$this->flash()->setSuccessMessage('Imports saved successfully');
		}
		else {
			$this->flash()->setErrorMessage('Save imports failed');
		}
		$this->redirect($this->getModule(), $this->getClassName(), $action, $params);
	}

	/**
	 * Checks if the HTTP Referer is the given url
	 *
	 * @param string|array $url A single url or an array of urls to check. Urls
	 * should start with http|https
	 * @return boolean
	 */
	final protected function isReferer($url) {
		if (!is_array($url)) {
			$url = array($url);
		}

		$return = false;
		foreach ($url as $u) {
			if (substr($this->request->getHttp()->referer, 0, strlen($u)) === $u) $return = true;
		}

		return $return;
	}

}
