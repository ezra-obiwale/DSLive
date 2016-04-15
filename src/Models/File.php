<?php

namespace dsLive\Models;

use Exception,
	Object,
	Util;

/**
 * Description of File
 *
 * @author topman
 */
abstract class File extends Model {

	private $extensions = array();
	private $badExtensions = array();
	private $withThumbnails = array();
	private $maxSize;
	private $directory;
	private $saveSingleFile;

	/**
	 * The name of the property to use as the name of the file when saving to filesystem
	 * @var string
	 */
	private $altNameProperty;

	/**
	 * Indicates whether to remove extension from files or not
	 * @var boolean
	 */
	private $stripExtension;

	/**
	 * Indicates whether to overwrite files with new if an existing file is found
	 * @var boolean
	 */
	private $overwrite = true;

	/**
	 * The names of the image being upload
	 * @var array
	 */
	private $names;
	private $errors;
	private $limits;
	private $properties = array();
	private $inDirectory;
	private $skipTableName;
	private $removeFiles;

	abstract public function __construct();

	/**
	 * Adds a file extension type to a file property
	 * @param string $property
	 * @param string $ext
	 * @return File
	 * @throws Exception
	 */
	public function addExtension($property, $ext) {
		if (!is_string($ext)) {
			throw new Exception('Add File Extension Error: You may only add string extensions for property "' . $property . '"');
		}
		$this->extensions[$property][] = $ext;
		return $this;
	}

	/**
	 * Sets the extensions for the given property. If any extensions existed for the property,
	 * they will be overriden.
	 * @param string $property
	 * @param array $extensions
	 * @return File
	 * @throws Exception
	 */
	public function setExtensions($property, array $extensions) {
//        if (!property_exists($this, $property)) {
//            throw new \Exception('File Add Extension Error: Property "' . $property . '" does not exists');
//        }

		$this->extensions[$property] = $extensions;
		return $this;
	}

	/**
	 * Sets the name of the property to use as the name of the file when saving to filesystem
	 * @param string $property Property to hold file
	 * @param string $altNameProperty Property with intended file name
	 * @return File
	 */
	final public function setAltNameProperty($property, $altNameProperty) {
		$this->altNameProperty[$property] = $altNameProperty;
		return $this;
	}

	/**
	 * Adds a bad file extension type to a file property
	 * @param string $property
	 * @param string $ext
	 * @return File
	 * @throws Exception
	 */
	public function addBadExtension($property, $ext) {
		if (!is_string($ext)) {
			throw new Exception('Add Bad File Extension Error: You may only add string extensions for property "' . $property . '"');
		}

		$this->badExtensions[$property][] = $ext;
		return $this;
	}

	/**
	 * Sets the bad extensions for the given property. If any bad extensions existed for the property,
	 * they will be overriden.
	 * @param string $property
	 * @param array $extensions
	 * @return File
	 * @throws Exception
	 */
	public function setBadExtensions($property, array $extensions) {
//        if (!property_exists($this, $property)) {
//            throw new \Exception('Add Bad File Extension Error: Property "' . $property . '" does not exists');
//        }

		$this->badExtensions[$property] = $extensions;
		return $this;
	}

	/**
	 *
	 * @param string $property
	 * @param int|array $desiredWidth Int width or array of thumbnail widths
	 * @return File
	 */
	final public function withThumbnails($property, $desiredWidth = 200) {
		$this->withThumbnails[$property] = is_array($desiredWidth) ? $desiredWidth : array($desiredWidth);
		return $this;
	}

	/**
	 * Sets the directory to upload files to
	 * @param string $directory Path name within the media directory
	 * @return File
	 */
	final public function setDirectory($directory) {
		$this->directory = (substr($directory, strlen($directory) - 1) === DIRECTORY_SEPARATOR) ?
				$directory : $directory . DIRECTORY_SEPARATOR;
		return $this;
	}

