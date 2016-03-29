<?php

namespace dsLive\Forms;

use dScribe\Form\Form,
    dsLive\Models\User;

class RegisterForm extends Form {

    public function __construct() {
        parent::__construct('registerForm');

        $this->setModel(new User());

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
                    'text' => 'Password',
                    'attrs' => array(
                        'class' => 'col-md-3',
                    ),
                ),
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
            ),
            'attributes' => array(
                'minLength' => 8,
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
            'name' => 'firstName',
            'type' => 'text',
            'options' => array(
                'label' => array(
                    'text' => 'First Name',
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
            'name' => 'lastName',
            'type' => 'text',
            'options' => array(
                'label' => array(
                    'text' => 'Last Name',
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
                'value' => 'Register',
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
            'firstName' => array(
                'required' => true,
                'NotEmpty' => array(),
            ),
            'lastName' => array(
                'required' => true,
                'NotEmpty' => array(),
            ),
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
