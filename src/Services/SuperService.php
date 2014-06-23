<?php

namespace DSLive\Services;

use DScribe\Core\AService,
    DScribe\Core\IModel,
    Exception,
    Object;

abstract class SuperService extends AService {

    protected $form;

    /**
     * @var array
     */
    private $errors = array();

    /**
     * Initialize property $errors to empty array
     */
    protected function init() {
        $this->errors = array();
    }

    private function getDefaultFormName() {
        return (class_exists($this->getModule() . '\Forms\\' . $this->getClassName() . 'Form')) ?
                $this->getModule() . '\Forms\\' . $this->getClassName() . 'Form' : null;
    }

    /**
     * Allows public access to form
     * @return \DScibe\Form\Form
     */
    public function getForm() {
        if (!$this->form) {
            if ($defaultFormName = $this->getDefaultFormName())
                $this->form = new $defaultFormName;
        }

        return $this->form;
    }

    /**
     * Fetches all data in the database
     * return array
     */
    public function fetchAll() {
        return $this->repository->fetchAll();
    }

    /**
     * Finds a row from database
     * @param mixed $id Id to fetch with
     * @return mixed
     */
    public function findOne($id, $exception = true) {
        return $this->findOneBy('id', $id, $exception);
    }

    /**
     * Finds a row from database
     * @param string $column Column to fetch by
     * @param mixed $value The value the column must contain
     * @return mixed
     */
    public function findOneBy($column, $value, $exception = true) {
        return $this->findOneWhere(array(array($column => $value)), $exception);
    }

    /**
     * Finds a row from database with the given criteria
     * @param array $criteria
     * @return mixed
     */
    public function findOneWhere($criteria, $exception = true) {
        $model = $this->repository->findOneWhere($criteria);
        if (!$model) {
            if ($exception)
                throw new Exception('Required page was not found');
            else
                $this->addErrors('Required object was not found');
        } else {
            $this->model = $model;
        }
        return $this->model;
    }

    /**
     * Inserts data into the database
     * @param IModel $model
     * @param Object $files
     * @return boolean
     * @todo Set first parameter as form so one can fetch either model or data
     */
    public function create(IModel $model, Object $files = null, $flush = true) {
        if ($files->notEmpty() && method_exists($model, 'uploadFiles') && !$model->uploadFiles($files)) {
            $this->addErrors('File upload failed');
            $this->addErrors($model->getErrors());
            return false;
        }

        if ($this->repository->insert($model)->execute()) {
            if ($flush)
                $this->flush();

            return $model;
        }

        return false;
    }

    /**
     * Saves data into the database
     * @param IModel $model
     * @param Object $files
     * @return boolean
     * @todo Set first parameter as form so one can fetch either model or data
     */
    public function save(IModel $model, Object $files = null, $flush = true) {
        if (!is_object($files)) {
            $files = new \Object();
        }
        if ($files->notEmpty() && method_exists($model, 'uploadFiles') && !$model->uploadFiles($files)) {
            $this->addErrors('File upload failed');
            $this->addErrors($model->getErrors());
            return false;
        }
        if ($this->repository->update($model)->execute()) {
            if ($flush)
                $this->flush();

            return $model;
        }

        return false;
    }

    /**
     * Deletes data from the database
     * return boolean
     */
    public function delete($flush = true) {
        try {
            //@todo find a way to delete attached files
            if (method_exists($this->model, 'unlink')) {
                $this->model->unlink();
            }
            $deleted = $this->repository->delete($this->model)->execute();
            if ($flush) {
                return $this->flush();
            }
            else {
                return $deleted;
            }
        }
        catch (Exception $ex) {
            if (stristr($ex->getMessage(), 'Integrity constraint violation:')) {
                $this->errors[] = ucwords(str_replace('_', ' ', $this->model->getTableName())) .
                        ' is being used in another part of the application';
            }
            return false;
        }
    }

    public function upsert(IModel $model, $where = 'id', Object $files = null, $flush = true) {
        if ($files->notEmpty() && method_exists($model, 'uploadFiles') && !$model->uploadFiles($files)) {
            $this->addErrors('File upload failed');
            $this->addErrors($model->getErrors());
            return false;
        }
        if ($this->repository->upsert(array($model), $where)->execute()) {
            return ($flush) ? $this->flush() : true;
        }
    }

    /**
     * Adds an error to the current operation
     * @param string|array $error
     * @return \DSLive\Controllers\SuperController
     */
    final public function addErrors($error) {
        if (is_string($error))
            $this->errors[] = $error;
        else if (is_array($error))
            $this->errors = array_merge($this->errors, $error);
        else
            throw new \Exception('Error must be of type string or array. Got "' . gettype($error) . '" instead');

        return $this;
    }

    /**
     * Fetches an array of all errors
     * @return array
     */
    final public function getErrors() {
        return is_array($this->errors) ? $this->errors : array();
    }

    /**
     * Surrounds each error in an li tag
     * @return string
     */
    final public function prepareErrors() {
        return '<li>' . join('</li><li>', $this->errors) . '</li>';
    }

}
