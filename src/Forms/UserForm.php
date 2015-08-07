<?php

namespace DSLive\Forms;

use \DScribe\Form\Form,
    \DSLive\Models\User;

class UserForm extends Form {

    public function __construct($name = 'userForm', User $user = null) {
        parent::__construct($name);

        $user = ($user === null) ? new User : $user;
        $this->setModel($user);

        $this->setAttributes(array(
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $this->add(array(
            'name' => 'MAX_FILE_SIZE',
            'type' => 'hidden',
            'attributes' => array(
                'value' => $user->getMaxSize(),
            )
        ));

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
                'maxlength' => 100,
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
                'maxlength' => 50,
                'required' => 'required',
            )
        ));

        $this->add(array(
            'name' => 'confirm',
            'type' => 'password',
            'options' => array(
                'label' => array(
                    'text' => 'Repeat Password',
                    'attrs' => array(
                        'class' => 'col-md-3',
                    ),
                ),
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
            ),
            'attributes' => array(
                'maxlength' => 50,
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
                'maxlength' => 40,
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
                'maxlength' => 40,
                'required' => 'required',
            )
        ));

        $this->add(array(
            'name' => 'picture',
            'type' => 'file',
            'options' => array(
                'label' => array(
                    'text' => 'Picture',
                    'attrs' => array(
                        'class' => 'col-md-3',
                    ),
                ),
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
            ),
        ));

        $this->add(array(
            'name' => 'role',
            'type' => 'radio',
            'options' => array(
                'label' => array(
                    'text' => 'Role',
                    'attrs' => array(
                        'class' => 'col-md-3',
                    ),
                ),
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
                'default' => 'subscriber',
                'values' => array(
                    'Subscriber' => 'subscriber',
                    'Editor' => 'editor',
                    'Admin' => 'admin'
                ),
            ),
            'attributes' => array(
                'required' => 'required',
            )
        ));

        $this->add(array(
            'name' => 'active',
            'type' => 'select',
            'options' => array(
                'label' => array(
                    'text' => 'Status',
                    'attrs' => array(
                        'class' => 'col-md-3',
                    ),
                ),
                'containerAttrs' => array(
                    'class' => 'col-md-8'
                ),
                'values' => array(
                    'Inactive' => 0,
                    'Active' => 1
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
                'value' => 'Save',
                'containerAttrs' => array(
                    'class' => 'col-md-8 col-md-offset-3'
                ),
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
