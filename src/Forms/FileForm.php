<?php

/*
 */

namespace dsLive\Forms;

use dScribe\Form\Form,
	Exception;

/**
 * Description of FileForm
 *
 * @author topman
 */
class FileForm extends Form {

	public function __construct($modelOrMaxSize, $name = 'fileForm', array $attributes = array()) {
		parent::__construct($name, $attributes);

		if (is_object($modelOrMaxSize)) {
			if (!is_a($modelOrMaxSize, 'dsLive\Models\File'))
					throw new Exception('$modelOrMaxSize must be of type \dsLive\Models\File');

			$this->setModel($modelOrMaxSize);
			$modelOrMaxSize = $modelOrMaxSize->getMaxSize(false);
		}
		$file = new \dsLive\Stdlib\File();
		$file->setMaxSize($modelOrMaxSize);
		$this->setAttributes(array(
			'method' => 'post',
			'enctype' => 'multipart/form-data'
		));

		$this->add(array(
			'name' => 'MAX_FILE_SIZE',
			'type' => 'hidden',
			'options' => array(
				'value' => $file->getMaxSize(),
			)
		));
	}

}
