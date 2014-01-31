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
     * @return boolean
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

            if (!self::fileIsOk($ppt, $info))
                return false;

            $dir = DATA . $ppt;
            if (!is_dir($dir)) {
                if (!mkdir($dir))
                    throw new \Exception('Permission denied to directory "' . DATA . '"');
            }

            $savePath = DATA . $ppt . DIRECTORY_SEPARATOR . time() . '_' . preg_replace('/[^A-Z0-9._-]/i', '_', basename($info['name']));

            if (move_uploaded_file($info['tmpName'], $savePath)) {
                self::unlink($ppt);
                return $savePath;
            }
            else {
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
    final public static function fileIsOk($property, array $info) {
        if ($info['error'] !== UPLOAD_ERR_OK)
            return false;

        if (!self::sizeIsOk($info['size']))
            return false;

        $info = pathinfo($info['name']);
        if (!self::extensionIsOk($property, $info['extension']))
            return false;

        return true;
    }

    /**
     * Checks if the given extension is allowed for the given property
     * @param string $property
     * @param string $extension
     * @return boolean
     */
    final public static function extensionIsOk($property, $extension) {
        if (isset(self::$extensions[$property]) && !in_array($extension, self::$extensions[$property]))
            return false;
        if (isset(self::$badExtensions[$property]) && in_array($extension, self::$badExtensions[$property]))
            return false;

        return true;
    }

    /**
     * Checks if the given size is not bigger than the expected size for the property
     * @param int|string $size
     * @return boolean
     */
    final public static function sizeIsOk($size) {
        if ($size > self::getMaxSize()) {
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
    
    final public static function getMime() {
        
    }

}
