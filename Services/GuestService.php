<?php

namespace DSLive\Services;

use DScribe\Core\AService,
    DScribe\Core\Engine,
    DScribe\View\View,
    DSLive\Forms\LoginForm,
    DSLive\Forms\RegisterForm,
    DSLive\Models\AdminUser,
    DSLive\Models\User;

class GuestService extends AService {

    protected $loginForm;
    protected $registerForm;

    protected function init() {
        $this->setModel(new User);
    }

    protected function inject() {
        return array(
            'loginForm' => array(
                'class' => 'DSLive\Forms\LoginForm'
            ),
            'registerForm' => array(
                'class' => 'DSLive\Forms\RegisterForm'
            ),
        );
    }

    /**
     * Fetches the registration form
     * @return RegisterForm
     */
    public function getRegisterForm() {
        return $this->registerForm;
    }

    /**
     * Fetches the login form
     * @return LoginForm
     */
    public function getLoginForm() {
        return $this->loginForm;
    }

    public function register(User $model, View $view) {
        $model->hashPassword();
        if ($this->repository->findOneBy('email', $model->getEmail()))
            return false;

        if (!$this->repository->fetchAll()->count()) {
            $_model = new AdminUser();
            $_model->populate($model->toArray());
            $model = $_model;
        }

        $model->setActive(Engine::getServer() === 'production');
        if ($this->repository->insert($model)->execute() && Engine::getServer() === 'production') {
            $configConfirm = Engine::getConfig('DsLive', 'register', 'confirm', false);
            if ($configConfirm && $configConfirm['active']) {
                $content = $view->getOutput($configConfirm['template']['module'], $configConfirm['template']['controller'], $configConfirm['template']['action'], array(md5($model->getEmail() . '&' . $model->getPassword())));
                $from = (!empty($configConfirm['from'])) ? $configConfirm['from'] : 'noreply@' . $_SERVER['HTTP_HOST'] . '.com';
                $email = new \Email($from, $model->getEmail());
                if (!$email->setHTML($content)->send(Engine::getConfig('app', 'name') . ': Confirm Registration')) {
                    return false;
                }
            }
        }
        return $this->flush();
    }

    public function login(User $model) {
        $model->hashPassword();
        $this->model = $this->repository->findOneWhere($model);
        if ($this->model) {
            $this->model->update();
            $this->repository->update($this->model, 'id');
        }
        return $this->model;
    }

}
