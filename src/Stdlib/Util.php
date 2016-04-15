<?php

/*
 */

namespace dsLive\Stdlib;

use dsLive\Models\User as DMU,
	Util as Utl;

/**
 * Description of Util
 *
 * @author topman
 */
class Util {

	/**
	 * Makes the value of the array both the index and the value if the index is
	 * an integer
	 * @param array $array
	 * @return array
	 */
	public static function prepareArrayForSelect(array $array) {
		$return = array();
		foreach ($array as $index => $value) {
			if (is_int($index)) $return[$value] = $value;
			else $return[$index] = $value;
		}
		return $return;
	}

	/**
	 * Fetches an array of all countries.
	 * 
	 * As a prerequisite, file "worldCountries.csv" must be in the DATA directory
	 * @return array
	 */
	public static function fetchCountries() {
		$countries = VENDOR . 'd-scribe' . DIRECTORY_SEPARATOR . 'ds-live'
				. DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'assets'
				. DIRECTORY_SEPARATOR . 'worldCountries.csv';
		if (!is_readable($countries)) return array();

		$countries = explode(',', file_get_contents($countries));
		sort($countries);
		return $countries;
	}

	/**
	 * Find all templates in a string
	 * @param array $templates array of first words after opening
	 * @param string $string The string to search through
	 * @param array|string $func Function to pass each found template into. The 
	 * found template is the first value while the first word is passed as the 
	 * second value. The value  returned by the function is returned. @see call_user_func
	 * @param string $opening
	 * @param string $closing
	 * @return array
	 */
	public static function findTemplates(array $templates, $string, $func = null, $opening = '{{',
									  $closing = '}}') {
		$found = array();
		foreach ($templates as $template) {
			$last = 0;
			while ($start = stripos($string, $opening . $template, $last)) {
				$end = stripos($string, $closing, $start);
				$f = substr($string, $start + strlen($opening), $end - $start - strlen($closing));
				if ($func === null) {
					$found[] = $f;
				}
				else {
					$ret = call_user_func($func, $f, $template);
					if (is_array($ret)) {
						$found = array_merge_recursive($found, $ret);
					}
					else {
						$found[] = $ret;
					}
				}
				$last = $end;
			}
		}
		return $found;
	}

	public static function prepareMessage($msg, DMU $user,
									   $func = array('dsLive\Stdlib\Util', 'parseTemplates')) {
		$templates = self::findTemplates(array('user', 'accessCode'), $msg, $func);
		foreach ($templates as $template => $value) {
			if (is_array($value)) {
				foreach ($value as $tpl) {
					self::replaceTemplate($tpl, $msg, $user);
				}
			}
			else {
				self::replaceTemplate($value, $msg, $user);
			}
		}
		return $msg;
	}

	public static function replaceTemplate($template, &$msg, DMU $user) {
		$method = 'get' . ucfirst(Utl::hyphenToCamel($template));
		if (method_exists($user, $method))
				$msg = str_replace('{{user: ' . $template . '}}', $user->$method(), $msg);

		$accessCode = $user->accessCode()->first();
		if ($accessCode && method_exists($accessCode, $method))
				$msg = str_replace('{{accessCode: ' . $template . '}}', $accessCode->$method(), $msg);

		$domain = engineGet('db')->table('settings')
						->select(array(array('key' => 'domain')))
						->first()->value;

		if ('confirmation-link' == $template) {
			$msg = str_replace('{{user: confirmation-link}}', 'http://' .
					$domain . '/guest/index/confirm-registration/' .
					urlencode($user->getId()) . '/' .
					urlencode($user->getEmail()), $msg);
		}
		else if ('password-reset-link' == $template) {
			$msg = str_replace('{{user: password-reset-link}}', 'http://' .
					$domain . '/guest/index/reset-password/' .
					urlencode($user->getId()) . '/' .
					urlencode($user->getReset()), $msg);
		}

		return $msg;
	}

	public static function parseTemplates($value, $template) {
		return array($template => trim(str_replace($template . ': ', '', $value)));
	}

	public static function getMimeType($filename) {
//        if (!function_exists('mime_content_type')) {
//            function mime_content_type($filename) {
		if (!is_file($filename)) {
			$error = 'Error: File not found';
			if (defined('__ADMIN_DEBUG')) {
				$error .= ' mime_content_type(): ' . $filename;
			}
			return $error;
		}
		elseif (function_exists('finfo_open')) {
			$finfo = @finfo_open(FILEINFO_MIME_TYPE);
			if (!$finfo) {
				$error = 'Error: Unable to verify MIME content type';
				if (defined('__ADMIN_DEBUG')) {
					$error .= ' finfo(): Opening magic.mime database may have failed';
				}
				return $error;
			}
			if ($mimetype = @finfo_file($finfo, $filename)) {
				finfo_close($finfo);
				return $mimetype;
			}
			else {
				$error = 'Error: Unable to verify MIME content type';
				if (defined('__ADMIN_DEBUG')) {
					$error .= ' Error executing finfo() for ' . $filename;
				} return $error;
			}
		}
		elseif (function_exists('exec') && function_exists('escapeshellarg')) {
			if ($execmime = trim(@exec('file -bi ' . @escapeshellarg($filename)))) {
				return $execmime;
			}
			else {
				$error = 'Error: Unable to verify MIME content type';
				if (defined('__ADMIN_DEBUG')) {
					$error .= ' exec()/escapeshellarg() can not open ' . $filename;
				} return $error;
			}
		}
		elseif (function_exists('pathinfo')) {
			if ($pathinfo = @pathinfo($filename)) {
				$imagetypes = array('gif', 'jpeg', 'jpg', 'png', 'swf', 'psd', 'bmp',
					'tiff', 'tif', 'jpc', 'jp2', 'jpx', 'jb2', 'swc', 'iff', 'wbmp',
					'xbm', 'ico');
				if (in_array($pathinfo['extension'], $imagetypes) && getimagesize($filename)) {
					$size = getimagesize($filename);
					return $size['mime'];
				}
			}
			else {
				$error = 'Error: Unable to verify MIME content type';
				if (defined('__ADMIN_DEBUG')) {
					$error .= ' pathinfo() can not open ' . $filename;
				} return $error;
			}
		}
		else {
			return 'application/octet-stream';
		}
	}

}
