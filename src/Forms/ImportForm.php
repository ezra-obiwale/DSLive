<?php

namespace DSLive\Forms;

class ImportForm extends FileForm {

    public function __construct($name = 'importForm', $maxSize = null, array $attributes = array()) {
        parent::__construct(ini_get('post_max_size'), $name, $attributes);

        $this->setAttributes(array(
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        if ($maxSize) {
            $this->add(array(
                'name' => 'MAX_FILE_SIZE',
                'type' => 'hidden',
                'attributes' => array(
                    'value' => $maxSize,
                )
            ));
        }

        $this->add(array(
            'name' => 'file',
            'type' => 'file',
            'options' => array(
                'label' => 'Excel File',
            ),
            'attributes' => array(
                'autofocus' => 'autofocus',
                'accept' => '.xls, .xlsx'
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
                'value' => 'Import',
            ),
            'attributes' => array(
                'class' => 'btn btn-success'
            )
        ));
    }

    public function getFilters() {
        return array(
            'file' => array(
                'required' => true,
                'NotEmpty' => array(),
            ),
        );
    }

}
