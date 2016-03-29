<?php

namespace DSLive\Services;

use DBScribe\Util,
    DScribe\Core\AService,
    DScribe\Form\Form,
    DScribe\View\View,
    DSLive\Forms\ContactUsForm,
    DSLive\Forms\LoginForm,
    DSLive\Forms\RegisterForm,
    DSLive\Forms\ResetPasswordForm,
    DSLive\Models\Settings,
    DSLive\Models\User,
    Email,
    Exception,
    Object,
    Table;

abstract class GuestService extends AService {

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
        $this->setModel(new User);
    }

    public function getSettingsRepository() {
        if ($this->settingsRepository)
                $this->settingsRepository = new \DBScribe\Repository(new Settings());

        return $this->settingsRepository;
    }

    /**
     * Fetches the registration form
     * @return RegisterForm
     */
    public function getRegisterForm() {
        if (!$this->registerForm) $this->registerForm = new RegisterForm ();

        return $this->registerForm;
    }

    /**
     * Fetches the login form
     * @return LoginForm
     */
    public function getLoginForm($passwordResetLink = null) {
        if (!$this->loginForm)
                $this->loginForm = new LoginForm($passwordResetLink);
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
        if (!$this->contactUsForm) $this->contactUsForm = new ContactUsForm();
        return $this->contactUsForm;
    }

    public function contactUs(Object $data, $to = null) {
        $email = new Email();

        $to = engineGet('config', 'app', 'webmaster');
        $domain = engineGet('config', 'app', 'name');
        $email->addTo($to);
        $email->sendFrom(trim($data->email));

        Table::init(array(
            'border' => 0,
            'cellspacing' => 0,
            'cellpadding' => 6,
            'style' => 'margin:10px 15%;width:70%;background-color:#eee;border:3px double'
        ));
        Table::newRow();
        Table::addRowData(trim($data->fullName) . " sent you a message on " .
                Util::createTimestamp() . ' from <a href="' .
                engineGet('config', 'app', 'domain') . '">' .
                engineGet('config', 'app', 'name') . '</a><hr />',
                array(
            'style' => 'font-size:larger;font-weight:bolder'
        ));
        Table::newRow();
        Table::addRowData(nl2br($data->message));
        $email->setHTML(Table::render(), array('autoSetText' => false));
        return $email->send($domain . ': ' . trim($data->title));
    }

    /**
     * 
     * @param View $view
     * @param array $controllerPath Array with keys module and controller
     * @param Form $form
     * @param boolean $setup
     * @param boolean $flush
     * @return boolean
     */
    public function register(View $view, $controllerPath, Form $form,
            $setup = false, $sendMail = true, $flush = true) {
        $model = $form->getModel();
        $model->hashPassword();
        if ($this->repository->findOneBy('email', $model->getEmail())) {
            $this->addErrors('User <a title="send mail to account owner" href="mailto:' . $model->getEmail() . '">' . $model->getEmail() . '</a> already exists');
            return false;
        }

        if ($setup) $model->setRole('admin')->setSuper(true);
        
        $model->setActive(engineGet('server') === 'development' || $setup);
        return $this->registerUser($model, $view, $controllerPath, $setup,
                        $sendMail, $flush);
    }

    private function registerUser(User $user, View $view, $controllerPath,
            $setup, $sendMail, $flush) {
        if ($this->repository->insert($user)->execute()) {
            if ($sendMail && engineGet('server') !== 'development' && !$this->sendEmail($user)) {
                $this->addErrors('Confirmation email not sent. <a href="' .
                                $view->url($controllerPath['module'],
                                        $controllerPath['controller'],
                                        'resend-confirmation',
                                        array($user->getId()))) .
                        '">Click here to resend your confirmation email</a>.';
            }

            if ($setup) $this->setup();
        }
        return ($flush) ? $this->flush() : $user;
    }

    public function confirmRegistration($id, $email) {
        $user = $this->repository->findOneWhere(array(array(
                'id' => $id,
                'email' => str_replace(':', '.', $email)
        )));
        if (NULL !== $user) {
            if ($user->getActive()) {
                $this->addErrors('User is already active');
                return false;
            }
            $user->setActive(TRUE);
            $this->repository->update($user)->execute();
            $this->flush();

            $this->sendEmail($user, self::NOTIFY_CONFIRM);
        }

        return $user;
    }

    public function resetPassword(User $model, $id, $reset) {
        if (!$reset) { //just requesting
            $model = $this->getRepository()->findOneBy('email',
                    $model->getEmail());
            if (!$model) {
                $this->addErrors('User account does not exist');
                return false;
            }
            $model->setReset(Util::createGUID());
            if ($this->repository->update($model)->execute()) {
                if (!$this->sendEmail($model, self::NOTIFY_PSWD_RESET)) {
                    $this->addErrors('Email notification failed to send')
                            ->addErrors('Please do a password reset again');
                }
            }
        }
        else { //reseting password now
            $password = $model->getPassword();
            $model->setId($id)->setPassword(null);
            $model = $this->getRepository()->findOneWhere(array($model));
            if (!$model) {
                $this->addErrors('User account does not exist');
                return false;
            }
            else if ($model->getReset() !== $reset) {
                $this->addErrors('Invalid action');
                return false;
            }
            $model->setPassword($password)->hashPassword()->setReset('');
            if ($this->repository->update($model)->execute()) {
                if (!$this->sendEmail($model, self::NOTIFY_PSWD_RESET_SUCCESS)) {
                    $this->addErrors('Email notification failed to send');
                }
            }
        }
        return $this->flush();
    }

    public function login(User $model) {
        $this->model = $this->repository->findOneBy('email', $model->getEmail());
        if ($this->model) {
            if (!$this->model->getActive()) {
                $this->addErrors('User account is not yet active')
                        ->addErrors('Please click on the confirmation link sent to your email account');
                return false;
            }
            else if (!$this->model->verifyPassword($model->getPassword())) {
                return false;
            }

            $this->model->update();
            $this->repository->update($this->model)->execute();
            $this->flush();
            $this->model->postFetch();
        }
        else {
            $this->addErrors('User account does not exist');
        }
        return $this->model;
    }

    public function mediaSignup(\Object $data, View $view, $controllerPath,
            $sendMail = true, $flush = true) {
        $model = $this->repository->findOneBy('email', $data->email);
        if ($model) {
            $this->addErrors('User already exists.');
            return false;
        }
        else {
            $this->model->populate($data->toArray());
            $this->model->setRole('user')->setActive(false);
            return $this->registerUser($this->model, $view, $controllerPath,
                            false, $sendMail, $flush);
        }
    }

    public function mediaLogin(\Object $data) {
        $this->model = $this->repository->findOneBy('email', $data->email);
        if ($this->model) {
            if (!$this->model->getActive()) {
                $this->addErrors('User account is not yet active')
                        ->addErrors('Please click on the confirmation link sent to your email account');
                return false;
            }

            $this->model->update();
            $this->repository->update($this->model)->execute();
            $this->flush();
            $this->model->postFetch();
        }
        else {
            $this->addErrors('User account does not exist');
        }
        return $this->model;
    }

    public function getNotificationService() {
        return new NotificationService;
    }

    public function sendEmail(User $user, $notifyType = self::NOTIFY_REG) {
        if (engineGet('server') === 'development') return true;

        $email = new Email();
        $notification = $this->getNotificationService()->getRepository()
                ->findOneWhere(array(array('type' => 'email', 'name' => $notifyType)));
        if (!$notification) // if notification type message is not available, ignore sending
                return true;

        $parsedEmail = str_replace('.', ':', $user->getEmail());
        $msg = str_replace('{email}', $parsedEmail, $notification->getMessage());
        $body = $user->parseString($msg);
        $view = new View();
        $links = array(
            '{confirmationLink}' => engineGet('config', 'app', 'domain') . $view->url($this->getModule(),
                    $this->getClassName(), 'confirm-registration',
                    array($user->getId(), $user->getEmail())),
            '{loginLink}' => engineGet('config', 'app', 'domain') . $view->url($this->getModule(),
                    $this->getClassName(), 'login'),
            '{passwordResetLink}' => engineGet('config', 'app', 'domain') . $view->url($this->getModule(),
                    $this->getClassName(), 'reset-password',
                    array($user->getId(), $user->getReset())),
        );

        $body = str_replace(array_keys($links), array_values($links), $body);
        $email->setHTML($body, array('autoSetText' => false));
        $webMasterEmail = engineGet('config', 'app', 'webmaster', false);
        if ($webMasterEmail)
                $email->sendFrom($webMasterEmail, $notification->getGetReplies());
        else
                $email->sendFrom('no-reply@' . str_replace(array('http://', 'https://'),
                            '', engineGet('config', 'app', 'domain')));

        $email->addTo($user->getEmail());

        if ($notification->getMessageTitle()) {
            $title = $notification->getMessageTitle();
        }
        else {
            $title = engineGet('config', 'app', 'name') . ' - ' . ucwords(str_replace(array(
                        '-', '_'), ' ', $notifyType));
        }

        return $email->send($title);
    }

    /**
     * Do something after setting up the admin
     */
    abstract public function setup();

    /**
     * Adds an error to the current operation
     * @param string|array $error
     * @return GuestController
     */
    final public function addErrors($error) {
        if (is_string($error)) $this->errors[] = $error;
        else if (is_array($error))
                $this->errors = array_merge($this->errors, $error);
        else throw new Exception('Error must be of type string or array');

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
        return $this->errors ? '<li>' . join('</li><li>', $this->errors) . '</li>'
                    : null;
    }

}
