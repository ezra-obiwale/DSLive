<?php

/*
 */

/**
 * Description of FileOut
 *
 * @author topman
 */
class FileOut {

    public function __invoke($filename, array $options = array()) {
        if (isset($options['imgTag']) && !$options['imgTag']) {
            if (isset($options['toScreen']) && $options['toScreen']) {
                header('Content-Type: image/png');
                die($content);
            }

            return $content;
        }

        $attributes = isset($options['attrs']) ? $options['attrs'] : array();
        $attributes = isset($options['attributes']) ? $options['attributes'] : $attributes;

        if (!is_array($attributes)) {
            throw new \Exception('Attributes for the filename must be an array');
        }
        if (!isset($attributes['width']))
            $attributes['width'] = '100%';
        if (isset($attributes['title']) && !isset($attributes['alt']))
            $attributes['alt'] = $attributes['title'];
        elseif (isset($attributes['alt']) && !isset($attributes['title']))
            $attributes['title'] = $attributes['alt'];

        if (!is_readable($filename)) {
            $filename = DATA . 'defaults' . DIRECTORY_SEPARATOR . 'headQ.png';
            if (!isset($attributes['width'])) {
                $attributes['style'] = (!isset($attributes['style'])) ?
                        'margin-left:20%;width:60%;' : $attributes['style'] . ';margin-left:20%;width:60%';
            }
        }

        $content = file_get_contents($filename);


        return '<img src="data:' . mime_content_type($filename) . ';base64, ' . base64_encode($content) . '" ' . $this->parseAttributes($attributes) . ' />';
    }

    private function parseAttributes($attributes) {
        $return = '';
        foreach ($attributes as $attr => $val) {
            $return .= $attr . '="' . $val . '" ';
        }
        return $return;
    }

}
