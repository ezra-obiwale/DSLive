<?php

/*
 */

namespace DSLive\Stdlib;

/**
 * Description of File
 *
 * @author topman
 */
class File {

    private static $extensions;
    private static $badExtensions;
    private static $maxSize;
    private static $mime;

    /**
     * Indicates whether to overwrite files with new if an existing file is found
     * @var boolean
     */
    private static $overwrite = true;

    /**
     * The names of the image being upload
     * @var array
     */
    private static $properties = array();
    private static $errors = array();

    /**
     * Adds a file extension type to a file property
     * @param string $property
     * @param string $ext
     * @return \DSLive\Stdlib\File
     * @throws \Exception
     */
    final public static function addExtension($property, $ext) {
        self::$extensions[$property][] = $ext;
    }

    /**
     * Sets the extensions for the given property. If any extensions existed for the property,
     * they will be overriden.
     * @param string $property
     * @param array $extensions
     * @return \DSLive\Stdlib\File
     * @throws \Exception
     */
    final public static function setExtensions($property, array $extensions) {
        self::$extensions[$property] = $extensions;
    }

    /**
     * Adds a bad file extension type to a file property
     * @param string $property
     * @param string $ext
     * @return \DSLive\Stdlib\File
     * @throws \Exception
     */
    final public static function addBadExtension($property, $ext) {
        self::$badExtensions[$property][] = $ext;
    }

    /**
     * Sets the bad extensions for the given property. If any bad extensions existed for the property,
     * they will be overriden.
     * @param string $property
     * @param array $extensions
     * @return \DSLive\Stdlib\File
     * @throws \Exception
     */
    final public static function setBadExtensions($property, array $extensions) {
        self::$badExtensions[$property] = $extensions;
    }

    /**
     * Sets the maximum size of the file to upload
     * @param int|string $size
     * @return \DSLive\Stdlib\File
     */
    final public static function setMaxSize($size) {
        self::$maxSize = $size;
    }

    /**
     * Fetches the byte value of the max filesize
     * If none is specified, uses the php_ini upload_max_filesize
     *
     * @return int
     */
    final public static function getMaxSize() {
        if (self::$maxSize === null) {
            self::$maxSize = ini_get('upload_max_filesize');
        }

        return self::parseSize(self::$maxSize);
    }

    /**
     * Uploads files to the server
     * @param array|\Object $files
     * @return boolean|array
     * @throws \Exception
     */
    final public static function uploadFiles($files) {
        if (is_object($files) && get_class($files) === 'Object') {
            $files = $files->toArray(true);
        }
        else if (is_object($files) || !is_array($files)) {
            throw new \Exception('Param $files must be either an object of type \Object or an array');
        }

        foreach ($files as $ppt => $info) {
            if (empty($info['name']))
                continue;

            if (!array_key_exists(self::$extensions, $ppt) && !array_key_exists(self::$badExtensions, $ppt))
                continue;

            $extension = self::fileIsOk($ppt, $info);
            if (!$extension)
                return false;

            $name = is_array($info['name']) ? $info['name'] : array($info['name']);

            $savePaths = array();
            $cnt = 1;
            foreach ($extension as $ky => $ext) {
                $public = ROOT . 'public';
                $dir = DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $ext . DIRECTORY_SEPARATOR;
                if (!is_dir($public . $dir)) {
                    if (!mkdir($public . $dir, 0777, true)) {
                        throw new \Exception('Permission denied to directory: "' . ROOT . 'public/"');
                    }
                }

                $nam = time() . '_' . preg_replace('/[^A-Z0-9._-]/i', '_', basename($name[$ky])) . '.' . $ext;

                $tmpName = (isset($info['tmpName'])) ? $info['tmpName'] : $info['tmp_name'];
                $source = is_array($tmpName) ? $tmpName[$ky] : $tmpName;
                if (!move_uploaded_file($source, $public . $dir . $nam)) {
                    return false;
                }
                $savePaths[$cnt] = $dir . $nam;
                $cnt++;
            }
        }
        return $savePaths;
    }

    /**
     * Checks the info against property settings
     * @param string $property
     * @param array $info
     * @return boolean
     */
    final public static function fileIsOk($property, array $info) {
        $error = is_array($info['error']) ? $info['error'] : array($info['error']);
        foreach ($error as $err) {
            if ($err !== UPLOAD_ERR_OK) {
                self::$errors[] = 'File not found';
                return false;
            }
        }

        if (!self::sizeIsOk($info['size'])) {
            self::$errors[] = 'File size too big';
            return false;
        }

        self::$mime = is_array($info['type']) ? serialize($info['type']) : $info['type'];
        return self::extensionIsOk($property, $info['name']);
    }

    /**
     * Checks if the given extension is allowed for the given property
     * @param string $property
     * @param string $extension
     * @return boolean
     */
    final public static function extensionIsOk($property, $name) {
        $name = is_array($name) ? $name : array($name);
        $extensions = array();
        foreach ($name as $nm) {
            $info = pathinfo($nm);
            $extension = strtolower(@$info['extension']);
            if ((isset(self::$extensions[$property]) && !in_array($extension, self::$extensions[$property])) || (isset(self::$badExtensions[$property]) && in_array($extension, self::$badExtensions[$property]))) {
                self::$errors[] = 'File extension not allowed';
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
    final public static function sizeIsOk($size) {
        $size = is_array($size) ? $size : array($size);
        foreach ($size as $sz) {
            if ($sz > self::getMaxSize()) {
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
    final public static function parseSize($size) {
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
     * @param string $path
     * @return boolean
     */
    final public static function unlink($path) {
        if (is_file($path))
            return unlink($path);

        return true;
    }

    /**
     * Fetches the mime attribute of the file
     * @return string
     */
    public static function getMime() {
        return self::$mime;
    }

    /**
     * Sets the mime attribute of the file
     * @param string $mime
     * @return \DSLive\Models\File
     */
    public static function setMime($mime) {
        self::$mime = $mime;
    }

    /**
     * Fetches the overwrite option
     * @return boolean
     */
    public static function getOverwrite() {
        return self::$overwrite;
    }

    /**
     * Indicates whether to overwrite file if existing or not
     * @param boolean $overwrite
     * @return \DSLive\Models\File
     */
    public static function setOverwrite($overwrite) {
        self::$overwrite = $overwrite;
    }

    /**
     * Fetches the errors that occurred during file upload
     * @return array
     */
    final public static function getErrors() {
        return self::$errors;
    }

}
