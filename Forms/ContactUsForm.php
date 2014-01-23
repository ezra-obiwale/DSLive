<?php

namespace DSLive\Forms;

use DScribe\Form\Form;

class ContactUsForm extends Form {

    public function __construct() {
        parent::__construct('contactUsForm');

        $this->setAttribute('method', 'POST');

        $this->add(array(
            'name' => 'fullName',
            'type' => 'text',
            'options' => array(
                'label' => 'Full Name',
            ),
            'attributes' => array(
                'autofocus' => 'autofocus',
                'class' => 'span8'
            )
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => 'Email',
            ),
            'attributes' => array(
                'class' => 'span8'
            )
        ));

        $this->add(array(
            'name' => 'title',
            'type' => 'text',
            'options' => array(
                'label' => 'Title',
            ),
            'attributes' => array(
                'class' => 'span8'
            )
        ));

        $this->add(array(
            'name' => 'message',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Message'
            ),
            'attributes' => array(
                'rows' => 8,
                'class' => 'span8'
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
                'value' => 'Login',
            ),
            'attributes' => array(
                'class' => 'btn btn-success'
            )
        ));
    }

    public function filters() {
        return array(
            'fullName' => array(
                'required' => true,
                'NotEmpty' => array(),
            ),
            'email' => array(
                'required' => true,
                'NotEmpty' => array(),
                'Email' => array(),
            ),
            'title' => array(
                'required' => true,
                'NotEmpty' => array(),
            ),
            'message' => array(
                'required' => true,
                'NotEmpty' => array(),
            ),
        );
    }

}
