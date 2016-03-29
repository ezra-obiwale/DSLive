<?php

namespace DSLive\Forms;

use DScribe\Form\Form,
    DSLive\Models\User;

class MediaSignUpForm extends Form {

    public function __construct() {
        parent::__construct('mediaSignUpForm');

        $this->setAttribute('method', 'POST');

        $this->add(array(
            'name' => 'email',
            'type' => 'hidden',
            'attributes' => array(
                'required' => 'required',
            )
        ))
		->add(array(
			'name' => 'firstName',
			'type' => 'hidden',
			'attributes' => array(
				'required' => 'required'
			),
		))
		->add(array(
			'name' => 'lastName',
			'type' => 'hidden',
			'attributes' => array(
				'required' => 'required',
			)
		));
		
        /* $this->add(array(
            'name' => 'media',
            'type' => 'hidden',
            'attributes' => array(
                'required' => 'required',
            )
        ));
        $this->add(array(
            'name' => 'id',
            'type' => 'hidden',
            'attributes' => array(
                'required' => 'required',
            )
        )); */
		
        $this->add(array(
            'name' => 'csrf',
            'type' => 'hidden'
        ));

    }

    public function getFilters() {
        return array(
            'email' => array(
                'required' => true,
                'NotEmpty' => array(),
                'Email' => array(),
            ),
            'firstName' => array(
                'required' => true,
                'NotEmpty' => array(),
            ),
            'lastName' => array(
                'required' => true,
                'NotEmpty' => array(),
            ),
			/* 'media' => array(
				'required' => true,
			),
			'id' => array(
				'required' => true,
			) */
        );
    }

}
