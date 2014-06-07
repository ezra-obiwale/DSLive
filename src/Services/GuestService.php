<?php

namespace DSLive\Services;

use DBScribe\Util,
    DScribe\Core\AService,
    DScribe\Core\Engine,
    DScribe\Core\Repository,
    DScribe\Form\Form,
    DScribe\View\View,
    DSLive\Controllers\GuestController,
    DSLive\Forms\ContactUsForm,
    DSLive\Forms\LoginForm,
    DSLive\Forms\RegisterForm,
    DSLive\Forms\ResetPasswordForm,
    DSLive\Models\AdminUser,
    DSLive\Models\Settings,
    DSLive\Models\User,
    DSLive\Stdlib\Util as DSU,
    Email,
    Exception,
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

    /**
     * @var array
     */
    private $errors;

    protected function init() {
        $this->setModel(new IMU);
    }

    public function getSettingsRepository() {
        if ($this->settingsRepository)
            $this->settingsRepository = new Repository(new Settings());

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

    /**
     * 
     * @param \DScribe\View\View $view
     * @param array $controllerPath Array with keys module and controller
     * @param \DScribe\Form\Form $form
     * @param boolean $setup
     * @param boolean $flush
     * @return boolean
     */
    public function register(View $view, $controllerPath, Form $form, $setup = false, $flush = true) {
        $model = $form->getModel();
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
            if (Engine::getServer() === 'production' && !$this->sendEmail($model)) {
                $this->addErrors('Confirmation email not sent. <a href="' .
                                $view->url($controllerPath['module'], $controllerPath['controller'], 'resend-confirmation', array($model->getId()))) .
                        '">Click here to resend your confirmation email</a>.';
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
            $this->repository->update($this->model)->execute();
        }
        return $this->model;
    }

    public function sendEmail(User $user, $notifyType = self::NOTIFY_REG) {
        if (Engine::getServer() === 'development')
            return true;

        $email = new Email();
        $reg = Engine::getDB()->table('notification')->select(array(array('type' => 'email', 'name' => $notifyType)))->first();
        if (!$reg) // if notification type message is not available, ignore sending
            return true;
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

    /**
     * Adds an error to the current operation
     * @param string|array $error
     * @return GuestController
     */
    final public function addErrors($error) {
        if (is_string($error))
            $this->errors[] = $error;
        else if (is_array($error))
            $this->errors = array_merge($this->errors, $error);
        else
            throw new Exception('Error must be of type string or array');

        return $this;
    }

    /**
     * Fetches an array of all errors
     * @return array
     */
    final public function getErrors() {
        return (is_array($this->errors)) ? $this->errors : array();
    }

    /**
     * Surrounds each error in an li tag
     * @return string
     */
    final public function prepareErrors() {
        return '<li>' . join('</li><li>', $this->errors) . '</li>';
    }

}
