<?php

namespace DSLive\Models;

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

    /**
     * The name of the property to use as the name of the file when saving to filesystem
     * @var string
     */
    private $altNameProperty;

    /**
     * @DBS\String (nullable=true)
     */
    protected $mime;

    /**
     * Indicates whether to overwrite files with new if an existing file is found
     * @var boolean
     */
    private $overwrite = true;
    private $maxFileNumber;

    /**
     * The names of the image being upload
     * @var array
     */
    private $names;
    private $errors;
    private $limits;

    abstract public function __construct();

    /**
     * Adds a file extension type to a file property
     * @param string $property
     * @param string $ext
     * @return \DSLive\Models\File
     * @throws \Exception
     */
    public function addExtension($property, $ext) {
//        if (!property_exists($this, $property)) {
//            throw new \Exception('Add File Extension Error: Property "' . $property . '" does not exists');
//        }

        $this->extensions[$property][] = $ext;
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
    public function setExtensions($property, array $extensions) {
//        if (!property_exists($this, $property)) {
//            throw new \Exception('File Add Extension Error: Property "' . $property . '" does not exists');
//        }

        $this->extensions[$property] = $extensions;
        return $this;
    }

    /**
     * Sets the name of the property to use as the name of the file when saving to filesystem
     * @param string $altNameProperty
     * @return \DSLive\Models\File
     */
    final public function setAltNameProperty($altNameProperty) {
        $this->altNameProperty = $altNameProperty;
        return $this;
    }

    /**
     * Adds a bad file extension type to a file property
     * @param string $property
     * @param string $ext
     * @return \DSLive\Models\File
     * @throws \Exception
     */
    public function addBadExtension($property, $ext) {
//        if (!property_exists($this, $property)) {
//            throw new \Exception('Add Bad File Extension Error: Property "' . $property . '" does not exists');
//        }

        $this->badExtensions[$property][] = $ext;
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
    public function setBadExtensions($property, array $extensions) {
//        if (!property_exists($this, $property)) {
//            throw new \Exception('Add Bad File Extension Error: Property "' . $property . '" does not exists');
//        }

        $this->badExtensions[$property] = $extensions;
        return $this;
    }

    final public function withThumbnails($property, $desiredWidth = 200) {
        $this->withThumbnails[$property] = $desiredWidth;
        return $this;
    }

    /**
     * Sets the directory to upload files to
     * @param string $directory
     * @return \DSLive\Models\File
     */
    final public function setDirectory($directory) {
        $this->directory = (substr($directory, strlen($directory) - 1) === DIRECTORY_SEPARATOR) ?
                $directory : $directory . DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * Sets the maximum size of the file to upload
     * @param int|string $size
     * @return \DSLive\Models\File
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

    public function getMaxFileNumber() {
        return $this->maxFileNumber;
    }

    public function setMaxFileNumber($maxFileNumber) {
        $this->maxFileNumber = $maxFileNumber;
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
     * Uploads files to the server
     * @param array|\Object $files
     * @return boolean
     * @throws \Exception
     */
    public function uploadFiles($files, $removeOld = true) {
        if (is_object($files) && get_class($files) === 'Object') {
            $files = $files->toArray(true);
        }
        else if (is_object($files) || !is_array($files)) {
            throw new \Exception('Param $files must be either an object of type \Object or an array');
        }

        $savePaths = array();
        foreach ($files as $ppt => $info) {
            if (empty($info['name']))
                continue;

            $extension = $this->fileIsOk($ppt, $info);
            if (!$extension)
                return false;

            $name = is_array($info['name']) ? $info['name'] : array($info['name']);

            $cnt = 2;
            foreach ($extension as $ky => $ext) {
                if (array_key_exists($ppt, $this->limits) && $this->limits[$ppt] == $ky)
                    break;

                $dir = $this->getMediaPath() . $ext . DIRECTORY_SEPARATOR;
                if (!is_dir($dir)) {
                    if (!mkdir($dir, 0777, true)) {
                        throw new \Exception('Permission denied to directory "' . ROOT . 'public/"');
                    }
                }
                if ($this->altNameProperty !== null) {
                    $nam = preg_replace('/[^A-Z0-9]/i', '-', basename($this->{'get' . $this->altNameProperty}()));
                }
                else {
                    $inf = pathinfo($name[$ky]);
                    $nam = str_replace('--', '', preg_replace('/[^A-Z0-9]/i', '-', $inf['filename']));
                }
                $nam .= $this->checkOverwrite($dir, $nam, $ext, $cnt) . '.' . $ext;

                $this->names[] = $nam;
                $tmpName = (isset($info['tmpName'])) ? $info['tmpName'] : $info['tmp_name'];
                $source = is_array($tmpName) ? $tmpName[$ky] : $tmpName;
                if (!move_uploaded_file($source, $dir . $nam)) {
                    return false;
                }

                if (array_key_exists($ppt, $this->withThumbnails)) {
                    if (!is_dir($dir . '.thumbnails'))
                        mkdir($dir . '.thumbnails');
                    \Util::resizeImage($dir . $nam, $this->withThumbnails[$ppt], $dir . '.thumbnails' . DIRECTORY_SEPARATOR . $nam);
                }

                $savePaths[$ppt][$nam] = str_replace(ROOT . 'public', '', $dir . $nam);
                $cnt++;
            }
            if ($removeOld)
                $this->unlink($ppt);
            if (!empty($savePaths)) {
                if ($this->$ppt && !is_object($this->$ppt)) {
                    if (is_array($this->$ppt))
                        $this->$ppt = array_merge($this->$ppt, $savePaths[$ppt]);
                    else
                        $this->$ppt = array_merge(array($this->$ppt), $savePaths[$ppt]);
                }
                else
                    $this->$ppt = $savePaths[$ppt];
            }
        }

        return $savePaths;
    }

    private function checkOverwrite($dir, $nam, $ext, &$cnt) {
        if (!$this->overwrite) {
            $preZeros = 3; // maximum of 9999
            $cnt_str = '-' . str_repeat('0', $preZeros) . $cnt;
            $benchmark = 10;
            while (is_readable($dir . $nam . $cnt_str . '.' . $ext)) {
                $cnt++;
                if ($cnt === $benchmark) {
                    $preZeros--;
                    $benchmark *= 10;
                }
                $cnt_str = '-' . str_repeat('0', $preZeros) . $cnt;
            }

            return $cnt_str;
        }
    }

    /**
     * Fetches the thumbnails of the values in the given property, if available
     * @param string $property Name of the property in the current class
     * @param mixed $key The key index of the file to get from the property's values
     * @return mixed
     */
    final public function getThumbnails($property, $key = null) {
        $method = 'get' . ucfirst($property);
        if (!$this->$method())
            return array();

        $thumbs = (!is_array($this->$method())) ? array($this->$method()) : $this->$method();
        array_walk($thumbs, function(&$value) {
            $value = str_replace(basename($value), '.thumbnails' . DIRECTORY_SEPARATOR . basename($value), $value);
        });

        if ($key === null)
            return $thumbs;

        if (array_key_exists($key, $thumbs))
            return $thumbs[$key];
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
                $this->errors[] = 'File not found';
                return false;
            }
        }

        if (!$this->sizeIsOk($info['size'])) {
            $this->errors[] = 'File size [' . round($info['size'][0] / 1000000, 1) . 'M] too big';
            return false;
        }

        $this->mime[$property][] = $info['type'];
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
            if ((isset($this->extensions[$property]) && !in_array($extension, $this->extensions[$property])) || (isset($this->badExtensions[$property]) && in_array($extension, $this->badExtensions[$property]))) {
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
        if (!$this->badExtensions)
            $this->badExtensions = array();
        if (!$this->extensions)
            $this->extensions = array();

        $properties = $property ? array($property) :
                array_keys(array_merge($this->badExtensions, $this->extensions));

        foreach ($properties as $property) {
            if (property_exists($this, $property) && is_string($this->$property) &&
                    is_file($this->$property)) {
                unlink(ROOT . 'public' . $this->$property);
                if (array_key_exists($property, $this->withThumbnails)) {
                    foreach ($this->getThumbnails($property) as $file) {
                        unlink(ROOT . 'public' . $file);
                    }
                }
            }
            else if (is_array($this->$property)) {
                foreach ($this->$property as $file) {
                    unlink(ROOT . 'public' . $file);
                }
                if (array_key_exists($property, $this->withThumbnails)) {
                    foreach ($this->getThumbnails($property) as $file) {
                        unlink(ROOT . 'public' . $file);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Fetches the mime attribute of the file
     * @return string
     */
    public function getMime($property = NULL) {
        if (!$property)
            return $this->mime;

        if (array_key_exists($property, $this->mime))
            return $this->mime[$property];
    }

    /**
     * Sets the mime attribute of the file
     * @param string $mime
     * @return \DSLive\Models\File
     */
    public function setMime($mime) {
        $this->mime = $mime;
        return $this;
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
     * @return \DSLive\Models\File
     */
    public function setOverwrite($overwrite) {
        $this->overwrite = $overwrite;
        return $this;
    }

    public function limitFile($property, $count) {
        $this->limits[$property] = $count;
        return $this;
    }

    /**
     * Fetches the errors that occurred during file upload
     * @return array
     */
    final public function getErrors() {
        return ($this->errors) ? $this->errors : array();
    }

    public function preSave($createId = true) {
        foreach (array_keys(array_merge($this->extensions, $this->badExtensions)) as $property) {
            if ($this->$property && is_array($this->$property))
                $this->$property = serialize($this->$property);
        }

        if ($this->mime && is_array($this->mime))
            $this->mime = serialize($this->mime);
        parent::preSave($createId);
    }

    public function postFetch() {
        foreach (array_keys(array_merge($this->extensions, $this->badExtensions)) as $property) {
            if ($this->$property && $array = unserialize($this->$property)) {
                $this->$property = $array;
            }
        }

        if ($this->mime)
            $this->mime = unserialize($this->mime);
    }

    public function getMediaPath() {
        return ROOT . 'public' . DIRECTORY_SEPARATOR . 'media' .
                DIRECTORY_SEPARATOR . \Util::_toCamel($this->getTableName()) .
                DIRECTORY_SEPARATOR . $this->directory;
    }

}
