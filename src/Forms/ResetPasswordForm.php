<?php

namespace DSLive\Forms;

use DScribe\Form\Form,
    DSLive\Models\User;

class ResetPasswordForm extends Form {

    public function __construct() {
        parent::__construct('forgotPasswordForm');

        $this->setModel(new User);
        $this->setAttribute('method', 'POST');

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => 'Email'
            ),
            'attributes' => array(
                'autofocus' => 'autofocus',
                'required' => 'required',
            )
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'password',
            'options' => array(
                'label' => 'Password'
            ),
            'attributes' => array(
                'autofocus' => 'autofocus',
                'required' => 'required',
            )
        ));

        $this->add(array(
            'name' => 'confirm',
            'type' => 'password',
            'options' => array(
                'label' => 'Confirm Password'
            ),
            'attributes' => array(
                'required' => 'required',
            )
        ));

        $this->add(array(
            'name' => 'csrf',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'options' => array(
                'value' => 'Reset Password',
            ),
            'attributes' => array(
                'class' => 'btn btn-success'
            )
        ));
    }

    public function getFilters() {
        return array(
            'email' => array(
                'required' => true,
                'NotEmpty' => array(),
                'Email' => array(),
            ),
            'password' => array(
                'required' => true,
                'NotEmpty' => array(),
                'MinLength' => array(
                    'value' => 8
                ),
            ),
            'confirm' => array(
                'Match' => array(
                    'element' => 'password'
                ),
            ),
        );
    }

}
