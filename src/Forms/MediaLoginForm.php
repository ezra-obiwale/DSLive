<?php

namespace DSLive\Forms;

use DScribe\Form\Form,
    DSLive\Models\User;

class MediaLoginForm extends Form {

    public function __construct() {
        parent::__construct('mediaLoginForm');

        $this->setAttribute('method', 'POST');

        $this->add(array(
            'name' => 'email',
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
			/* 'media' => array(
				'required' => true,
			),
			'id' => array(
				'required' => true,
			) */
        );
    }

}
