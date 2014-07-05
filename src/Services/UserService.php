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

    /**
     * Inject form into the service
     * @return array
     */
    protected function inject() {
        return array_merge(parent::inject(), array(
            'form' => array(
                'class' => 'DSLive\Forms\UserForm'
            ),
            'passwordForm' => array(
                'class' => 'DSLive\Forms\PasswordForm',
            ),
        ));
    }

    public function getPasswordForm() {
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
        return parent::delete($user ? $user : true);
    }

    public function changePassword(Object $model) {
        if ($this->model->hashPassword($model->old) !== $this->model->getPassword())
            return false;

        $this->model->setPassword($model->new);
        $this->model->hashPassword();
        $this->repository->update($this->model, 'id')->execute();
        return $this->flush();
    }
    
}
