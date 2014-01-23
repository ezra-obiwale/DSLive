<?php

namespace DSLive\Services;

use DScribe\Core\AService,
    DScribe\Core\IModel,
    Exception,
    Object;

abstract class SuperService extends AService {

    protected $form;
    /**
     * @todo
     * @var array
     */
    protected $errors;

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

    protected function inject() {
        $defaultFormName = $this->getDefaultFormName();
        return $defaultFormName ?
                array(
            'form' => array(
                'class' => $defaultFormName,
            )
                ) : array();
    }

    /**
     * Allows public access to form
     * @return \DScibe\Form\Form
     */
    public function getForm() {
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
    public function findOneBy($column, $value, $exception = true) {
        $model = $this->repository->findOneBy($column, $value);
        if (!$model && $exception)
            throw new Exception('Required page was not found');
        $this->model = $model;
        return $this->model;
    }

    /**
     * Finds a row from database
     * @param mixed $id Id to fetch with
     * @return mixed
     */
    public function findOne($id, $exception = true) {
        $model = $this->repository->findOne($id);
        if (!$model && $exception)
            throw new Exception('Required page was not found');
        $this->model = $model;
        return $this->model;
    }

    /**
     * Finds a row from database with the given criteria
     * @param array $criteria
     * @return mixed
     */
    public function findOneWhere($criteria, $exception = true) {
        $model = $this->repository->findOneWhere($criteria);
        if (!$model && $exception)
            throw new Exception('Required page was not found');
        $this->model = $model;
        return $this->model;
    }

    /**
     * Inserts data into the database
     * @param IModel $model
     * @param Object $files
     * @return boolean
     */
    public function create(IModel $model, Object $files = null, $flush = true) {
        if (method_exists($model, 'uploadFiles') && !$model->uploadFiles($files))
            return false;

        $created = $this->repository->insert($model)->execute();
        if ($flush) {
            return $this->flush();
        }
        else {
            return $created;
        }
    }

    /**
     * Saves data into the database
     * @param IModel $model
     * @param Object $files
     * @return boolean
     */
    public function save(IModel $model, Object $files = null, $flush = true) {
        if (!is_object($files)) {
            $files = new \Object();
        }
        $_files = array_values($files->toArray());
        if ($files->count() && !empty($_files[0]->tmpName)) {
            if (method_exists($model, 'unlink')) {
                foreach ($files->toArray() as $name => $content) {
                    $model->unlink($name);
                }
            }
            if (method_exists($model, 'uploadFiles')) {
                if (!$model->uploadFiles($files))
                    return false;
            }
        }

        $saved = $this->repository->update($model, 'id')->execute();

        if ($flush) {
            return $this->flush();
        }
        else {
            return $saved;
        }
    }

    /**
     * Deletes data from the database
     * return boolean
     */
    public function delete($flush = true) {
        try {
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

}
