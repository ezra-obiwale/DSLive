<?php

namespace DSLive\Forms;

use DScribe\Form\Form,
    DSLive\Models\User;

class LoginForm extends Form {

    public function __construct($passwordResetLink = null) {
        parent::__construct('loginForm');

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
                'required' => 'required',
            )
        ));

        $this->add(array(
            'name' => 'csrf',
            'type' => 'hidden'
        ));
		$bI = $passwordResetLink ? 
			'<a href="' . $passwordResetLink . '" class="badge">Reset Password</a>' : null;
        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'options' => array(
                'value' => 'Login',
                'containerAttrs' => array(
                    'class' => 'col-md-8 col-md-offset-3'
                ),
                'blockInfo' => $bI
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
            ),
        );
    }

}
