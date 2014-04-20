<?php

namespace DSLive\Forms;

use DScribe\Form\Form,
    DSLive\Models\SubscriberUser;

class RegisterForm extends Form {

    public function __construct() {
        parent::__construct('registerForm');

        $this->setModel(new SubscriberUser());

        $this->setAttribute('method', 'POST');

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => 'Email'
            ),
            'attributes' => array(
                'autofocus' => 'autofocus'
            )
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'password',
            'options' => array(
                'label' => 'Password'
            ),
            'attributes' => array(
                'minLength' => 8
            )
        ));

        $this->add(array(
            'name' => 'confirm',
            'type' => 'password',
            'options' => array(
                'label' => 'Confirm Password'
            ),
        ));

        $this->add(array(
            'name' => 'csrf',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'options' => array(
                'value' => 'Register'
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
                )
            ),
            'confirm' => array(
                'required' => true,
                'Match' => array(
                    'element' => 'password'
                )
            ),
        );
    }

}
