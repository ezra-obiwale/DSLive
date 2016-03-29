<?php

namespace dsLive\Forms;

use dScribe\Form\Form,
    dsLive\Models\User;

class ResetPasswordForm extends Form {

    public function __construct() {
        parent::__construct('forgotPasswordForm');

        $this->setModel(new User);
        $this->setAttribute('method', 'POST');

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => array(
                    'text' => 'Email',
                    'attrs' => array(
                        'class' => 'col-md-3',
                    ),
                ),
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
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
                'label' => array(
                    'text' => 'New Password',
                    'attrs' => array(
                        'class' => 'col-md-3',
                    ),
                ),
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
                'blockInfo' => 'Minimum 8 characters',
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
                'label' => array(
                    'text' => 'Repeat New Password',
                    'attrs' => array(
                        'class' => 'col-md-3',
                    ),
                ),
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
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
                'containerAttrs' => array(
                    'class' => 'col-md-8 col-md-offset-3'
                ),
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
