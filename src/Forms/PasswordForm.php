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
                'label' => 'Old Password'
            ),
        ));

        $this->add(array(
            'name' => 'new',
            'type' => 'password',
            'options' => array(
                'label' => 'New Password'
            ),
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
                'value' => 'Save'
            ),
            'attributes' => array(
                'class' => 'btn btn-success btn-large'
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
            ),
            'confirm' => array(
                'required' => true,
                'Match' => array(
                    'element' => 'new',
                    'message' => 'Confirm password'
                )
            ),
        );
    }

}
