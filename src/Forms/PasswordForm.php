<?php

namespace dsLive\Forms;

use dScribe\Form\Form;

class PasswordForm extends Form {

	public function __construct() {
		parent::__construct('passwordForm');

		$this->setAttribute('method', 'post');

		$this->add(array(
			'name' => 'old',
			'type' => 'password',
			'options' => array(
				'label' => array(
					'text' => 'Current Password',
					'attrs' => array(
						'class' => 'col-md-4',
					),
				),
				'containerAttrs' => array(
					'class' => 'col-md-6'
				),
			),
			'attributes' => array(
				'required' => 'required',
			)
		));

		$this->add(array(
			'name' => 'new',
			'type' => 'password',
			'options' => array(
				'label' => array(
					'text' => 'New Password',
					'attrs' => array(
						'class' => 'col-md-4',
					),
				),
				'containerAttrs' => array(
					'class' => 'col-md-6'
				),
				'blockInfo' => 'Minimum 8 characters',
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
				'label' => array(
					'text' => 'Repeat New Password',
					'attrs' => array(
						'class' => 'col-md-4',
					),
				),
				'containerAttrs' => array(
					'class' => 'col-md-6'
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
				'value' => 'Save',
				'containerAttrs' => array(
					'class' => 'col-md-offset-4 col-md-6'
				),
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
