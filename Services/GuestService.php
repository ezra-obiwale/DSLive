<?php

namespace DSLive\Services;

use DScribe\Core\AService,
    DScribe\Core\Engine,
    DScribe\Core\Repository,
    DScribe\View\View,
    DSLive\Forms\LoginForm,
    DSLive\Forms\RegisterForm,
    DSLive\Forms\ResetPasswordForm,
    DSLive\Models\AdminUser,
    DSLive\Models\Settings,
    DSLive\Models\User,
    Email,
    Util;

class GuestService extends AService {

    const NOTIFY_REG = 'registration';
    const NOTIFY_CONFIRM = 'confirmation';
    const NOTIFY_PSWD_RESET = 'password-reset';

    protected $loginForm;
    protected $registerForm;
    protected $resetPasswordForm;
    protected $contactUsForm;
    private $settingsRepository;

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
            'resetPasswordForm' => array(
                'class' => 'DSLive\Forms\ResetPasswordForm'
            ),
            'contactUsForm' => array(
                'class' => 'DSLive\Forms\ContactUsForm'
            ),
            'settingsRepository' => array(
                'class' => 'DScribe\Core\Repository',
                'params' => array(new Settings())
            )
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

    /**
     * Fetches the reset password form
     * @return ResetPasswordForm
     */
    public function getResetPasswordForm() {
        return $this->resetPasswordForm;
    }

    public function getContactUsForm() {
        return $this->contactUsForm;
    }
    
    public function contactUs(\Object $data) {
        // @todo send mail to admin
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
                $email = new Email($from, $model->getEmail());
                if (!$email->setHTML($content)->send(Engine::getConfig('app', 'name') . ': Confirm Registration')) {
                    return false;
                }
            }
            else {
                return false;
            }
        }
        return $this->flush();
    }

    public function confirmRegistration($id, $email) {
        $user = $this->repository->findOneWhere(array(array('id' => $id, 'email' => $email)));
        if (NULL !== $user) {
            $user->setActive(TRUE);
            $this->repository->update($user);
            $this->flush();

            $this->sendEmail(self::NOTIFY_CONFIRM, $user);
        }

        return $user;
    }

    public function resetPassword(User $model, $id, $password) {
        if (!$password) {
            $model->setPassword($model->hashPassword(Util::randomPassword(12)));
            if ($this->repository->update($model, 'email')->execute() && Engine::getServer() === 'production') {
                $configReset = Engine::getConfig('DsLive', 'register', 'reset', false);
                if ($configReset && $configReset['active']) {
                    $content = $view->getOutput($configReset['template']['module'], $configReset['template']['controller'], $configReset['template']['action'], array(md5($model->getEmail() . '&' . $model->getPassword())));
                    $from = (!empty($configReset['from'])) ? $configReset['from'] : 'noreply@' . $_SERVER['HTTP_HOST'] . '.com';
                    $email = new Email($from, $model->getEmail());
                    if (!$email->setHTML($content)->send(Engine::getConfig('app', 'name') . ': Confirm Registration')) {
                        return false;
                    }
                }
                else {
                    return false;
                }
            }
        }
        else {
            $model->setId($id);
            $this->repository->update($model);
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

    private function sendEmail(User $user, $notifyType = self::NOTIFY_REG) {
        $email = new \Email();
        $webMasterName = Engine::getConfig('app', 'webmaster', 'name', false);
        $webMasterEmail = Engine::getConfig('app', 'webmaster', 'email');
        $email->sendFrom((isset($webMasterName)) ? $webMasterName . "<" . $webMasterEmail . ">" : $webMasterEmail);
        $email->addTo($user->getEmail());
        $email->setHTML($this->getEmailContent($notifyType));
        if (!$email->setHTML($content)->send(Engine::getConfig('app', 'name') . ': Confirm Registration')) {
            return false;
        }
    }

    private function getEmailContent($notifyType) {
        $setting = $this->settingsRepo->findOneBy('key', 'notify-' . $notifyType);
        $defaultNotifyMessageFunction = 'get' . \Util::hyphenToCamel($notifyType) . 'Message';
        return ($setting) ? $setting->getValue() : $this->{$defaultNotifyMessageFunction}();
    }

    protected function getRegistrationMessage(User $user) {
        ob_start();
        $confirmationLink = $_SERVER['SERVER_NAME'] . '/guest/index/confirm-registration/' . urlencode($user->getId()) . '/' . urlencode($user->getEmail());
        ?>
        <h1><?= Engine::getConfig('app', 'name') ?> - Registration</h1>
        <p>Hi,</p>
        <p>
            Your registration with <b><?= Engine::getConfig('app', 'name') ?></b> is successful. 
            To activate your account, navigate to <a href='<?= $confirmationLink ?>'><?= $confirmationLink ?></a>
        </p>
        <?php
        return ob_get_clean();
    }

    protected function getConfirmationMessage() {
        ob_start();
        $loginLink = $_SERVER['SERVER_NAME'] . '/guest/index/login';
        ?>
        <h1><?= Engine::getConfig('app', 'name') ?> - Registration Confirmed</h1>
        <p>Hi,</p>
        <p>
            Your registration with <b><?= Engine::getConfig('app', 'name') ?></b> has been successfully confirmed. 
            To login into your account, navigate to <a href='<?= $loginLink ?>'><?= $loginLink ?></a>
        </p>
        <?php
        return ob_get_clean();
    }

    protected function getPasswordResetMessage(User $user) {
        ob_start();
        $resetLink = $_SERVER['SERVER_NAME'] . '/guest/index/reset-password/' . urlencode($user->getId()) . '/' . urlencode($user->getId());
        ?>
        <h1><?= Engine::getConfig('app', 'name') ?> - Registration Confirmed</h1>
        <p>Hi,</p>
        <p>
            You requested a password reset. Please navigate to <a href='<?= $resetLink ?>'><?= $resetLink ?></a>
        </p>
        <?php
        return ob_get_clean();
    }

    public static function beforeLogout($class, $method, array $methodParams = array()) {
        $beforeLogout = \Session::fetch('bL');
        if (!$beforeLogout) {
            $beforeLogout = array();
        }
        $beforeLogout[$class][$method] = $methodParams;
        \Session::save('bL', $beforeLogout);
    }

    public function doBeforeLogout() {
        foreach (\Session::fetch('bL') as $class => $methodsArray) {
            foreach ($methodsArray as $method => $params) {
                call_user_func_array(array($class, $method), $params);
            }
        }
    }

}
