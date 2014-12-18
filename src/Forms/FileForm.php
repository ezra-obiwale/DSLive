<?php

/*
 */

namespace DSLive\Forms;

use DScribe\Form\Form,
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
            if (!is_a($modelOrMaxSize, 'DSLive\Models\File'))
                throw new Exception('$modelOrMaxSize must be of type \DSLive\Models\File');

            $this->setModel($modelOrMaxSize);
            $modelOrMaxSize = $modelOrMaxSize->getMaxSize(false);
        }
        $file = new \DSLive\Stdlib\File();
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
