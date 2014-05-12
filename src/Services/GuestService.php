<?php

namespace DSLive\Services;

use DBScribe\Util,
    DScribe\Core\AService,
    DScribe\Core\Engine,
    DScribe\View\View,
    DSLive\Forms\ContactUsForm,
    DSLive\Forms\LoginForm,
    DSLive\Forms\RegisterForm,
    DSLive\Forms\ResetPasswordForm,
    DSLive\Models\AdminUser,
    DSLive\Models\Settings,
    DSLive\Models\User,
    DSLive\Stdlib\Util as DSU,
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

    public function getSettingsRepository() {
        if ($this->settingsRepository)
            $this->settingsRepository = new \DScribe\Core\Repository(new Settings());

        return $this->settingsRepository;
    }

    /**
     * Fetches the registration form
     * @return RegisterForm
     */
    public function getRegisterForm() {
        if (!$this->registerForm)
            $this->registerForm = new RegisterForm ();

        return $this->registerForm;
    }

    /**
     * Fetches the login form
     * @return LoginForm
     */
    public function getLoginForm() {
        if (!$this->loginForm)
            $this->loginForm = new LoginForm();
        return $this->loginForm;
    }

    /**
     * Fetches the reset password form
     * @return ResetPasswordForm
     */
    public function getResetPasswordForm() {
        if (!$this->resetPasswordForm)
            $this->resetPasswordForm = new ResetPasswordForm();
        return $this->resetPasswordForm;
    }

    public function getContactUsForm() {
        if (!$this->contactUsForm)
            $this->contactUsForm = new ContactUsForm();
        return $this->contactUsForm;
    }

    public function contactUs(Object $data, $to = null) {
        $email = new Email();

        $to = ($to !== null) ? $to :
                Engine::getDB()->table('settings')
                        ->select(array(array('key' => 'email')))
                        ->first()->value;
        $domain = Engine::getConfig('app', 'name');
        $email->addTo($to);
        $email->sendFrom(trim($data->email));
        $message = ucwords(trim($data->fullName)) . " sent you a message on " . Util::createTimestamp() . "\n\r\n\r" . trim($data->message);
//        $email->setText($message);
        $email->setHTML(nl2br($message), array('autoSetText' => false));
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

    private function sendEmail(User $user, $notifyType = self::NOTIFY_REG) {
        if (Engine::getServer() === 'development')
            return true;

        $email = new Email();
        $reg = Engine::getDB()->table('notification')->select(array(array('type' => 'email', 'name' => $notifyType)))->first();
        if (!$reg)
            return false;
        //@change that of userService->sendAccessCode() too
//        $email->setHTML($reg->message, array('autoSetText' => true));
        $email->setText(DSU::prepareMessage($reg->message, $user));
        $webMasterEmail = Engine::getDB()->table('settings')->select(array(array('key' => 'email')))->first();
        if (!$webMasterEmail)
            return false;
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

    public function getErrorMessage($code) {
        $codes = array(
            400 => array('400 Bad Request', 'The request cannot be fulfilled due to bad syntax.'),
            401 => array('401 Login Error', 'It appears that the password and/or user-name you entered was incorrect.'),
            403 => array('403 Forbidden', 'Sorry, you do not have access to this resource.'),
            404 => array('404 Missing', 'We\'re sorry, but the page you\'re looking for is missing, hiding, or maybe we moved it somewhere else and forgot to tell you.'),
            405 => array('405 Method Not Allowed', 'The method specified in the Request-Line is not allowed for the specified resource.'),
            408 => array('408 Request Timeout', 'Your browser failed to send a request in the time allowed by the server.'),
            414 => array('414 URL To Long', 'The URL you entered is longer than the maximum length.'),
            500 => array('500 Internal Server Error', 'The request was unsuccessful due to an unexpected condition encountered by the server.'),
            502 => array('502 Bad Gateway', 'The server received an invalid response from the upstream server while trying to fulfill the request.'),
            504 => array('504 Gateway Timeout', 'The upstream server failed to send a request in the time allowed by the server.'),
        );

        return (isset($codes[$code])) ? $codes[$code] : array('Unknown Error', 'Your request generated an unknown error');
    }

}
