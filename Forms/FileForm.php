<?php

/*
 */

namespace DSLive\Forms;

use DScribe\Form\Form,
    DSLive\Models\File;

/**
 * Description of FileForm
 *
 * @author topman
 */
class FileForm extends Form {

    public function __construct(File $model, $name = 'fileForm', array $attributes = array()) {
        parent::__construct($name, $attributes);

        $this->setModel($model);

        $this->setAttributes(array(
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $this->add(array(
            'name' => 'MAX_FILE_SIZE',
            'type' => 'hidden',
            'attributes' => array(
                'value' => $model->getMaxSize(),
            )
        ));
    }

}