	/**
	 * Sets the maximum size of the file to upload
	 * @param int|string $size
	 * @return File
	 */
	final public function setMaxSize($size) {
		$this->maxSize = $size;
		return $this;
	}

	/**
	 * Fetches the byte value of the max filesize
	 * If none is specified, uses the php_ini upload_max_filesize
	 *
	 * @return int
	 */
	final public function getMaxSize($parse = true) {
		if ($this->maxSize === null) {
			$this->maxSize = ini_get('upload_max_filesize');
		}

		return ($parse) ? $this->parseSize($this->maxSize) : $this->maxSize;
	}

	/**
	 * Indicates whether to strip extension from the file or not
	 * @param string|boolean $property Name of property or true|false for all 
	 * properties
	 * @param boolean $strip Indication for the property if given
	 * @return File
	 */
	public function stripExtension($property = true, $strip = true) {
		if (is_bool($property)) $this->stripExtension = $property;
		else if (is_string($property)) $this->stripExtension[$property] = $strip;

		return $this;
	}

	/**
	 * Fetches the names of the files uploaded without the extensions
	 * @return array
	 */
	final public function getFileNames() {
		return $this->names;
	}

	/**
	 * Indicates that file should not be placed directly in media directory and 
	 * not within its extension director
	 * @param bool $bool
	 * @return File
	 */
	public function inDirectory($bool = true) {
		$this->inDirectory = $bool;
		return $this;
	}

	/**
	 * Indicates that table name should not be included in the file path
	 * @param bool $bool
	 * @return File
	 */
	public function skipTableName($bool = true) {
		$this->skipTableName = $bool;
		return $this;
	}

	/**
	 * Accepts relative paths to files to delete. Would also delete the files' thumbnails too.
	 * @param string|array $files
	 * @return void
	 */
	public function setRemoveFiles($files) {
		$this->removeFiles = $files;
		return $this;
	}

