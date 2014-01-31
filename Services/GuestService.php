<?php

namespace DSLive\Services;

use DBScribe\Util,
    DScribe\Core\AService,
    DScribe\Core\Engine,
    DScribe\View\View,
    DSLive\Forms\LoginForm,
    DSLive\Forms\RegisterForm,
    DSLive\Forms\ResetPasswordForm,
    DSLive\Models\AdminUser,
    DSLive\Models\Settings,
    DSLive\Models\User,
    Email,
    In\Models\User as IMU,
    Object,
    Session;

class GuestService extends AService {

    const NOTIFY_REG = 'registration';
    const NOTIFY_CONFIRM = 'confirmation';
    const NOTIFY_PSWD_RESET = 'password-reset';
    const NOTIFY_PSWD_RESET_SUCCESS = 'password-reset-success';

    protected $loginForm;
    protected $registerForm;
    protected $resetPasswordForm;
    protected $contactUsForm;
    protected $settingsRepository;

    protected function init() {
        $this->setModel(new IMU);
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

    public function contactUs(Object $data) {
        $email = new Email();
        $to = Engine::getDB()->table('settings')->select(array(array('key' => 'email')))->first()->value;
        $domain = Engine::getConfig('app', 'name');
        $email->addTo($to);
        $email->sendFrom(trim($data->email));
        $message = ucwords(trim($data->fullName)) . " sent you a message on " . Util::createTimestamp() . "\n\r\n\r" . trim($data->message);
//        $email->setText($message);
        $email->setHTML($message, array('autoSetText' => true));
        return $email->send($domain . ': ' . trim($data->title));
    }

    public function register(User $model, View $view, $setup = false, $flush = true) {
        $model->hashPassword();
        if ($this->repository->findOneBy('email', $model->getEmail()))
            return false;

        if (!$this->repository->fetchAll()->count()) {
            $_model = new AdminUser();
            $_model->populate($model->toArray());
            $model = $_model;
        }

        $model->setActive((Engine::getServer() !== 'development' || $setup));

        if ($this->repository->insert($model)->execute()) {
            if (!$this->sendEmail($model)) {
                return false;
            }
        }
        return ($flush) ? $this->flush() : true;
    }

    public function confirmRegistration($id, $email) {
        $user = $this->repository->findOneWhere(array(array('id' => $id, 'email' => $email)));
        if (NULL !== $user) {
            $user->setActive(TRUE);
            $this->repository->update($user)->execute();
            $this->flush();

            $this->sendEmail($user, self::NOTIFY_CONFIRM);
        }

        return $user;
    }

    public function resetPassword(IMU $model, $id, $reset) {
        if (!$reset) { //just requesting
            $model = $this->getRepository()->findOneBy('email', $model->getEmail());
            if (!$model) {
                return false;
            }
            $model->setReset(Util::createGUID());
            if ($this->repository->update($model)->execute()) {
                if (!$this->sendEmail($model, self::NOTIFY_PSWD_RESET)) {
                    return false;
                }
            }
        }
        else { //reseting password now
            $password = $model->getPassword();
            $model->setId($id)->setReset($reset)->setPassword(null);
            $model = $this->getRepository()->findOneWhere(array($model));
            if (!$model) {
                return false;
            }
            $model->setPassword($password)->hashPassword()->setReset('');
            if ($this->repository->update($model)->execute()) {
                if (!$this->sendEmail($model, self::NOTIFY_PSWD_RESET_SUCCESS)) {
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
            if (!$this->model->getActive())
                return false;
            $this->model->update();
            $this->repository->update($this->model, 'id');
        }
        return $this->model;
    }

    protected function sendEmail(User $user, $notifyType = self::NOTIFY_REG) {
        if (Engine::getServer() === 'development')
            return true;

        $email = new Email();
        $reg = Engine::getDB()->table('notification')->select(array(array('type' => 'email', 'name' => $notifyType)))->first();
        if (!$reg)
            return false;
        //@change that of userService->sendAccessCode() too
        $email->setHTML(nl2br($reg->message));
        $webMasterEmail = Engine::getDB()->table('settings')->select(array(array('key' => 'email')))->first();
        if ($webMasterEmail)
            $email->sendFrom($webMasterEmail->value);
        $email->addTo($user->getEmail());

        if ($reg->messageTitle) {
            $title = $reg->messageTitle;
        }
        else {
            $title = Engine::getConfig('app', 'name') . ' - ' . ucwords(str_replace(array('-', '_'), ' ', $notifyType));
        }

        if (!$email->send($title)) {
            return false;
        }
        return true;
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
        $beforeLogout = Session::fetch('bL');
        if (!$beforeLogout) {
            $beforeLogout = array();
        }
        $beforeLogout[$class][$method] = $methodParams;
        Session::save('bL', $beforeLogout);
    }

    public function doBeforeLogout() {
        foreach (Session::fetch('bL') as $class => $methodsArray) {
            foreach ($methodsArray as $method => $params) {
                call_user_func_array(array($class, $method), $params);
            }
        }
    }

}
