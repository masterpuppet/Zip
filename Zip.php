<?php

class Zip
{
    /**
     * Zip file name
     */
    private $zipFileName;

    /**
     * Base path
     */
    private $basePath;

    /**
     * ZipArchive object
     */
    private $zip;

    /**
     * array with files to extract
     */
    private $filesToExtract = array();

    /**
     * Destination directory
     */
    private $destinationDir;

    /**
     * Destination temporary directory
     */
    private $tmpDestinationDir;

    /**
     * Set up if search must check everything or be specific
     *    true : everything
     *    false: specific
     */
    private $greedy         = true;

    /**
     * Stay with same structure
     */
    private $sameStructure  = true;

    /**
     * Error in ZIP
     */
    private $zipError       = array(
        ZIPARCHIVE::ER_EXISTS => 'File already exists',
        ZIPARCHIVE::ER_INCONS => 'Zip archive inconsistent',
        ZIPARCHIVE::ER_INVAL  => 'Invalid argument',
        ZIPARCHIVE::ER_MEMORY => 'Malloc failure',
        ZIPARCHIVE::ER_NOENT => 'No such file',
        ZIPARCHIVE::ER_NOZIP => 'Not a zip archive',
        ZIPARCHIVE::ER_OPEN => 'Can\'t open file',
        ZIPARCHIVE::ER_READ => 'Read error',
        ZIPARCHIVE::ER_SEEK => 'Seek error'
   );

    /**
     * array with default accepted mime files
     */
    private $mimeFiles      = array(
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
        'bmp' => 'image/bmp',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'txt' => array(
            'text/plain',
            'application/x-empty',
        ),
   );

    /**
     * Path to magic.mime file
     */
    private $magicMime      = null;

    /**
     * Boolean to remove zip file
     */
    private $removeZipFile  = false;

    /**
     * Boolean to remove temporary directory
     */
    private $removeTmpDir   = false;

    /**
     * @param $basePath string Base path of zip
     * @param $fileName string Name of file
     * @param $destinationDir string|null Destination to move allowed files or extract all zip file
     * @param $tmpDestinationDir string|null Destination to extract file and check mime
     * @throws InvalidArgumentException
     */
    public function __construct($basePath = './', $fileName = null, $destinationDir = null, $tmpDestinationDir = null)
    {
        if (empty($basePath) === true) {
            throw new InvalidArgumentException('There must be a base path in $basePath');
        }

        $this->setBasePath($basePath);

        if (empty($fileName) === false) {
            $this->setZipFileName($this->getBasePath() . DIRECTORY_SEPARATOR . $fileName);
        }

        if (empty($tmpDestinationDir) === false) {
            $this->setTmpDestinationDir($this->getBasePath() . DIRECTORY_SEPARATOR . $tmpDestinationDir);
        }

        if (empty($destinationDir) === false) {
            $this->setDestinationDir($this->getBasePath() . DIRECTORY_SEPARATOR . $destinationDir);
        }
    }

    /**
     * Zip errors explained
     *
     * @return string
     * @throws UnexpectedValueException
     */
    private function getZipError($error)
    {
        if (empty($error) === false && array_key_exists($error, $this->zipError) === true) {
            return $this->zipError[$error];
        } else {
            throw new UnexpectedValueException('Error do not exists in this class, check manual for error: ' . $error);
        }
    }

