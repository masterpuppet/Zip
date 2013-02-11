<?php

namespace Zip;

use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Extract extends Zip
{
    /**
     * array with files to extract
     */
    protected $filesToExtract = array();

    /**
     * Destination directory
     */
    protected $destinationDir;

    /**
     * Destination temporary directory
     */
    protected $tmpDestinationDir;

    /**
     * Set up if search must check everything or be specific
     *    true : everything
     *    false: specific
     */
    protected $greedy         = true;

    /**
     * Stay with same structure
     */
    protected $sameStructure  = true;

    /**
     * Boolean to remove zip file
     */
    protected $removeZipFile  = false;

    /**
     * Boolean to remove temporary directory
     */
    protected $removeTmpDir   = false;

   /**
     * @param string $basePath               Base path of zip
     * @param string $fileName               Name of file
     * @param string|null $destinationDir    Destination to move allowed files or extract all zip file
     * @param string|null $tmpDestinationDir Destination to extract file and check mime
     * @throws \InvalidArgumentException
     */
    public function __construct($basePath = './', $fileName = null, $destinationDir = null, $tmpDestinationDir = null)
    {
        parent::__construct($basePath, $fileName);

        if (empty($tmpDestinationDir) === false) {
            $this->setTmpDestinationDir($this->getBasePath() . DIRECTORY_SEPARATOR . $tmpDestinationDir);
        }

        if (empty($destinationDir) === false) {
            $this->setDestinationDir($this->getBasePath() . DIRECTORY_SEPARATOR . $destinationDir);
        }
    }

    /**
     * Limit files to extracts
     *
     * @param string $file
     */
    public function setFileToExtract($file)
    {
        $this->filesToExtract[] = $file;
    }

    /**
     * Limit files to extracts
     *
     * @param string $file
     */
    public function setFilesToExtract(array $file)
    {
        $this->filesToExtract = $file;
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
     * @param string $destinationDir
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
     * @param string $tmpDestinationDir
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
     * @param boolean $bool
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
     * @param boolean $bool
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
     * Remove zip file
     *
     * @param boolean $bool
     */
    public function removeZipFile($bool)
    {
        $this->removeZipFile = $bool;
    }

    /**
     * Remove temporary directory
     *
     * @param boolean $bool
     */
    public function removeTmpDir($bool)
    {
        $this->removeTmpDir = $bool;
    }

    /**
     * Iterate directory
     *
     * @param string $path Path to iterate
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

        foreach ($this->iterateDir($path) as $k => $v) {
            $files[] = $v;
        }

        return $files;
    }

    /**
     * Move allowed file to new destination
     *
     * @param array $moveFiles
     * @return array
     * @throws \InvalidArgumentException
     */
    public function moveValidMime(array $moveFiles = array())
    {
        $filesMoved        = array();
        $destinationDir    = $this->getDestinationDir();
        $tmpDestinationDir = $this->getTmpDestinationDir();

        if (empty($destinationDir) === true) {
            throw new \InvalidArgumentException('There is no a destination declared in $destinationDir');
        }

        if (empty($tmpDestinationDir) === true) {
            throw new \InvalidArgumentException('There is no a temporary destination declared in $tmpDestinationDir');
        }

        if (empty($moveFiles) === false) {
            foreach ($moveFiles as $file) {
                $tmpFile = realpath($tmpDestinationDir . DIRECTORY_SEPARATOR . $file);

                if (empty($tmpFile) === false) {
                    $mime = Mime::isValidMime($tmpFile);

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
                foreach ($it as $k => $v) {
                    $mime = Mime::isValidMime($v);

                    if ($mime['isValid'] === true) {
                        $pathInfo    = pathinfo($v);
                        $destination = $destinationDir . DIRECTORY_SEPARATOR
                                     . $pathInfo['filename'] . '_' . microtime(true)
                                     . '.' . $pathInfo['extension'];
                        if (copy($v, $destination) === true) {
                            $filesMoved[] = array(
                                'original' => $v,
                                'destination' => $destination,
                            );
                        }
                    }
                }
            }
        }

        return $filesMoved;
    }

    /**
     * Extract allowed files to temporary directory
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function extractByExtension()
    {
        $tmpDestinationDir = $this->getTmpDestinationDir();

        if (empty($tmpDestinationDir) === true) {
            throw new \InvalidArgumentException('There is no a temporary destination declared in $tmpDestinationDir');
        }

        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file     = $this->zip->statIndex($i);
            $pathInfo = pathinfo($file['name']);

            if (array_key_exists('extension', $pathInfo) === true) {
                $mimeFiles = Mime::getMimeFiles();

                /**
                 * Include by specific extension or all if empty Mime::mimeFiles
                 */
                if ((array_key_exists($pathInfo['extension'], $mimeFiles) === true) || (empty($mimeFiles) === true)) {
                    $this->setFileToExtract($file['name']);
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
     * @throws \InvalidArgumentException
     */
    public function extractSpecificsFiles()
    {
        $files             = array();
        $destinationDir = $this->getDestinationDir();

        if (empty($destinationDir) === true) {
            throw new \InvalidArgumentException('There is no a destination declared in $destinationDir');
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
                throw new \InvalidArgumentException('There is no a temporary destination declared in $tmpDestinationDir');
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

                if (empty($file) === false && Mime::isValidMime($file) === false) {
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
     * @throws \InvalidArgumentException
     */
    public function extractAllFiles()
    {
        $destinationDir = $this->getDestinationDir();

        if (empty($destinationDir) === true) {
            throw new \InvalidArgumentException('There is no a destination declared in $destinationDir');
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