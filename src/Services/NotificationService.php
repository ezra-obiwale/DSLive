<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace dsLive\Services;

use dbScribe\Util,
    dsLive\Models\Notification,
    Table;

/**
 * Description of NotificationService
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
class NotificationService extends SuperService {

    const NOTIFY_REG = 'registration';
    const NOTIFY_CONFIRM = 'confirmation';
    const NOTIFY_PSWD_RESET = 'password-reset';
    const NOTIFY_PSWD_RESET_SUCCESS = 'password-reset-success';

    protected function init() {
        parent::init();
        $this->setModel(new Notification);

        if (!$this->repository->count()) {
            $registration = array(
                'id' => Util::createGUID(),
                'name' => 'registration',
                'type' => 'email',
                'required' => true,
                'message' => $this->createRegistrationMessage()
            );
            $confirmation = array(
                'id' => Util::createGUID(),
                'name' => 'confirmation',
                'type' => 'email',
                'required' => true,
                'message' => $this->createConfirmationMessage()
            );
            $passwordReset = array(
                'id' => Util::createGUID(),
                'name' => 'password-reset',
                'type' => 'email',
                'required' => true,
                'message' => $this->createPasswordResetMessage()
            );
            $passwordResetSuccess = array(
                'id' => Util::createGUID(),
                'name' => 'password-reset-success',
                'type' => 'email',
                'required' => true,
                'message' => $this->createPasswordResetSuccessMessage()
            );

            $this->repository->insert(array($registration, $confirmation, $passwordReset, $passwordResetSuccess))->execute();
            $this->flush();
        }
    }

    /**
     * Creates default message to send when a user registers
     * @return string
     */
    public function createRegistrationMessage() {
        Table::init(array(
            'border' => '0',
            'cellspacing' => '0',
            'cellpadding' => '6',
            'style' => 'margin:10px 15%;width:70%;border:3px double;background-color:#eee;'
        ));
        Table::newRow();
        Table::addRowData('Dear User,', array('colspan' => 1));
        Table::newRow();
        Table::addRowData('You have successfully registered with ' . engineGet('config', 'app', 'name') .
                '. Please click on the link below to confirm your registration and activate your account.', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('<a href="{confirmationLink}">{confirmationLink}</a>', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('You may copy and and paste the link into your browser address if it is not clickable.', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('&nbsp;', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('Your account confirmation and activation link once again is:', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('<a href="{confirmationLink}">{confirmationLink}</a>', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('&nbsp;', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('Thank you', array('colspan' => 2));
        Table::newRow();
        Table::addRowData(engineGet('config', 'app', 'name'), array('colspan' => 2));

        return Table::render();
    }

    /**
     * Creates default message to send when a user confirms his account
     * @return string
     */
    public function createConfirmationMessage() {
        Table::init(array(
            'border' => '0',
            'cellspacing' => '0',
            'cellpadding' => '6',
            'style' => 'margin:10px 15%;width:70%;border:3px double;background-color:#eee;'
        ));
        Table::newRow();
        Table::addRowData('Dear User,', array('colspan' => 1));
        Table::newRow();
        Table::addRowData('You account with ' . engineGet('config', 'app', 'name') .
                ' has been successfully confirmed and activated. <a href="{loginLink}">Click here to login now</a>', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('You may copy and and paste the link below into your browser address if the one above is not clickable.', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('{loginLink}', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('&nbsp;', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('Thank you', array('colspan' => 2));
        Table::newRow();
        Table::addRowData(engineGet('config', 'app', 'name'), array('colspan' => 2));

        return Table::render();
    }

    /**
     * Creates default message to send when a user resets his password
     * @return string
     */
    public function createPasswordResetMessage() {
        Table::init(array(
            'border' => '0',
            'cellspacing' => '0',
            'cellpadding' => '6',
            'style' => 'margin:10px 15%;width:70%;border:3px double;background-color:#eee;'
        ));
        Table::newRow();
        Table::addRowData('Dear User,', array('colspan' => 1));
        Table::newRow();
        Table::addRowData('You have requested for a password reset to your account with ' . engineGet('config', 'app', 'name') .
                '. Please click on the link below to reset your account password.', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('<a href="{passwordResetLink}">{passwordResetLink}</a>', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('You may copy and and paste the link into your browser address if it is not clickable.', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('&nbsp;', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('PS: If you have not requested a password reset, please ignore this message.', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('Thank you', array('colspan' => 2));
        Table::newRow();
        Table::addRowData(engineGet('config', 'app', 'name'), array('colspan' => 2));

        return Table::render();
    }

    /**
     * Creates default messate to send when a user successfully resets his password
     * @return string
     */
    public function createPasswordResetSuccessMessage() {
        Table::init(array(
            'border' => '0',
            'cellspacing' => '0',
            'cellpadding' => '6',
            'style' => 'margin:10px 15%;width:70%;border:3px double;background-color:#eee;'
        ));
        Table::newRow();
        Table::addRowData('Dear User,', array('colspan' => 1));
        Table::newRow();
        Table::addRowData('You have successfully reset your password to your '
                . 'account with ' .
                engineGet('config', 'app', 'name'), array('colspan' => 2));
        Table::newRow();
        Table::addRowData('If you have not requested a password reset your '
                . 'account may have been hacked. Follow the steps below to '
                . 'recover your account and make hacking it a lot tougher.', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('<ul>'
                . '<li><strong>Change your email password</strong>. This is necessary since the '
                . 'hacker couldn\'t have successfully changed your password '
                . 'without access to your email to confirm the change</li>'
                . '<li>Click the password reset link below</li>'
                . '<li>Change your password to a strong password containing a combination of at least 3 of:'
                . '<ul>'
                . '<li>Small letters [a - z]</li>'
                . '<li>Capital letters [A - Z]</li>'
                . '<li>Numbers [0 - 9]</li>'
                . '<li>Symbols [@.-%$ etc]</li>'
                . '</ul>'
                . '</li>'
                . '</ul>', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('Below is the said password reset link. Click on it to reset your password', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('<a href="{passwordResetLink}">{passwordResetLink}</a>', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('You may copy and and paste the link into your browser'
                . ' address if it is not clickable.', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('&nbsp;', array('colspan' => 2));
        Table::newRow();
        Table::addRowData('Thank you', array('colspan' => 2));
        Table::newRow();
        Table::addRowData(engineGet('config', 'app', 'name'), array('colspan' => 2));

        return Table::render();
    }

}
