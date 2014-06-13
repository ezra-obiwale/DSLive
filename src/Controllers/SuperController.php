<?php

/*
 */

namespace DSLive\Controllers;

use DScribe\Core\AController,
    DScribe\Core\Engine,
    DScribe\Core\IModel,
    DScribe\Core\Repository,
    DScribe\View\View,
    DSLive\Models\User,
    DSLive\Services\SuperService,
    DSLive\Stdlib\AjaxResponse,
    Exception,
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
        $this->currentUser->setConnection(Engine::getDB());
        $this->layout = $this->currentUser->getRole();
        $this->order = 'id';
    }

    public function getCurrentUserFromDB() {
        if (!$this->userIsLive && $this->currentUser->getId()) {
            $userRepo = new Repository(new \DSLive\Models\User);
            $user = $userRepo->findOne($this->currentUser->getId());
            if ($user)
                $this->currentUser = $user;

            $this->userIsLive = true;
        }
        return $this->currentUser;
    }

    public function noCache() {
        return array('index', 'new', 'edit', 'delete');
    }

    public function accessDenied($action, $args = array(), array $redirect = array()) {
        if ($this->request->isAjax()) {
            return $this->ajaxResponse()
                            ->sendJson('You do not have permision to this action/page', AjaxResponse::STATUS_FAILURE);
        }

        if (!$this->currentUser->is('guest'))
            throw new Exception('You do not have the required permission to view this page');

        $this->flash()->addMessage('Please login to continue');
        $this->redirect($redirect['module'] ? $redirect['module'] : 'guest', $redirect['controller'] ? $redirect['controller'] : 'index', $redirect['action'] ? $redirect['action'] : 'login', array(
            Util::camelToHyphen($this->getModule()),
            Util::camelToHyphen($this->getClassName()),
            Util::camelToHyphen($action),
            join(':', $args)
        ));
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
            if (in_array($action, array('index', 'new', 'edit', 'view', 'delete')))
                continue;
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

            if (!property_exists($this->service->getModel(), \Util::_toCamel($column)))
                continue;

            $repository->orderBy($column, $direction);
        }

        $this->view->variables(array(
            'models' => $repository->fetchAll(),
        ));

        return $this->request->isAjax() ?
                $this->view->partial() :
                $this->view;
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
        $form = $this->service->getForm();

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->checkFiles($data);
            $form->setData($data);
            if ($form->isValid() && $this->service->create($form->getModel(), $this->request->getFiles())) {
                $this->flash()->setSuccessMessage('Save successful');

                if (isset($this->request->getPost()->saveAndNew)) {
                    $form->reset();
                }
                else {
                    $this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array());
                }
            }
            else {
                $this->flash()
                        ->setErrorMessage('Save failed')
                        ->addErrorMessage($this->service->getErrors());
            }
        }

        $this->view->variables(array(
            'form' => $form,
        ));

        return $this->request->isAjax() ?
                $this->view->partial() :
                $this->view;
    }

    protected function checkFiles(&$data, $model = null) {
        foreach ($this->request->getFiles()->toArray() as $name => $dat) {
            if ((!is_array($dat->name) && empty($dat->name)) || (is_array($dat->name) && empty($dat->name[0]))) {
                $this->request->getFiles()->remove($name);
                $method = 'get' . ucfirst($name);
                $this->request->getPost()->$name = $model->$method();
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
        $model = (is_object($id)) ? $id : $this->service->findOne($id);
        $form = $this->service->getForm();

        $form->setModel(clone $model);
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->checkFiles($data, $model);
            $form->setData($data);
            if ($form->isValid() && $this->service->save($form->getModel(), $this->request->getFiles())) {
                $this->flash()->setSuccessMessage('Save successful');
                $this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array());
            }
            else {
                $this->flash()
                        ->setErrorMessage('Save failed')
                        ->addErrorMessage($this->service->getErrors());
            }
        }

        $this->view->variables(array(
            'model' => $model,
            'form' => $form,
        ));

        return $this->request->isAjax() ?
                $this->view->partial() :
                $this->view;
    }

    /**
     * View a model
     * @param string $id Id of the model
     * @return View
     */
    public function viewAction($id) {
        $this->view->variables(array(
            'model' => (is_object($id) && is_a($id, 'DBScribe\Row')) ? $id : $this->service->findOne($id),
        ));

        return $this->request->isAjax() ?
                $this->view->partial() :
                $this->view;
    }

    /**
     * Deletes a model from the repository
     * @param string $id Id of the model to delete
     * @param int $confirm >= 1 to confirm delete
     * @return View
     */
    public function deleteAction($id, $confirm = null, array $redirect = array()) {
        $model = (is_object($id)) ? $id : $this->service->findOne($id);
        if ($confirm == 1) {
            if ($this->service->delete()) {
                $this->flash()->setSuccessMessage('Delete successful');
            }
            else {
                $this->flash()
                        ->setErrorMessage('Delete failed')
                        ->addErrorMessage($this->service->getErrors());
            }
            $this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array());
        }

        $this->view->variables(array(
            'model' => $model,
        ));

        return $this->request->isAjax() ?
                $this->view->partial() :
                $this->view;
    }

}