	/**
	 * Uploads files to the server
	 * @param array|Object $files
	 * @return boolean
	 * @throws Exception
	 */
	public function uploadFiles($files, $removeOld = false) {
		if (is_object($files) && get_class($files) === 'Object') {
			$files = $files->toArray(true);
		}
		else if (is_object($files) || !is_array($files)) {
			throw new Exception('Param $files must be either an object of type \Object or an array');
		}

		$savePaths = array();
		foreach ($files as $ppt => $info) {
			if (empty($info['name'])) {
				$this->postFetch($ppt);
				$this->errors[] = 'No file found for ' . $ppt;
				continue;
			}

			$extension = $this->fileIsOk($ppt, $info);
			if (!$extension) return false;

			$name = is_array($info['name']) ? $info['name'] : array($info['name']);

			$cnt = 2;
			foreach ($extension as $ky => $ext) {
				if (array_key_exists($ppt, $this->limits) && $this->limits[$ppt] ==
						$ky) break;

				$dir = $this->inDirectory ?
						$this->getMediaPath() :
						$this->getMediaPath() . $ext . DIRECTORY_SEPARATOR;
				if (!is_dir($dir)) {
					if (!mkdir($dir, 0777, true)) {
						throw new Exception('Permission denied to directory "' . ROOT . 'public"');
					}
				}
				if ($this->altNameProperty[$ppt] !== null) {
					$method = 'get' . $this->altNameProperty[$ppt];
					$nam = method_exists($this, $method) ?
							preg_replace('/[^A-Z0-9]/i', '-', basename($this->{$method}())) :
							$this->getProperty($this->altNameProperty[$ppt]);
				}
				else {
					$inf = pathinfo($name[$ky]);
					$nam = str_replace('--', '', preg_replace('/[^A-Z0-9]/i', '-', $inf['filename']));
				}

				$nam .= $this->checkOverwrite($dir, $nam, $ext, $cnt);
				if ((is_array($this->stripExtension) && !$this->stripExtension[$ppt]) ||
						(!is_array($this->stripExtension) && !$this->stripExtension)) $nam .= '.' . $ext;
				$this->names[] = $nam;
				$tmpName = (isset($info['tmpName'])) ? $info['tmpName'] : $info['tmp_name'];
				$source = is_array($tmpName) ? $tmpName[$ky] : $tmpName;
				if (!move_uploaded_file($source, $dir . $nam)) return false;

				if (array_key_exists($ppt, $this->withThumbnails)) {
					if (!is_dir($dir . '.thumbnails')) mkdir($dir . '.thumbnails');

					foreach ($this->withThumbnails[$ppt] as $size) {
						$newFile = $dir . '.thumbnails' . DIRECTORY_SEPARATOR;
						if ((is_array($this->stripExtension) && !$this->stripExtension[$ppt]) ||
								(!is_array($this->stripExtension) && !$this->stripExtension))
								$newFile .= str_replace('.' . $ext, '-' . $size . '.' . $ext, $nam);
						else $newFile .= $nam . '-' . $size;

						Util::resizeImage($dir . $nam, $size, $newFile, $ext);
					}
				}
				$savePaths[$ppt][] = engineGet('serverPath') . str_replace(array(ROOT, '\\'), array('', '/'), $dir . $nam);
				$cnt++;
			}
			if ($removeOld || (is_array($this->saveSingleFile) && in_array($ppt, $this->saveSingleFile)))
					$this->unlink($ppt);
			if (count($savePaths)) {
				$oldValue = $this->getProperty($ppt);
				if (!is_array($this->saveSingleFile) || !in_array($ppt, $this->saveSingleFile) && $oldValue) {
					if (is_array($oldValue)) $pptValue = array_merge($oldValue, $savePaths[$ppt]);
					else if ($oldValue) $pptValue = array_merge(array($oldValue), $savePaths[$ppt]);
					else $pptValue = $savePaths[$ppt];
				}
				else if (is_array($this->saveSingleFile) && in_array($ppt, $this->saveSingleFile))
						$pptValue = $savePaths[$ppt][0];
				else $pptValue = $savePaths[$ppt];

				$this->$ppt = $pptValue;
				$this->setProperty($ppt, $pptValue);
			}
		}
		return $savePaths;
	}

	private function checkOverwrite($dir, $nam, $ext, &$cnt) {
		if (!$this->overwrite) {
			$preZeros = 3; // maximum of 9999
			$cnt_str = '-' . str_repeat('0', $preZeros) . $cnt;
			$benchmark = 10;
			$file = $ext ? $dir . $nam . $cnt_str . '.' . $ext : $dir . $nam . $cnt_str;
			while (is_readable($file)) {
				$cnt++;
				if ($cnt === $benchmark) {
					$preZeros--;
					$benchmark *= 10;
				}
				$cnt_str = '-' . str_repeat('0', $preZeros) . $cnt;
				$file = $ext ? $dir . $nam . $cnt_str . '.' . $ext : $dir . $nam . $cnt_str;
			}

			return $cnt_str;
		}
	}

	/**
	 * Fetches the thumbnails of the values in the given property, if available
	 * @param string $property Name of the property in the current class
	 * @param int $key The key index of the thumbnail to fetch
	 * @param int $size The thumbnail size to fetch
	 * @return mixed
	 */
	final public function getThumbnails($property, $key = null, $size = null) {
		$value = $this->getOldValue($property);
		if (!$value) $value = array();
		$thumbs = !is_array($value) ? array($value) : $value;
		array_walk($thumbs, function(&$value) use($size) {
			$info = pathinfo(basename($value));
			if ($size) $size = '-' . $size;
			$value = str_replace($info['filename'], '.thumbnails/' . $info['filename'] . $size, $value);
		});

		if ($key === null) return $thumbs;
		if (array_key_exists($key, $thumbs)) return $thumbs[$key];
	}

