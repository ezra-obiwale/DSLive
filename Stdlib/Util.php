<?php
/*
 */

namespace DSLive\Stdlib;

/**
 * Description of Util
 *
 * @author topman
 */
class Util {

	public static function prepareArrayForSelect(array $array) {
		$return = array();
		foreach ($array as $value) {
			$return[$value] = $value;
		}
		return $return;
	}

	public static function fetchCountries() {
		if (!is_readable(DATA . 'worldCountries.csv'))
			return array();

		$countries = explode(',', file_get_contents(DATA . 'worldCountries.csv'));
		sort($countries);
		return $countries;
	}

}
