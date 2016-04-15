<?php

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
			throw new Exception('Attributes for the filename must be an array');
		}
		if (!isset($attributes['width'])) $attributes['width'] = '100%';
		if (isset($attributes['title']) && !isset($attributes['alt']))
				$attributes['alt'] = $attributes['title'];
		elseif (isset($attributes['alt']) && !isset($attributes['title']))
				$attributes['title'] = $attributes['alt'];

		return '<img src="' . $this->cleanFilename($filename) . '" ' . $this->parseAttributes($attributes) . ' />';
	}

	private function cleanFilename($filename) {
		if ($file = str_replace('protected/data', '', stristr($filename, 'protected/data'))) {
			$public = ROOT . 'public' . $file;
			if (!is_readable($public)) {
				copy($filename, $public);
			}
			return str_replace('protected/data', '', $file);
		}

		return str_replace(ROOT . 'public', '', $filename);
	}

	private function parseAttributes($attributes) {
		$return = '';
		foreach ($attributes as $attr => $val) {
			$return .= $attr . '="' . $val . '" ';
		}
		return $return;
	}

}