	/**
	 * Checks the info against property settings
	 * @param string $property
	 * @param array $info
	 * @return boolean
	 */
	final public function fileIsOk($property, array $info) {
		$error = is_array($info['error']) ? $info['error'] : array($info['error']);
		foreach ($error as $err) {
			if ($err !== UPLOAD_ERR_OK) {
				$this->postFetch($property);
				$this->errors[] = 'File not found';
				return false;
			}
		}

		if (!$this->sizeIsOk($info['size'])) {
			$this->errors[] = 'File size [' . round($info['size'][0] / 1000000, 1) . 'M] too big';
			return false;
		}

		return $this->extensionIsOk($property, $info['name']);
	}

	/**
	 * Checks if the given extension is allowed for the given property
	 * @param string $property
	 * @param string $extension
	 * @return boolean
	 */
	final public function extensionIsOk($property, $name) {
		$name = is_array($name) ? $name : array($name);
		$extensions = array();
		foreach ($name as $nm) {
			$info = pathinfo($nm);
			$extension = strtolower($info['extension']);
			if ((isset($this->extensions[$property]) && !in_array($extension, $this->extensions[$property])) || (isset($this->badExtensions[$property]) &&
					in_array($extension, $this->badExtensions[$property]))) {
				$this->errors[] = 'File extension (' . $extension . ') not allowed for ' . $property;
				return false;
			}

			$extensions[] = $extension;
		}
		return $extensions;
	}