    /**
     * Get base key from array (Recursively)
     *
     * @param $input array
     * @param $searchValue mixed
     * @param $strict boolean Default false
     * @param $keyBase string
     * @return array
     */
    protected function arrayKeysRecursive(array $input, $searchValue = null, $strict = false, $keyBase = null)
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
                $keys[$key] = $this->arrayKeysRecursive($value, $searchValue, $strict);
            }

            if (empty($keys) === false) {
                $keysFound = array_merge($keysFound, $keys);
            }
        }

        return $keysFound;
    }

    /**
     * Unset recursively
     *
     * @param $keys array
     * @param $values array
     * @param $specific boolean
     *     true : if want to unset a specific key (must have the same structure as $values)
     *     false: if want to unset in any part of $values
     */
    protected function recursiveUnset(array $keys, array &$values = array(), $specific = false)
    {
        if ($specific === true) {
            foreach ($keys as $key => $keyValue) {
                if (is_array($keyValue) === true) {
                    $this->recursiveUnset($keyValue, $values[$key], $specific);
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
                    $this->recursiveUnset($keys, $value, $specific);
                }
            }
        }
    }

    /**
     * Set base path directory
     *
     * @param $basePath string
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
     * @param $fileName string
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
     * Limit files to extracts
     *
     * @param $file string
     */
    public function setFilesToExtract($file)
    {
        $this->filesToExtract[] = $file;
    }

    /**
     * Return files to extract
     *
     * @return array
     */
    public function getFilesToExtract()
    {
        return $this->filesToExtract;
    }

    /**
     * Set destination directory
     *
     * @param $destinationDir string
     */
    public function setDestinationDir($destinationDir)
    {
        if (file_exists($destinationDir) === false) {
            mkdir($destinationDir);
        }

        $this->destinationDir = realpath($destinationDir);
    }

    /**
     * Get destination directory
     *
     * @return string
     */
    public function getDestinationDir()
    {
        return $this->destinationDir;
    }

    /**
     * Set temporary destination directory
     *
     * @param $tmpDestinationDir string
     */
    public function setTmpDestinationDir($tmpDestinationDir)
    {
        if (file_exists($tmpDestinationDir) === false) {
            mkdir($tmpDestinationDir);
        }

        $this->tmpDestinationDir = realpath($tmpDestinationDir);
    }

    /**
     * Get temporary destination directory
     *
     * @return string
     */
    public function getTmpDestinationDir()
    {
        return $this->tmpDestinationDir;
    }

    /**
     * @param $bool boolean
     */
    public function setGreedy($bool)
    {
        $this->greedy = $bool;
    }

    /**
     * Get if search must check everything of specific
     */
    public function getGreedy()
    {
        return $this->greedy;
    }

    /**
     * @param $bool boolean
     */
    public function setSameStructure($bool)
    {
        $this->sameStructure = $bool;
    }

    /**
     * Get same structure or file only
     */
    public function getSameStructure()
    {
        return $this->sameStructure;
    }

    /**
     * Set allowed mime
     *
     * @param $key string Extension value
     *               Example: jpg
                              txt
     * @param $value string|array Mime value
     *               Example: image/jpeg
     *                        array('text/plan', 'application/x-empty')
     */
    public function setMimeFiles($key, $value)
    {
        $this->mimeFiles[$key] = $value;
    }

    /**
     * Get all allowed mimes
     *
     * @param flatArray boolean If true, flat array to have all values together
     *     Some extension can have differents mimes for example:
     *         txt: text/plain | application/x-empty
     * @return array
     */
    public function getMimeFiles($flatArray = false)
    {
        $localMimeFiles = array();

        if ($flatArray === true) {
            array_walk_recursive($this->mimeFiles, function ($value, $key) use (&$localMimeFiles) {
                $localMimeFiles[] = $value;
            });
        } else {
            $localMimeFiles = $this->mimeFiles;
        }

        return $localMimeFiles;
    }

    /**
     * Set path of magic.mime
     *
     * @param $path string|null Path to magic.mime file
     * @throws RuntimeException
     */
    public function setMagicMime($path = null)
    {
        $path = realpath($path);

        if (empty($path) === true) {
            throw new RuntimeException('Path is not correct for magic.mime file');
        }

        $this->magicMime = $path;
    }

    /**
     * Get path of magic.mime file
     *
     * @return string
     */
    public function getMagicMime()
    {
        return $this->magicMime;
    }

    /**
     * Unset specific mime by key (extension name) in $this->mimeFiles
     *
     * @param $keys string
     * @param $specific boolean
     *     true : if wants to unset a specific key (must have the same structure as $this->mimeFiles)
     *     false: if want to unset in any part of $this->mimeFiles
     */
    public function unsetMimeByKey(array $keys, $specific = false)
    {
        $this->recursiveUnset($keys, $this->mimeFiles, $specific);
        var_dump($this->mimeFiles);exit;
    }

    /**
     * Unset specific mime by value in $this->mimeFiles
     *
     * @param $values array
     */
    public function unsetMimeByValue(array $values)
    {
        foreach ($values as $value) {
            $this->recursiveUnset($this->arrayKeysRecursive($this->mimeFiles, $value), $this->mimeFiles, true);
        }
    }

    /**
     * Unset all values in $this->mimeFiles
     */
    public function unsetAllMime()
    {
        $this->mimeFiles = array();
    }

    /**
     * Shortest way to remove all directories structure
     *
     * @param $path string Path to remove
     * @param $addBasePath string 
     *    true : include the base path
     *    false: do not include the base path
     */
    public function rrmdir($path, $addBasePath = false)
    {
        $path = realpath((($addBasePath === true) ? ($this->getBasePath() . DIRECTORY_SEPARATOR) : '') . $path);

        return (is_file($path) === true)
               ? @unlink($path)
               : (array_map(array($this, 'rrmdir'), glob($path . '/*')) == @rmdir($path));
    }

    /**
     * Remove zip file
     *
     * @param $bool boolean
     */
    public function removeZipFile($bool)
    {
        $this->removeZipFile = $bool;
    }

    /**
     * Remove temporary directory
     *
     * @param $bool boolean
     */
    public function removeTmpDir($bool)
    {
        $this->removeTmpDir = $bool;
    }

    /**
     * @param $flags integer
     * @throws RuntimeException
     */
    public function open($flags = 0)
    {
        $zip = new ZipArchive();
        $open = $zip->open($this->zipFileName, $flags);
        if ($open === true) {
            $this->zip = $zip;
        } else {
            throw new RuntimeException($this->getZipError($open));
        }

    }

    /**
     * Iterate directory
     *
     * @param $path string Path to iterate
     */
    public function iterateDir($path)
    {
        if (empty($path) === true) {
            return false;
        }

        $dir = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);

        return new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * Get list from a specific directory
     *
     * @return array
     */
    public function listFiles($path)
    {
        $files = array();
        $it    = $this->iterateDir($path);

        while ($it->valid() === true) {
            $files[] = $it->key();

            $it->next();
        }

        return $files;
    }

    /**
     * @param $fileName string
     */
    public function isValidMime($fileName)
    {
        $mimeFiles = $this->getMimeFiles();
        $finfo     = new finfo(FILEINFO_MIME, $this->getMagicMime());
        $e         = explode(';', $finfo->file($fileName));

        return array(
            'isDir' => ($e[0] == 'directory'),
            'isFile' => ($e[0] != 'directory'),
            'isValid' => (empty($mimeFiles) === true || in_array($e[0], $this->getMimeFiles(true)) === true),
        );
    }

    /**
     * Move allowed file to new destination
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function moveValidMime(array $moveFiles = array())
    {
        $filesMoved        = array();
        $destinationDir    = $this->getDestinationDir();
        $tmpDestinationDir = $this->getTmpDestinationDir();

        if (empty($destinationDir) === true) {
            throw new InvalidArgumentException('There is no a destination declared in $destinationDir');
        }

        if (empty($tmpDestinationDir) === true) {
            throw new InvalidArgumentException('There is no a temporary destination declared in $tmpDestinationDir');
        }

        if (empty($moveFiles) === false) {
            foreach ($moveFiles as $file) {
                $tmpFile = realpath($tmpDestinationDir . DIRECTORY_SEPARATOR . $file);

                if (empty($tmpFile) === false) {
                    $mime = $this->isValidMime($tmpFile);

                    if ($mime['isValid'] === true) {
                        $pathInfo = pathinfo($file);
                        $destination = $destinationDir . DIRECTORY_SEPARATOR
                                     . $pathInfo['dirname'] . DIRECTORY_SEPARATOR;

                        /**
                         * mkdir send an error if one of the directories exists
                         */
                        @mkdir($destination, '0666', true);

                        $destination .= $pathInfo['filename'] . '.' . $pathInfo['extension'];

                        if (copy($tmpFile, $destination) === true) {
                            $filesMoved[] = array('original' => $tmpFile,
                                                   'destination' => $destination,);
                        }
                    }
                }
            }
        } else {
            $it = $this->iterateDir($tmpDestinationDir);

            if ($it !== false) {
                while ($it->valid() === true) {
                    $mime = $this->isValidMime($it->key());

                    if ($mime['isValid'] === true) {
                        $pathInfo    = pathinfo($it->key());
                        $destination = $destinationDir . DIRECTORY_SEPARATOR
                                     . $pathInfo['filename'] . '_' . microtime(true)
                                     . '.' . $pathInfo['extension'];
                        if (copy($it->key(), $destination) === true) {
                            $filesMoved[] = array('original' => $it->key(),
                                                   'destination' => $destination,);
                        }
                    }

                    $it->next();
                }
            }
        }

        return $filesMoved;
    }

    /**
     * Extract allowed files to temporary directory
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function extractByExtension()
    {
        $tmpDestinationDir = $this->getTmpDestinationDir();

        if (empty($tmpDestinationDir) === true) {
            throw new InvalidArgumentException('There is no a temporary destination declared in $tmpDestinationDir');
        }

        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file     = $this->zip->statIndex($i);
            $pathInfo = pathinfo($file['name']);

            if (array_key_exists('extension', $pathInfo) === true) {
                $mimeFiles = $this->getMimeFiles();

                /**
                 * Include by specific extension or all if empty $this->mimeFiles
                 */
                if ((array_key_exists($pathInfo['extension'], $mimeFiles) === true) || (empty($mimeFiles) === true)) {
                    $this->setFilesToExtract($file['name']);
                }
            }
        }

        $this->zip->extractTo($tmpDestinationDir, $this->getFilesToExtract());

        return $this->moveValidMime();
    }

    /**
     * Extract specifics files
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function extractSpecificsFiles()
    {
        $files             = array();
        $destinationDir = $this->getDestinationDir();

        if (empty($destinationDir) === true) {
            throw new InvalidArgumentException('There is no a destination declared in $destinationDir');
        }

        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file     = $this->zip->statIndex($i);
            $pathInfo = pathinfo($file['name']);

            if (empty($pathInfo['extension']) === false) {
                $fileName = $pathInfo['filename'] . '.' . $pathInfo['extension'];

                /**
                 * Check first if user write full path or check if user only write the name of the file and want to check everything
                 */
                if(in_array($file, $this->getFilesToExtract()) === true
                    || (in_array($fileName, $this->getFilesToExtract()) === true && $this->getGreedy() === true)){
                    $files[] = $file['name'];
                }
            }
        }

        if ($this->getSameStructure() === true) {
            $tmpDestinationDir = $this->getTmpDestinationDir();

            if (empty($tmpDestinationDir) === true) {
                throw new InvalidArgumentException('There is no a temporary destination declared in $tmpDestinationDir');
            }

            $this->zip->extractTo($tmpDestinationDir, $files);

            return $this->moveValidMime($files);
        } else {
            $this->zip->extractTo($destinationDir, $files);

            /**
             * Verify if is a valid mime
             */
            foreach ($files as $file) {
                $file = realpath($destinationDir . DIRECTORY_SEPARATOR . $file);

                if (empty($file) === false && $this->isValidMime($file) === false) {
                    unlink($file);
                }
            }

            return $files;
        }
    }

    /**
     * Extract all files
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function extractAllFiles()
    {
        $destinationDir = $this->getDestinationDir();

        if (empty($destinationDir) === true) {
            throw new InvalidArgumentException('There is no a destination declared in $destinationDir');
        }

        $this->zip->extractTo($destinationDir);

        return $this->listFiles($destinationDir);
    }

    /**
     * Close object ZipArchive()
     */
    public function __destruct()
    {
        if (($this->zip instanceof ZipArchive) === true) {
            $this->zip->close();

            if ($this->removeTmpDir === true) {
                $this->rrmdir($this->getTmpDestinationDir(), false);
            }

            if ($this->removeZipFile === true) {
                unlink($this->zipFileName);
            }
        }
    }
}
