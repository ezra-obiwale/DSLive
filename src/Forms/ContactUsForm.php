<?php

namespace dsLive\Forms;

use dScribe\Form\Form;

class ContactUsForm extends Form {

	public function __construct() {
		parent::__construct('contactUsForm');

		$this->setAttribute('method', 'POST');

		$this->add(array(
			'name' => 'fullName',
			'type' => 'text',
			'options' => array(
				'label' => array(
					'text' => 'Full Name',
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
				'required' => 'required',
			)
		));

		$this->add(array(
			'name' => 'title',
			'type' => 'text',
			'options' => array(
				'label' => array(
					'text' => 'Title',
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
			'name' => 'message',
			'type' => 'textarea',
			'options' => array(
				'label' => array(
					'text' => 'Message',
					'attrs' => array(
						'class' => 'col-md-3',
					),
				),
				'containerAttrs' => array(
					'class' => 'col-md-8'
				),
			),
			'attributes' => array(
				'rows' => 8,
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
				'value' => 'Send',
				'containerAttrs' => array(
					'class' => 'col-md-offset-3 col-md-8'
				),
			),
			'attributes' => array(
				'class' => 'btn btn-success'
			)
		));
	}

	public function getFilters() {
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
