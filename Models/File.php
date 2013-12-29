<?php
/*
 */

namespace DSLive\Models;

/**
 * Description of File
 *
 * @author topman
 */
abstract class File extends Model {

	private $_extensions;
	private $_badExtensions;
	private $_maxSize;

	/**
	 * @DBS\String (size=5)
	 */
	protected $extension;

	public function getExtension() {
		return $this->extension;
	}

	public function setExtension($extension) {
		$this->extension = $extension;
	}

	/**
	 * Adds a file extension type to a file property
	 * @param string $property
	 * @param string $ext
	 * @return \DSLive\Models\File
	 * @throws \Exception
	 */
	final public function addExtension($property, $ext) {
		if (!property_exists($this, $property)) {
			throw new \Exception('Add File Extension Error: Property "' . $property . '" does not exists');
		}

		$this->_extensions[$property][] = $ext;
		return $this;
	}

	/**
	 * Sets the extensions for the given property. If any extensions existed for the property,
	 * they will be overriden.
	 * @param string $property
	 * @param array $extensions
	 * @return \DSLive\Models\File
	 * @throws \Exception
	 */
	final public function setExtensions($property, array $extensions) {
		if (!property_exists($this, $property)) {
			throw new \Exception('File Add Extension Error: Property "' . $property . '" does not exists');
		}

		$this->_extensions[$property] = $extensions;
		return $this;
	}

	/**
	 * Adds a bad file extension type to a file property
	 * @param string $property
	 * @param string $ext
	 * @return \DSLive\Models\File
	 * @throws \Exception
	 */
	final public function addBadExtension($property, $ext) {
		if (!property_exists($this, $property)) {
			throw new \Exception('Add Bad File Extension Error: Property "' . $property . '" does not exists');
		}

		$this->_badExtensions[$property][] = $ext;
		return $this;
	}

	/**
	 * Sets the bad extensions for the given property. If any bad extensions existed for the property,
	 * they will be overriden.
	 * @param string $property
	 * @param array $extensions
	 * @return \DSLive\Models\File
	 * @throws \Exception
	 */
	final public function setBadExtensions($property, array $extensions) {
		if (!property_exists($this, $property)) {
			throw new \Exception('Add Bad File Extension Error: Property "' . $property . '" does not exists');
		}

		$this->_badExtensions[$property] = $extensions;
		return $this;
	}

	/**
	 * Sets the maximum size of the file to upload
	 * @param int|string $size
	 * @return \DSLive\Models\File
	 */
	final public function setMaxSize($size) {
		$this->_maxSize = $size;
		return $this;
	}

	/**
	 * Fetches the byte value of the max filesize
	 * If none is specified, uses the php_ini upload_max_filesize
	 *
	 * @return int
	 */
	final public function getMaxSize() {
		if ($this->_maxSize === null) {
			$this->_maxSize = ini_get('upload_max_filesize');
		}

		return $this->parseSize($this->_maxSize);
	}

	/**
	 * Uploads files to the server
	 * @param array|\Object $files
	 * @return boolean
	 * @throws \Exception
	 */
	final public function uploadFiles($files) {
		if (is_object($files) && get_class($files) === 'Object') {
			$files = $files->toArray(true);
		} else if (is_object($files) || !is_array($files)) {
			throw new \Exception('Param $files must be either an object of type \Object or an array');
		}

		foreach ($files as $ppt => $info) {
			if (empty($info['name']))
				continue;

			if (!property_exists($this, $ppt))
				continue;

			if (!$this->fileIsOk($ppt, $info))
				return false;

			$dir = DATA . \Util::_toCamel($this->getTableName()) . DIRECTORY_SEPARATOR . $this->extension;
			if (!is_dir($dir)) {
				if (!mkdir($dir, 0777, true)) {
					throw new \Exception('Permission denied to directory "' . DATA . '"');
				}
			}

			$savePath = $dir . DIRECTORY_SEPARATOR . time() . '_' . preg_replace('/[^A-Z0-9._-]/i', '_', basename($info['name']));

			if (move_uploaded_file($info['tmpName'], $savePath)) {
				$this->unlink($ppt);
				$this->$ppt = $savePath;
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks the info against property settings
	 * @param string $property
	 * @param array $info
	 * @return boolean
	 */
	final public function fileIsOk($property, array $info) {
		if ($info['error'] !== UPLOAD_ERR_OK)
			return false;

		if (!$this->sizeIsOk($info['size']))
			return false;

		$info = pathinfo($info['name']);
		if (!$this->extensionIsOk($property, $info['extension']))
			return false;

		return true;
	}

	/**
	 * Checks if the given extension is allowed for the given property
	 * @param string $property
	 * @param string $extension
	 * @return boolean
	 */
	final public function extensionIsOk($property, $extension) {
		if (isset($this->_extensions[$property]) && !in_array($extension, $this->_extensions[$property]))
			return false;
		if (isset($this->_badExtensions[$property]) && in_array($extension, $this->_badExtensions[$property]))
			return false;

		$this->extension = $extension;
		return true;
	}

	/**
	 * Checks if the given size is not bigger than the expected size for the property
	 * @param int|string $size
	 * @return boolean
	 */
	final public function sizeIsOk($size) {
		if ($size > $this->getMaxSize()) {
			return false;
		}

		return true;
	}

	/**
	 * Converts string sizes (kb,mb) to int size (bytes)
	 * @param string|int $size
	 * @return int
	 * @throws \Exception
	 */
	final public function parseSize($size) {
		if (is_int($size))
			return $size;

		if (!is_string($size)) {
			throw new \Exception('File sizes must either be an integer or a string');
		}

		if (strtolower(substr($size, strlen($size) - 1)) === 'k' || strtolower(substr($size, strlen($size) - 2)) === 'kb') {
			return (int) $size * 1000;
		} elseif (strtolower(substr($size, strlen($size) - 1)) === 'm' || strtolower(substr($size, strlen($size) - 2)) === 'mb') {
			return (int) $size * 1000000;
		}
	}

	/**
	 * Deletes the file in the given property
	 * @param string $property
	 * @return boolean
	 */
	final public function unlink($property) {
		if (property_exists($this, $property) && is_string($this->$property) &&
			is_file($this->$property))
			return unlink($this->$property);

		return true;
	}

}
