<?php

namespace Zip;

class Mime
{
    /**
     * Path to magic.mime file
     *
     * @var null|string
     */
    protected static $magicMime = null;

    /**
     * array with default accepted mime files
     *
     * Example:
     *     Specify a single mime extension
     *     \Zip\Mime::setMimeFile('jpg', 'image/jpeg');
     *     \Zip\Mime::setMimeFile('gif', 'image/gif');
     *     
     *     Specify multiple mimes to an extension
     *     \Zip\Mime::setMimeFile('txt', array('text/plain', 'application/x-empty'));
     *
     *     Setup multiple mimes
     *     $mimes = array(
     *         'jpg' => 'image/jpeg',
     *         'txt' => array(
     *             'text/plain',
     *             'application/x-empty',
     *         ),
     *     );
     *     \Zip\Mime::setMimeFiles($mimes);
     *
     * @var array
     */
    protected static $mimeFiles = array();

    /**
     * @var boolean
     */
    protected static $validateMime = false;

    /**
     * @param string $fileName
     * @return boolean
     */
    public static function isValidMime($fileName)
    {
        if (self::$validateMime === false) {
            return true;
        }

        $isValid   = false;
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $mimeFiles = self::getMimeFiles();
        $finfo     = new \finfo(FILEINFO_MIME, self::getMagicMime());
        $e         = explode(';', $finfo->file($fileName));
        if (empty($e[0]) === true) {
            return false;
        }

        if (array_key_exists($extension, $mimeFiles) === true && is_array($mimeFiles[$extension]) === true) {
            $isValid = in_array($e[0], $mimeFiles[$extension]);
        } elseif (array_key_exists($extension, $mimeFiles) === true) {
            $isValid = ($e[0] == $mimeFiles[$extension]);
        }

        return $isValid;
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
     * Set allowed mime and setup self::$validateMime to true
     *
     * @param string $extension Extension value
     * @param string|array $mime Mime value
     * @see \Zip\Mime::$mimeFiles For examples
     * @throws \InvalidArgumentException
     */
    public static function setMimeFile($extension, $mime)
    {
        if (empty($extension) === true) {
            throw new \InvalidArgumentException('$extension cannot be empty');
        }

        if (empty($mime) === true) {
            throw new \InvalidArgumentException('$mime cannot be empty');
        }

        self::$mimeFiles[$extension] = $mime;

        self::validateMime(true);
    }

    /**
     * Set allowed mime and setup self::$validateMime to true
     *
     * @param array $mimes
     * @see \Zip\Mime::$mimeFiles For examples
     * @throws \InvalidArgumentException
     */
    public static function setMimeFiles(array $mimes)
    {
        if (empty($mimes) === true) {
            throw new \InvalidArgumentException('$mimes cannot be empty');
        }

        foreach ($mimes as $extension => $mime) {
            self::$mimeFiles[$extension] = $mime;
        }

        self::validateMime(true);
    }

    /**
     * @param boolean $bool
     */
    public static function validateMime($bool)
    {
        self::$validateMime = $bool;
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
                if (in_array($mime, $localMimeFiles) === false) {
                    $localMimeFiles[] = $mime;
                }
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