<?php

namespace Zip;

use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveArrayIterator;

class Zip
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $zipFileName;

    /**
     * @var ZipArchive
     */
    protected $zip;

    /**
     * @var string
     */
    protected $mode = '0666';

    /**
     * @var array
     */
    protected $zipError = array(
        ZIPARCHIVE::ER_MULTIDISK   => 'Multi-disk zip archives not supported',
        ZIPARCHIVE::ER_RENAME      => 'Renaming temporary file failed',
        ZIPARCHIVE::ER_CLOSE       => 'Closing zip archive failed',
        ZIPARCHIVE::ER_EXISTS      => 'File already exists',
        ZIPARCHIVE::ER_TMPOPEN     => 'Failure to create temporary file',
        ZIPARCHIVE::ER_ZLIB        => 'Zlib error',
        ZIPARCHIVE::ER_CHANGED     => 'Entry has been changed',
        ZIPARCHIVE::ER_COMPNOTSUPP => 'Compression method not supported',
        ZIPARCHIVE::ER_EOF         => 'Premature EOF',
        ZIPARCHIVE::ER_INTERNAL    => 'Internal error',
        ZIPARCHIVE::ER_INCONS      => 'Zip archive inconsistent',
        ZIPARCHIVE::ER_REMOVE      => 'Can\'t remove file',
        ZIPARCHIVE::ER_DELETED     => 'Entry has been deleted',
        ZIPARCHIVE::ER_INVAL       => 'Invalid argument',
        ZIPARCHIVE::ER_MEMORY      => 'Malloc failure',
        ZIPARCHIVE::ER_NOENT       => 'No such file',
        ZIPARCHIVE::ER_NOZIP       => 'Not a zip archive',
        ZIPARCHIVE::ER_OPEN        => 'Can\'t open file',
        ZIPARCHIVE::ER_WRITE       => 'Write error',
        ZIPARCHIVE::ER_READ        => 'Read error',
        ZIPARCHIVE::ER_SEEK        => 'Seek error',
        ZIPARCHIVE::ER_CRC         => 'CRC error',
        ZIPARCHIVE::ER_ZIPCLOSED   => 'Containing zip archive was closed',
        
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

        /**
         * Fix base path so it get the correct directory separator and trim the last one if got any
         */
        $this->setBasePath($basePath);

        if (empty($fileName) === false) {
            $this->setZipFileName($fileName);
        }
    }

    /**
     * @param null|string $fileName
     * @param integer     $flags
     * @return \Zip
     * @throws \RuntimeException
     */
    public function open($fileName = null, $flags = 0)
    {
        $fileName = (empty($fileName) === true)
                  ? $this->getZipFileName()
                  : $fileName;
        if (empty($fileName) === true) {
            throw new \RuntimeException('$fileName cannot be empty');
        }
        $fileName = $this->getBasePath() . DIRECTORY_SEPARATOR . $fileName;
        $zip      = new ZipArchive();
        $open     = $zip->open($fileName, $flags);
        if ($open === true) {
            $this->zip = $zip;
        } else {
            throw new \RuntimeException($this->getZipError($open));
        }

        return $this;
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
            throw new \RuntimeException($this->zipError[$error]);
        } else {
            throw new \UnexpectedValueException('Error do not exists in this class, check manual for error: ' . $error);
        }
    }

    /**
     * @param string $basePath Base path directory
     * @return \Zip
     */
    public function setBasePath($basePath)
    {
        $basePath = rtrim(preg_replace('~(\\+)|(/+)~', DIRECTORY_SEPARATOR, $basePath), DIRECTORY_SEPARATOR);

        if (file_exists($basePath) === false) {
            mkdir($basePath, $this->getMode(), true);
        }

        $this->basePath = realpath($basePath);

        return $this;
    }

    /**
     * @return string Base path directory
     * @return \Zip
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string $fileName Zip file name
     * @return \Zip
     */
    public function setZipFileName($fileName)
    {
        $this->zipFileName = $fileName;

        return $this;
    }

    /**
     * @return string Zip file name
     */
    public function getZipFileName()
    {
        return $this->zipFileName;
    }

    /**
     * @return ZipArchive
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $mode
     * @return \Zip
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Iterate directory
     *
     * @param string  $path Path to iterate
     * @param boolean $toArray
     * @return RecursiveIteratorIterator|array
     */
    public function iterateDir($path, $toArray = false)
    {
        if (empty($path) === true) {
            return array();
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        if ($toArray === true) {
            $files = array();
            foreach ($it as $k => $v) {
                $files[] = $v->getRealPath();
            }
            $it = $files;
        }

        return $it;
    }

    /**
     * @param string $path Path to remove
     * @param string $addBasePath
     *    true : include the base path
     *    false: do not include the base path
     * @return boolean
     */
    public function removeFiles($path, $addBasePath = false)
    {
        $dir      = array();
        $basePath = ($addBasePath === true)
                  ? ($this->getBasePath() . DIRECTORY_SEPARATOR)
                  : '';
        $path     = realpath($basePath . $path);
        if (empty($path) === true) {
            return false;
        }
        chmod($path, $this->getMode());


        foreach (array_reverse($this->iterateDir($path, true)) as $k => $v) {
            chmod($v, $this->getMode());
            if (is_dir($v) === true) {
                rmdir($v);
            } else {
                unlink($v);
            }
        }

        return rmdir($path);
    }

    /**
     * @throws \BadMethodCallException
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this->zip, $method) === true) {
            call_user_func_array(array($this->zip, $method), $arguments);
        } else {
            throw new \BadMethodCallException('Method "' .  __CLASS__ . '::' . $method . '" do not exists');
        }

        return $this;
    }

    /**
     * Close object \ZipArchive()
     */
    public function __destruct()
    {
        if (($this->zip instanceof ZipArchive) === true) {
            $this->zip->close();
        }
    }
}
