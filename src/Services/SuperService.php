<?php

namespace DSLive\Services;

use DBScribe\Table,
    DBScribe\Util as DU,
    DScribe\Core\AService,
    DScribe\Core\IModel,
    DSLive\Forms\ImportForm,
    DSLive\Models\Model,
    Exception,
    Object,
    Util;

abstract class SuperService extends AService {

    protected $form;

    /**
     *
     * @var Model
     */
    protected $model;

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
    public function fetchAll($returnType = Table::RETURN_MODEL) {
        return $this->repository->fetchAll($returnType);
    }

    /**
     * Finds a row from database
     * @param mixed $id Id to fetch with
     * @return mixed
     */
    public function findOne($id, $exception = true, $returnType = Table::RETURN_MODEL) {
        return $this->findOneBy('id', $id, $exception, $returnType);
    }

    /**
     * Finds a row from database
     * @param string $column Column to fetch by
     * @param mixed $value The value the column must contain
     * @return mixed
     */
    public function findOneBy($column, $value, $exception = true, $returnType = Table::RETURN_MODEL) {
        return $this->findOneWhere(array(array($column => $value)), $exception, $returnType);
    }

    /**
     * Finds a row from database with the given criteria
     * @param array $criteria
     * @return mixed
     */
    public function findOneWhere($criteria, $exception = true, $returnType = Table::RETURN_MODEL) {
        $model = $this->repository->findOneWhere($criteria, $returnType);
        if (!$model) {
            if ($exception)
                throw new Exception('Required page was not found');
            else
                $this->addErrors('Required object was not found');
        } else {
            $this->model = $model;
        }
        return $model;
    }

    /**
     * Inserts data into the database
     * @param IModel $model
     * @param Object $files
     * @return boolean
     * @todo Set first parameter as form so one can fetch either model or data
     */
    public function create(IModel $model, Object $files = null, $flush = true) {
        if ($files && $this->checkFileIsNotEmpty($files->toArray(true)) &&
                method_exists($model, 'uploadFiles') &&
                !$upload = $model->uploadFiles($files)) {
            $this->addErrors('File upload failed');
            $this->addErrors($model->getErrors());
            return false;
        } else if (!$upload && method_exists($model, 'uploadFiles')) {
            foreach (array_keys($files->toArray(true)) as $property) {
                $model->postFetch($property);
            }
        }
        if ($this->repository->insert($model)->execute()) {
            if ($flush)
                $this->flush();

            $model->postFetch();
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
        $upload = true;
        if ($files && $this->checkFileIsNotEmpty($files->toArray(true)) &&
                method_exists($model, 'uploadFiles') &&
                !$upload = $model->uploadFiles($files)) {
            $this->addErrors('File upload failed');
            $this->addErrors($model->getErrors());
            return false;
        } else if (!$upload && method_exists($model, 'uploadFiles')) {
            foreach (array_keys($files->toArray(true)) as $property) {
                $model->postFetch($property);
            }
        }
        if ($this->repository->update($model)->execute()) {
            if ($flush)
                $this->flush();

            $model->postFetch();
            return $model;
        }

        return false;
    }

    private function checkFileIsNotEmpty($files) {
        foreach ($files as $file) {
            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Deletes data from the database
     * return boolean
     */
    public function delete($model = null, $flush = true) {
        if (is_object($model)) {
            $this->model = $model;
        } else if (is_bool($model)) {
            $flush = $model;
        }

        try {
            if (method_exists($this->model, 'unlink')) {
                $this->model->unlink();
            }
            $this->model->preSave(false);
            $deleted = $this->repository->delete($this->model)->execute();
            if ($flush) {
                return $this->flush();
            } else {
                return $deleted;
            }
        } catch (Exception $ex) {
            if (stristr($ex->getMessage(), 'Integrity constraint violation:')) {
                $this->errors[] = ucwords(str_replace('_', ' ', $this->model->getTableName())) .
                        ' is being used in another part of the application';
            }
            return false;
        }
    }

    public function upsert(IModel $model, $where = 'id', Object $files = null, $flush = true) {
        if ($files && $files->notEmpty() && method_exists($model, 'uploadFiles') && !$model->uploadFiles($files)) {
            $this->addErrors('File upload failed');
            $this->addErrors($model->getErrors());
            return false;
        }
        if ($this->repository->upsert(array($model), $where)->execute()) {
            if ($flush)
                $this->flush();

            $model->postFetch();
            return $model;
        }

        return false;
    }

    /**
     * @return ImportForm
     */
    public function getImportForm() {
        return new ImportForm();
    }

    public function import(Object $data) {
        if (empty($data->file->name) || $data->file->error)
            return false;

        $dir = DATA . 'temp' . DIRECTORY_SEPARATOR;
        $importDir = DATA . 'imports' . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!is_dir($importDir)) {
            mkdir($importDir, 0777, true);
        }

        if (move_uploaded_file($data->file->tmpName, $dir . 'import.csv')) {
            $file = fopen($dir . 'import.csv', 'r');
            $temp = array();
            $count = 0;
            while (($line = fgetcsv($file)) !== FALSE) {
                $temp[] = $line;
                $count++;
            }
            fclose($file);
            unlink($dir . 'import.csv');
            return Util::updateConfig($importDir . $this->model->getTableName() . '.php', $temp, true, array());
        }

        return false;
    }

    public function saveImports() {
        $importDir = DATA . 'imports' . DIRECTORY_SEPARATOR;
        if (is_readable($importDir . $this->model->getTableName() . '.php')) {
            $imported = include $importDir . $this->model->getTableName() . '.php';
            unset($imported[0]);
            $columns = $this->model->getTable()->getColumns(true);
            $save = array();
            foreach ($imported as $line) {
                $this->model->populate(array_combine($columns, $line))->preSave();
                $save[] = $this->model->toArray();
            }
            if ($this->repository->insert($save)->execute()) {
                if ($this->flush()) {
                    unlink($importDir . $this->model->getTableName() . '.php');
                    return true;
                }
            }
        }
        return false;
    }

    public function export() {
        $path = DATA . 'temp' . DIRECTORY_SEPARATOR;
        $file = $path . $this->model->getTableName() . '_' . str_replace(' ', '_', DU::createTimestamp()) . '.csv';

        $handle = fopen($file, 'w+');
        $table = $this->model->getTable();
        foreach ($table->getColumns(true) as $column) {
            $headings[] = ucwords(str_replace('_', ' ', $column));
        }
        fputcsv($handle, $headings);
        foreach ($this->repository->fetchAll(Table::RETURN_DEFAULT) as $row) {
            fputcsv($handle, array_values($row));
        }

        header('Content-Description: File Transfer');

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        unlink($file);
    }

    /**
     * Adds an error to the current operation
     * @param string|array $error
     * @return \DSLive\Controllers\SuperService
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
        return ($this->errors) ? '<li>' . join('</li><li>', $this->errors) . '</li>' : null;
    }

}
