<?php

namespace Zip;

use ZipArchive;

class Zip
{
    /**
     * Base path
     */
    protected $basePath;

    /**
     * Zip file name
     */
    protected $zipFileName;

    /**
     * ZipArchive object
     */
    protected $zip;

    /**
     * Error in ZIP
     */
    protected $zipError       = array(
        ZIPARCHIVE::ER_EXISTS => 'File already exists',
        ZIPARCHIVE::ER_INCONS => 'Zip archive inconsistent',
        ZIPARCHIVE::ER_INVAL  => 'Invalid argument',
        ZIPARCHIVE::ER_MEMORY => 'Malloc failure',
        ZIPARCHIVE::ER_NOENT  => 'No such file',
        ZIPARCHIVE::ER_NOZIP  => 'Not a zip archive',
        ZIPARCHIVE::ER_OPEN   => 'Can\'t open file',
        ZIPARCHIVE::ER_READ   => 'Read error',
        ZIPARCHIVE::ER_SEEK   => 'Seek error'
   );

    /**
     * @param string $basePath Base path of zip
     * @param string $fileName Name of file
     * @throws \InvalidArgumentException
     */
    public function __construct($basePath = './', $fileName = null)
    {
        if (empty($basePath) === true) {
            throw new \InvalidArgumentException('There must be a base path in $basePath');
        }

        $this->setBasePath($basePath);

        if (empty($fileName) === false) {
            $this->setZipFileName($this->getBasePath() . DIRECTORY_SEPARATOR . $fileName);
        }
    }

    /**
     * Zip errors explained
     *
     * @return string
     * @throws \UnexpectedValueException
     */
    private function getZipError($error)
    {
        if (empty($error) === false && array_key_exists($error, $this->zipError) === true) {
            return $this->zipError[$error];
        } else {
            throw new \UnexpectedValueException('Error do not exists in this class, check manual for error: ' . $error);
        }
    }

    /**
     * Set base path directory
     *
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        if (file_exists($basePath) === false) {
            mkdir($basePath);
        }

        $this->basePath = realpath($basePath);
    }

    /**
     * Get base path directory
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Zip file name
     *
     * @param string $fileName
     */
    public function setZipFileName($fileName)
    {
        $this->zipFileName = $fileName;
    }

    /**
     * Return zip file name
     *
     * @return array
     */
    public function getZipFileName()
    {
        return $this->zipFileName;
    }

    /**
     * Shortest way to remove all directories structure
     *
     * @param string $path Path to remove
     * @param string $addBasePath
     *    true : include the base path
     *    false: do not include the base path
     */
    public function rrmdir($path, $addBasePath = false)
    {
        $basePath = ($addBasePath === true)
                  ? ($this->getBasePath() . DIRECTORY_SEPARATOR)
                  : '';
        $path     = realpath($basePath . $path);
        if (empty($path) === true) {
            return false;
        }

        return (is_file($path) === true)
            ? @unlink($path)
            : (array_map(array($this, 'rrmdir'), glob($path . '/*')) == @rmdir($path));
    }

    /**
     * @param null|string $fileName
     * @param integer     $flags
     * @throws \RuntimeException
     */
    public function open($fileName = null, $flags = 0)
    {
        $fileName = (empty($fileName) === true)
                  ? $this->zipFileName
                  : $fileName;
        if (empty($fileName) === true) {
            throw new \RuntimeException('$fileName cannot be empty');
        }
        $zip  = new ZipArchive();
        $open = $zip->open($fileName, $flags);
        if ($open === true) {
            $this->zip = $zip;
        } else {
            throw new \RuntimeException($this->getZipError($open));
        }

    }
}
