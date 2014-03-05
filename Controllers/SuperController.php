<?php

/*
 */

namespace DSLive\Controllers;

use DScribe\Core\AController,
    DScribe\Core\IModel,
    DScribe\Core\Repository,
    DScribe\View\View,
    DSLive\Models\User,
    DSLive\Stdlib\AjaxResponse,
    Exception,
    Object,
    Util;

/**
 * Description of SuperController
 *
 * @author topman
 */
abstract class SuperController extends AController {

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

    /**
     * Clones current user into property $currentUser
     * Sets the layout as the current user's role
     * Initializes the order for indexAction() to "id"
     */
    public function init() {
        $this->currentUser = clone($this->userIdentity()->getUser());
        $this->layout = $this->currentUser->getRole();
        $this->order = 'id';
    }

    public function noCache() {
        return array('index', 'new', 'edit', 'delete');
    }

    public function accessDenied($action, $args = array()) {
        if ($this->request->isAjax()) {
            return $this->ajaxResponse()
                            ->sendJson('You do not have permision to this action/page', AjaxResponse::STATUS_FAILURE);
        }

        if (!$this->currentUser->is('guest'))
            throw new Exception('You do not the required permission to view this page');

        $this->flash()->setErrorMessage('Please login to continue');
        $this->redirect('guest', 'index', 'login', array(
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
     * Creates a new AjaxResponse Object
     * @return AjaxResponse
     */
    protected function ajaxResponse() {
        return new AjaxResponse;
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
    public function newAction(array $variables = array(), array $modifyForm = array(), array $redirect = array()) {
        $form = $this->service->getForm();

        foreach ($modifyForm as $type => $typeArray) {
            switch ($type) {
                case 'ignoreFilters':
                    foreach ($typeArray as $name) {
                        $form->ignoreFilter($name);
                    }
                    break;
                case 'removeElements':
                    foreach ($typeArray as $name) {
                        $form->remove($name);
                    }
                    break;
                case 'setElements':
                    foreach ($typeArray as $elem => $part) {
                        $this->modifyElement($form->get($elem), $part);
                    }
                    break;
            }
        }

        if ($this->request->isPost()) {
            $data = $this->request->getPost()->toArray();
            $this->checkFiles();
            if ($this->request->getFiles()->notEmpty()) {
                $data = array_merge($data, $this->request->getFiles()->toArray());
            }
            $form->setData($data);
            if ($form->isValid() && $this->service->create($form->getModel(), $this->request->getFiles())) {
                if ($this->request->isAjax()) {
                    $this->ajaxResponse()
                            ->setAction(AjaxResponse::ACTION_RESET_WAIT)
                            ->sendJson('Save successful');
                }
                $this->flash()->setSuccessMessage('Save successful');

                if (isset($this->request->getPost()->saveAndNew)) {
                    $form->reset();
                }
                else {
                    $this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array());
                }
            }
            else {
                if ($this->request->isAjax()) {
                    $this->ajaxResponse()
                            ->setErrors($form->prepareErrorMsgs())
                            ->sendJson('Save failed', AjaxResponse::STATUS_FAILURE, AjaxResponse::ACTION_WAIT);
                }
                $this->flash()->setErrorMessage('Save failed');
            }
        }

        $this->view->variables(array_merge($variables, array(
            'form' => $form,
        )));

        return $this->request->isAjax() ?
                $this->view->partial() :
                $this->view;
    }

    private function checkFiles() {
        foreach ($this->request->getFiles()->toArray() as $name => $data) {
            if (empty($data->name)) {
                $this->request->getFiles()->remove($name);
            }
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
    public function editAction($id, array $variables = array(), array $modifyForm = array(), array $redirect = array()) {
        $model = (is_object($id)) ? $id : $this->service->findOne($id);
        $form = $this->service->getForm();

        foreach ($modifyForm as $type => $typeArray) {
            switch ($type) {
                case 'ignoreFilters':
                    foreach ($typeArray as $name) {
                        $form->ignoreFilter($name);
                    }
                    break;
                case 'removeElements':
                    foreach ($typeArray as $name) {
                        $form->remove($name);
                    }
                    break;
                case 'setElements':
                    foreach ($typeArray as $elem => $part) {
                        $this->modifyElement($form->get($elem), $part);
                    }
                    break;
            }
        }

        $form->setModel($model);
        if ($this->request->isPost()) {
            $data = $this->request->getPost()->toArray();
            $this->checkFiles();
            if ($this->request->getFiles()->notEmpty()) {
                $data = array_merge($data, $this->request->getFiles()->toArray());
            }
            $form->setData($data);
            if ($form->isValid() && $this->service->save($form->getModel(), $this->request->getFiles())) {
                if ($this->request->isAjax()) {
                    $this->ajaxResponse()
                            ->setAction(AjaxResponse::ACTION_WAIT)
                            ->sendJson('Save successful');
                }
                $this->flash()->setSuccessMessage('Save successful');
                $this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array());
            }
            else {
                if ($this->request->isAjax()) {
                    $this->ajaxResponse()
                            ->sendJson('Save failed', AjaxResponse::STATUS_FAILURE, AjaxResponse::ACTION_WAIT);
                }
                $this->flash()->setErrorMessage('Save failed');
            }
        }

        $this->view->variables(array_merge($variables, array(
            'model' => $model,
            'form' => $form,
        )));

        return $this->request->isAjax() ?
                $this->view->partial() :
                $this->view;
    }

    /**
     * Modifies an element in the form
     * @param Object $elem
     * @param array $part 
     */
    private function modifyElement(Object $elem, array $part) {
        foreach ($part as $key => $value) {
            $toDo = null;
            $path = explode('.', $key);
            foreach ($path as $ky => $pt) {
                if (!$ky) {
                    $toDo = & $elem->$pt;
                }
                elseif (count($path) < ($ky + 1)) {
                    $toDo = & $toDo->$pt;
                }
                else {
                    $toDo->$pt = $value;
                }
            }
        }
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
    public function deleteAction($id, $confirm = null, array $variables = array(), array $redirect = array()) {
        $model = $this->service->findOne($id);
        if ($confirm == 1) {
            if ($this->service->delete()) {
                if ($this->request->isAjax()) {
                    $this->ajaxResponse()
                            ->sendJson('Delete successful');
                }
                $this->flash()->setSuccessMessage('Delete successful');
            }
            else {
                if ($this->request->isAjax()) {
                    $this->ajaxResponse()
                            ->sendJson('Delete failed', AjaxResponse::STATUS_FAILURE, AjaxResponse::ACTION_WAIT);
                }
                $this->flash()->setErrorMessage('Delete failed');
            }
            $this->redirect((isset($redirect['module'])) ? $redirect['module'] : \Util::camelToHyphen($this->getModule()), (isset($redirect['controller'])) ? $redirect['controller'] : \Util::camelToHyphen($this->getClassName()), (isset($redirect['action'])) ? $redirect['action'] : 'index', (isset($redirect['params'])) ? $redirect['params'] : array());
        }

        $this->view->variables(array_merge(array(
            'model' => $model,
                        ), $variables));

        return $this->request->isAjax() ?
                $this->view->partial() :
                $this->view;
    }

}
