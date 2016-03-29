<?php

namespace DSLive\Services;

use DSLive\Forms\PasswordForm,
    DSLive\Models\User,
    Object;

class UserService extends SuperService {

    /**
     *
     * @var PasswordForm
     */
    protected $passwordForm;

    public function getPasswordForm() {
	if (!$this->passwordForm) $this->passwordForm = new PasswordForm ();
	return $this->passwordForm;
    }

    /**
     * Inserts data into the database
     * @param \DSLive\Models\User
     * return boolean
     */
    public function create(User $model, $files) {
	$model->hashPassword();
	return parent::create($model, $files);
    }

    public function delete($user = null) {
	if (!$this->model->unlink()) return false;
	return parent::delete($user ? $user : true);
    }

    public function changePassword(Object $model, $verify = true) {
	if ($verify && !$this->model->verifyPassword($model->old)) return false;

	$this->model->setPassword($model->new);
	$this->model->hashPassword();
	$this->repository->update($this->model, 'id')->execute();
	return $this->flush();
    }

    public function saveImports(Object $post, $flush = true) {
	$importDir = DATA . 'imports' . DIRECTORY_SEPARATOR;
	if (is_readable($importDir . $this->model->getTableName() . '.php')) {
	    $imported = include $importDir . $this->model->getTableName() . '.php';
	    foreach ($imported[0] as $col) {
		$columns[] = lcfirst(str_replace(array(' ', '*'), '', $col));
	    }
	    unset($imported[0]);
	    $save = array();
	    foreach ($imported as $line) {
		$model = clone $this->model;
		$model->populate(array_combine($columns, $line))
			->setRole($line[count($line) - 1] == 1 ?
					'member-auditor' : 'member')
			->setActive(true)
			->preSave();
		$save[] = $model->toArray();
	    }
	    if ($this->repository->insert($save)->execute()) {
		if (!$flush) return true;
		if ($this->flush()) {
		    unlink($importDir . $this->model->getTableName() . '.php');
		    return true;
		}
	    }
	}
	return false;
    }

}