	/**
	 * Checks if the given size is not bigger than the expected size for the property
	 * @param int|string $size
	 * @return boolean
	 */
	final public function sizeIsOk($size) {
		$size = is_array($size) ? $size : array($size);
		foreach ($size as $sz) {
			if ($sz > $this->getMaxSize()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Converts string sizes (kb,mb) to int size (bytes)
	 * @param string|int $size
	 * @return int
	 * @throws Exception
	 */
	final public function parseSize($size) {
		if (is_int($size)) return $size;

		if (!is_string($size)) {
			throw new Exception('File sizes must either be an integer or a string');
		}

		if (strtolower(substr($size, strlen($size) - 1)) === 'k' || strtolower(substr($size, strlen($size) - 2)) === 'kb') {
			return (int) $size * 1000;
		}
		elseif (strtolower(substr($size, strlen($size) - 1)) === 'm' || strtolower(substr($size, strlen($size) - 2)) === 'mb') {
			return (int) $size * 1000000;
		}
	}

	/**
	 * Deletes the file in the given property
	 * @param string $property
	 * @return boolean
	 */
	final public function unlink($property = null) {
		if (!$this->badExtensions) $this->badExtensions = array();
		if (!$this->extensions) $this->extensions = array();

		$properties = $property ? array($property) :
				array_keys(array_merge($this->badExtensions, $this->extensions));
		foreach ($properties as $property) {
			if (property_exists($this, $property)) {
				if (is_string($this->getOldValue($property)) &&
						is_readable(ROOT . str_replace(engineGet('serverPath'), '', $this->getOldValue($property)))) {
					unlink(ROOT . str_replace(engineGet('serverPath'), '', $this->getOldValue($property)));
					if (array_key_exists($property, $this->withThumbnails)) {
						foreach ($this->withThumbnails[$property] as $size) {
							foreach ($this->getThumbnails($property, null, $size) as $file) {
								unlink(ROOT . str_replace(engineGet('serverPath'), '', $file));
							}
						}
					}
				}
				else if (is_array($this->getOldValue($property))) {
					foreach ($this->getOldValue($property) as $key => $file) {
						unlink(ROOT . str_replace(engineGet('serverPath'), '', $file));
						if (array_key_exists($property, $this->withThumbnails)) {
							foreach ($this->withThumbnails[$property] as $size) {
								foreach ($this->getThumbnails($property, $key, $size) as $file) {
									unlink(ROOT . str_replace(engineGet('serverPath'), '', $file));
								}
							}
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Fetches the overwrite option
	 * @return boolean
	 */
	public function getOverwrite() {
		return $this->overwrite;
	}

	/**
	 * Indicates whether to overwrite file if existing or not
	 * @param boolean $overwrite
	 * @return File
	 */
	public function setOverwrite($overwrite) {
		$this->overwrite = $overwrite;
		return $this;
	}

	/**
	 * Limits the number of files to upload when multiple uploads are allowed
	 * @param string $property
	 * @param int $count
	 * @return File
	 */
	public function limitFile($property, $count) {
		$this->limits[$property] = $count;
		return $this;
	}

	/**
	 * Indicates whether to save just one file. The property would contain just the path and not an
	 * array of paths
	 * @param string $property
	 * @return File
	 */
	public function saveSingleFile($property) {
		$this->saveSingleFile[] = $property;
		return $this;
	}

	/**
	 * Fetches the errors that occurred during file upload
	 * @return array
	 */
	final public function getErrors() {
		return ($this->errors) ? $this->errors : array();
	}

	/**
	 * Fetches an array of properties which expect file inputs
	 * @return array
	 */
	final public function getFileProperties() {
		return array_unique(array_merge(array_keys($this->badExtensions), array_keys($this->extensions)));
	}

	public function preSave($createId = true) {
		if ($this->removeFiles) {
			if (!$this->removeFiles) return;
			if (!is_array($this->removeFiles)) $this->removeFiles = array($this->removeFiles);
			foreach ($this->removeFiles as $property => $filee) {
				if (is_array($this->$property)) $flipped = array_flip($this->$property);
				foreach ($filee as $file) {
					$file = strstr($file, '?', true);
					if (is_array($this->$property)) {
						if (array_key_exists($file, $flipped)) {
							unset($this->{$property}[$flipped[$file]]);
						}
					}
					else if ($this->$property) {
						$this->$property = null;
					}
					$abs_file = strstr($this->getMediaPath(), strrev(basename(strrev($file))), true) . $file;
					if (file_exists($abs_file)) unlink($abs_file);
					$info = pathinfo($abs_file);
					$checked = array();
					foreach ($this->withThumbnails as $array) {
						foreach ($array as $size) {
							if (in_array($size, $checked)) continue;
							$checked[] = $size;
							$file = $info['dirname'] . DIRECTORY_SEPARATOR . '.thumbnails' . DIRECTORY_SEPARATOR . $info['filename'] . '-' . $size . '.' . $info['extension'];
							if (file_exists($file)) unlink($file);
						}
					}
				}
				$this->$property = array_values($this->$property);
			}
		}

		foreach (array_keys(array_merge($this->extensions, $this->badExtensions)) as $property) {
			if ($this->$property && is_array($this->$property))
					$this->$property = json_encode($this->$property);
		}

		parent::preSave($createId);
	}

	public function postFetch($property = null) {
		if ($property && property_exists($this, $property)) {
			return $this->{$property};
		}

		$fileProperties = array_keys(array_merge($this->extensions, $this->badExtensions));
		foreach ($fileProperties as $property) {
			if ($val = json_decode($this->$property, true)) $this->$property = $val;
		}

		parent::postFetch($property);
	}

	public function getMediaPath() {
		return ASSETS . 'media' .
				($this->skipTableName ? '' :
						DIRECTORY_SEPARATOR . Util::_toCamel($this->getTableName())) .
				DIRECTORY_SEPARATOR . $this->directory;
	}

	public function setProperty($property, $value) {
		$this->properties[$property] = $value;
		return $this;
	}

	public function getProperty($property) {
		return array_key_exists($property, $this->properties) ? $this->properties[$property] : $this->getOldValue($property);
	}

	public function getProperties($preSave = true) {
		if ($preSave) $this->preSave();
		return $this->properties;
	}

	public function getImage($property) {
		if ($this->$property) {
			return is_array($this->$property) ? $this->{$property}[0] : $this->$property;
		}
	}

}
