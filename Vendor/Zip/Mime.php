<?php

namespace Zip;

class Mime
{
    /**
     * Path to magic.mime file
     */
    protected static $magicMime = null;

    /**
     * array with default accepted mime files
     */
    protected static $mimeFiles = array(
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'bmp'  => 'image/bmp',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'txt' => array(
            'text/plain',
            'application/x-empty',
        ),
        'doc' => array(
            'application/msword',
        ),
        'docx' => array(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ),
        'xls' => array(
            'application/vnd.ms-excel',
        ),
        'xlsx' => array(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ),
        'ppt' => array(
            'application/vnd.ms-powerpoint',
            'application/vnd.ms-office',
        ),
        'pptx' => array(
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
        ),
        'pdf' => 'application/pdf',
    );

    /**
     * @param string $fileName
     * @throws \RuntimeException
     */
    public static function isValidMime($fileName)
    {
        $isValid   = false;
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeFiles = self::getMimeFiles();
        $finfo     = new \finfo(FILEINFO_MIME, self::getMagicMime());
        $e         = explode(';', $finfo->file($fileName));
        if (empty($e[0]) === true) {
            throw new \RuntimeException('Error getting mime type from "' . $fileName . '"');
        }

        if (array_key_exists($extension, $mimeFiles) === true && is_array($mimeFiles[$extension]) === true) {
            $isValid = in_array($e[0], $mimeFiles[$extension]);
        } elseif (array_key_exists($extension, $mimeFiles) === true) {
            $isValid = ($e[0] == $mimeFiles[$extension]);
        }

        return array(
            'isDir'   => ($e[0] == 'directory'),
            'isFile'  => ($e[0] != 'directory'),
            'isValid' => ($isValid === true),
        );
    }

    /**
     * Get base key from array (Recursively)
     *
     * @param array   $input
     * @param mixed   $searchValue
     * @param boolean $strict Default false
     * @param string  $keyBase
     * @return array
     */
    public static function arrayKeysRecursive(array $input, $searchValue = null, $strict = false, $keyBase = null)
    {
        $keysFound = array();
        $keys      = array();

        foreach ($input as $key => $value) {
            if (($strict === true && $value === $searchValue) || ($strict === false && $value == $searchValue)) {
                /**
                 * The key got the path
                 */
                $keysFound[$key] = null;
            }

            if (is_array($value) === true) {
                $keys[$key] = self::arrayKeysRecursive($value, $searchValue, $strict);
            }

            if (empty($keys) === false) {
                $keysFound = array_merge($keysFound, $keys);
            }
        }

        return $keysFound;
    }

    /**
     * Set allowed mime
     *
     * @param string $extension Extension value
     *               Example: jpg
                              txt
     * @param string|array $mime Mime value
     *               Example: image/jpeg
     *                        array('text/plan', 'application/x-empty')
     */
    public static function setMimeFiles($extension, $mime)
    {
        self::$mimeFiles[$extension] = $mime;
    }

    /**
     * Get all allowed mimes
     *
     * @param boolean $flatArray If true, flat array to have all values together
     *     Some extension can have differents mimes for example:
     *         txt: text/plain | application/x-empty
     * @return array
     */
    public static function getMimeFiles($flatArray = false)
    {
        $localMimeFiles = array();

        if ($flatArray === true) {
            array_walk_recursive(self::$mimeFiles, function ($mime, $extension) use (&$localMimeFiles) {
                $localMimeFiles[] = $mime;
            });
        } else {
            $localMimeFiles = self::$mimeFiles;
        }

        return $localMimeFiles;
    }

    /**
     * Set path of magic.mime
     *
     * @param string|null $path Path to magic.mime file
     * @throws \RuntimeException
     */
    public static function setMagicMime($path = null)
    {
        $path = realpath($path);

        if (empty($path) === true) {
            throw new \RuntimeException('Path is not correct for magic.mime file');
        }

        self::$magicMime = $path;
    }

    /**
     * Get path of magic.mime file
     *
     * @return string
     */
    public static function getMagicMime()
    {
        return self::$magicMime;
    }

    /**
     * Unset recursively
     *
     * @param array $keys
     * @param array $values
     * @param boolean $specific
     *     true : if want to unset a specific key (must have the same structure as $values)
     *     false: if want to unset in any part of $values
     */
    protected function recursiveUnset(array $keys, array &$values = array(), $specific = false)
    {
        if ($specific === true) {
            foreach ($keys as $key => $keyValue) {
                if (is_array($keyValue) === true) {
                    self::recursiveUnset($keyValue, $values[$key], $specific);
                }
                else {
                    unset($values[$key]);
                }
            }
        } else {
            foreach ($values as $key => &$value) {
                if (in_array($key, $keys) === true){
                    unset($values[$key]);
                } elseif(is_array($value) === true) {
                    self::recursiveUnset($keys, $value, $specific);
                }
            }
        }
    }

    /**
     * Unset specific mime by key (extension name) in self::$mimeFiles
     *
     * @param string $keys
     * @param boolean $specific
     *     true : if wants to unset a specific key (must have the same structure as self::$mimeFiles)
     *     false: if want to unset in any part of self::$mimeFiles
     */
    public static function unsetMimeByKey(array $keys, $specific = false)
    {
        self::recursiveUnset($keys, self::$mimeFiles, $specific);
    }

    /**
     * Unset specific mime by value in self::$mimeFiles
     *
     * @param array $values
     */
    public static function unsetMimeByValue(array $values)
    {
        foreach ($values as $value) {
            self::recursiveUnset(self::arrayKeysRecursive(self::$mimeFiles, $value), self::$mimeFiles, true);
        }
    }

    /**
     * Unset all values in self::$mimeFiles
     */
    public static function unsetAllMime()
    {
        self::$mimeFiles = array();
    }
}