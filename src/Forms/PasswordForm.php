<?php

namespace DSLive\Forms;

use DScribe\Form\Form;

class PasswordForm extends Form {

    public function __construct() {
        parent::__construct('passwordForm');

        $this->setAttribute('method', 'post');

        $this->add(array(
            'name' => 'old',
            'type' => 'password',
            'options' => array(
                'label' => 'Current Password',
            ),
            'attributes' => array(
                'required' => 'required',                
            )
        ));

        $this->add(array(
            'name' => 'new',
            'type' => 'password',
            'options' => array(
                'label' => 'New Password',
                'inlineInfo' => 'Minimum 8 characters',
            ),
            'attributes' => array(
                'required' => 'required',
				'min' => 8,
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
                'value' => 'Save'
            ),
            'attributes' => array(
                'class' => 'btn btn-success'
            ),
        ));
    }

    public function getFilters() {
        return array(
            'old' => array(
                'required' => true,
            ),
            'new' => array(
                'required' => true,
                'NotMatch' => array(
                    'element' => 'old',
                    'message' => 'New and old passwords cannot be the same'
                ),
                'minLength' => array(
                    'value' => 8
                )
            ),
            'confirm' => array(
                'required' => true,
                'Match' => array(
                    'element' => 'new',
                    'message' => 'Password mismatch'
                )
            ),
        );
    }

}
