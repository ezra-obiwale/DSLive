<?php

namespace DSLive\Forms;

use DScribe\Form\Form;

class UserForm extends Form {

    public function __construct() {
        parent::__construct('userForm');

        $user = class_exists('\In\Models\User') ? '\In\Models\User' : '\DSLive\Models\User';
        $this->setModel(new $user);

        $this->setAttributes(array(
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $this->add(array(
            'name' => 'MAX_FILE_SIZE',
            'type' => 'hidden',
            'attributes' => array(
                'value' => $this->getModel()->getMaxSize(),
            )
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => 'Email'
            ),
            'attributes' => array(
                'maxLength' => 100
            )
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'password',
            'options' => array(
                'label' => 'Password'
            ),
            'attributes' => array(
                'maxLength' => 50
            )
        ));

        $this->add(array(
            'name' => 'confirm',
            'type' => 'password',
            'options' => array(
                'label' => 'Repeat Password'
            ),
            'attributes' => array(
                'maxLength' => 50
            )
        ));

        $this->add(array(
            'name' => 'firstName',
            'type' => 'text',
            'options' => array(
                'label' => 'First Name'
            ),
            'attributes' => array(
                'maxLength' => 40
            )
        ));

        $this->add(array(
            'name' => 'lastName',
            'type' => 'text',
            'options' => array(
                'label' => 'Last Name'
            ),
            'attributes' => array(
                'maxLength' => 40
            )
        ));

        $this->add(array(
            'name' => 'phone',
            'type' => 'tel',
            'options' => array(
                'label' => 'Phone Number'
            ),
            'attributes' => array(
                'maxLength' => 20
            )
        ));

        $this->add(array(
            'name' => 'picture',
            'type' => 'file',
            'options' => array(
                'label' => 'Picture'
            ),
        ));

        $this->add(array(
            'name' => 'role',
            'type' => 'radio',
            'options' => array(
                'label' => 'Role',
                'default' => 'subscriber',
                'values' => array(
                    'Subscriber' => 'subscriber',
                    'Editor' => 'editor',
                    'Admin' => 'admin'
                )
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
                'class' => 'btn btn-success btn-large'
            )
        ));
    }

    public function getFilters() {
        return array(
            'email' => array(
                'required' => true,
                'Email' => array(),
            ),
            'password' => array(
                'required' => true,
            ),
            'confirm' => array(
                'Match' => array(
                    'element' => 'password'
                )
            ),
            'firstName' => array(
                'required' => true,
            ),
            'lastName' => array(
                'required' => true,
            ),
            'role' => array(
                'required' => true,
            ),
        );
    }

}
