<?php
class Zip
{
	/**
	 * Zip file name
	 */
	private $_zipFileName;

	/**
	 * Base path for all
	 */
	private $_basePath;

	/**
	 * ZipArchive object
	 */
	private $_zip;

	/**
	 * array with files to extract
	 */
	private $_filesToExtract = array();

	/**
	 * Destination directory
	 */
	private $_destinationDir;

	/**
	 * Destination temporary directory
	 */
	private $_tmpDestinationDir;

	/**
	 * Set up if search must check everything or be specific
	 *    true : everything
	 *    false: specific
	 */
	private $_greedy         = true;

	/**
	 * Stay with same structure
	 */
	private $_sameStructure  = true;

	/**
	 * Error in ZIP
	 */
	private $_zipError       = array( ZIPARCHIVE::ER_EXISTS => 'File already exists',
	                                  ZIPARCHIVE::ER_INCONS => 'Zip archive inconsistent',
	                                  ZIPARCHIVE::ER_INVAL => 'Invalid argument',
	                                  ZIPARCHIVE::ER_MEMORY => 'Malloc failure',
	                                  ZIPARCHIVE::ER_NOENT => 'No such file',
	                                  ZIPARCHIVE::ER_NOZIP => 'Not a zip archive',
	                                  ZIPARCHIVE::ER_OPEN => 'Can\'t open file',
	                                  ZIPARCHIVE::ER_READ => 'Read error',
	                                  ZIPARCHIVE::ER_SEEK => 'Seek error', );

	/**
	 * array with default accepted mime files
	 */
	private $_mimeFiles      = array( 'jpg' => 'image/jpeg',
	                                  'jpeg' => 'image/jpeg',
	                                  'gif' => 'image/gif',
	                                  'png' => 'image/png',
	                                  'bmp' => 'image/bmp',
	                                  'tiff' => 'image/tiff',
	                                  'tif' => 'image/tiff',
	                                  'txt' => 'text/plain',
	                                  'txt' => 'application/x-empty', );

	/**
	 * Path to magic.mime file
	 */
	private $_magicMime      = null;

	/**
	 * Boolean to remove zip file
	 */
	private $_removeZipFile  = false;

	/**
	 * Boolean to remove temporary directory
	 */
	private $_removeTmpDir   = false;




	/**
	 * @param $fileName string Name of file
	 * @param $destinationDir string|null Destination to move allowed files or extract all zip file
	 * @param $tmpDestinationDir string|null Destination to extract file and check mime
	 * @param $basePath string Base path of zip
	 */
	public function __construct($basePath = './', $fileName = null, $destinationDir = null, $tmpDestinationDir = null)
	{
		if( empty($basePath) === true ){
			throw new InvalidArgumentException('$basePath must be a string and cannot be empty');
		}
	
		$this->setBasePath($basePath);

		if( empty($fileName) === false ){
			$this->setZipFileName($this->getBasePath() . DIRECTORY_SEPARATOR . $fileName);
		}

		if( empty($tmpDestinationDir) === false ){
			$this->setTmpDestinationDir($this->getBasePath() . DIRECTORY_SEPARATOR . $tmpDestinationDir);
		}

		if( empty($destinationDir) === false ){
			$this->setDestinationDir($this->getBasePath() . DIRECTORY_SEPARATOR . $destinationDir);
		}
	}




	/**
	 * Set base path directory
	 *
	 * @param $basePath string
	 */
	public function setBasePath($basePath)
	{
		if( file_exists($basePath) === false ){
			mkdir($basePath);
		}

		$this->_basePath = realpath($basePath);
	}




	/**
	 * Get base path directory
	 *
	 * @return string
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}




	/**
	 * Zip file name
	 *
	 * @param $fileName string
	 */
	public function setZipFileName($fileName)
	{
		$this->_zipFileName = $fileName;
	}




	/**
	 * Return zip file name
	 *
	 * @return array
	 */
	public function getZipFileName()
	{
		return $this->_zipFileName;
	}




	/**
	 * Limit files to extracts
	 *
	 * @param $file string
	 */
	public function setFilesToExtract($file)
	{
		$this->_filesToExtract[] = $file;
	}




	/**
	 * Return files to extract
	 *
	 * @return array
	 */
	public function getFilesToExtract()
	{
		return $this->_filesToExtract;
	}




	/**
	 * Set destination directory
	 *
	 * @param $destinationDir string
	 */
	public function setDestinationDir($destinationDir)
	{
		if( file_exists($destinationDir) === false ){
			mkdir($destinationDir);
		}

		$this->_destinationDir = realpath($destinationDir);
	}




	/**
	 * Get destination directory
	 *
	 * @return string
	 */
	public function getDestinationDir()
	{
		return $this->_destinationDir;
	}




	/**
	 * Set temporary destination directory
	 *
	 * @param $tmpDestinationDir string
	 */
	public function setTmpDestinationDir($tmpDestinationDir)
	{
		if( file_exists($tmpDestinationDir) === false ){
			mkdir($tmpDestinationDir);
		}

		$this->_tmpDestinationDir = realpath($tmpDestinationDir);
	}




	/**
	 * Get temporary destination directory
	 *
	 * @return string
	 */
	public function getTmpDestinationDir()
	{
		return $this->_tmpDestinationDir;
	}




	/**
	 * @param $bool boolean
	 */
	public function setGreedy($bool)
	{
		$this->_greedy = $bool;
	}




	/**
	 * Get if search must check everything of specific
	 */
	public function getGreedy()
	{
		return $this->_greedy;
	}




	/**
	 * @param $bool boolean
	 */
	public function setSameStructure($bool)
	{
		$this->_sameStructure = $bool;
	}




	/**
	 * Get same structure or file only
	 */
	public function getSameStructure()
	{
		return $this->_sameStructure;
	}




	/**
	 * Set allowed mime
	 *
	 * @param $key string Extension value
	 *               Example: jpg
	 * @param $value string Mime value
	 *               Example: image/jpeg
	 */
	public function setMimeFiles($key, $value)
	{
		$this->_mimeFiles[$key] = $value;
	}




	/**
	 * Get all allowed mimes
	 *
	 * @return array
	 */
	public function getMimeFiles()
	{
		return $this->_mimeFiles;
	}




	/**
	 * Set path of magic.mime
	 *
	 * @param $path string|null Path to magic.mime file
	 */
	public function setMagicMime($path = null)
	{
		$path = realpath($path);

		if( empty($path) === true ){
			throw new InvalidArgumentException('Undifined value in $path');
		}

		$this->_magicMime = $path;
	}




	/**
	 * Get path of magic.mime file
	 *
	 * @return string
	 */
	public function getMagicMime()
	{
		return $this->_magicMime;
	}




	/**
	 * Unset specific value in $this->_mimeFiles
	 */
	public function unsetMime($key)
	{
		if( array_key_exists($key, $this->_mimeFiles) === true ){
			unset($this->_mimeFiles[$key]);
		}
	}




	/**
	 * Unset all values in $this->_mimeFiles
	 */
	public function unsetAllMime()
	{
		$this->_mimeFiles = array();
	}




	/**
	 * Shortest way to remove all directories structure
	 */
	public function rrmdir($path)
	{
		return ( is_file($path) === true )
		       ? @unlink($path)
			   : ( array_map(array($this, 'rrmdir'), glob($path . '/*')) == @rmdir($path) );
	}




	/**
	 * Zip errors explained
	 *
	 * @return string
	 */
	public function getZipError($error)
	{
		if( empty($error) === false && array_key_exists($error, $this->_zipError) === true ){
			return $this->_zipError[$error];
		}
		else{
			return 'Error do not exists in this class check manual for error: ' . $error;
		}
	}




	/**
	 * Remove zip file
	 *
	 * @param $bool boolean
	 */
	public function removeZipFile($bool)
	{
		$this->_removeZipFile = $bool;
	}




	/**
	 * Remove temporary directory
	 *
	 * @param $bool boolean
	 */
	public function removeTmpDir($bool)
	{
		$this->_removeTmpDir = $bool;
	}




	/**
	 * @param $flags integer
	 */
	public function open($flags = 0)
	{
		$zip = new ZipArchive();
		$open = $zip->open($this->_zipFileName, $flags);
		if( $open === true ){
			$this->_zip = $zip;
		}
		else{
			echo '<pre>' . $this->getZipError($open) . '<pre>';
			exit;
		}
		
	}




	/**
	 * Iterate directory
	 *
	 * @param $path string Path to iterate
	 */
	public function iterateDir($path)
	{
		if( empty($path) === true ){
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

		while( $it->valid() === true ){
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
		return array( 'isDir' => ( $e[0] == 'directory' ),
                      'isFile' => ( $e[0] != 'directory'),
					  'isValid' => ( empty($mimeFiles) === true || in_array( $e[0], $this->getMimeFiles() ) === true ), );
	}




	/**
	 * Move allowed file to new destination
	 *
	 * @return array
	 */
	public function moveValidMime(array $moveFiles = array())
	{
		$filesMoved        = array();
		$destinationDir    = $this->getDestinationDir();
		$tmpDestinationDir = $this->getTmpDestinationDir();

		if( empty($destinationDir) === true ){
			throw new InvalidArgumentException('Undefined variable $_destinationDir');
		}

		if( empty($tmpDestinationDir) === true ){
			throw new InvalidArgumentException('Undefined variable $_tmpDestinationDir');
		}

		if( empty($moveFiles) === false ){
			foreach($moveFiles as $file){
				$tmpFile = realpath($tmpDestinationDir . DIRECTORY_SEPARATOR . $file);

				if( empty($tmpFile) === false ){
					$mime = $this->isValidMime($tmpFile);

					if( $mime['isValid'] === true ){
						$pathInfo = pathinfo($file);
						$destination = $destinationDir . DIRECTORY_SEPARATOR
									 . $pathInfo['dirname'] . DIRECTORY_SEPARATOR;

						/**
						 * mkdir send an error if one of the directories exists
						 */
						@mkdir($destination, '0666', true);

						$destination .= $pathInfo['filename'] . '.' . $pathInfo['extension'];

						if( copy($tmpFile, $destination) === true ){
							$filesMoved[] = array( 'original' => $tmpFile, 
												   'destination' => $destination, );
						}
					}
				}
			}
		}
		else{
			$it = $this->iterateDir($tmpDestinationDir);

			if( $it !== false ){
				while( $it->valid() === true ){
					$mime = $this->isValidMime($it->key());

					if( $mime['isValid'] === true ){
						$pathInfo    = pathinfo($it->key());
						$destination = $destinationDir . DIRECTORY_SEPARATOR
									 . $pathInfo['filename'] . '_' . microtime(true)
									 . '.' . $pathInfo['extension'];
						if( copy($it->key(), $destination) === true ){
							$filesMoved[] = array( 'original' => $it->key(), 
												   'destination' => $destination, );
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
	 */
	public function extractByExtension()
	{
		$tmpDestinationDir = $this->getTmpDestinationDir();

		if( empty($tmpDestinationDir) === true ){
			throw new InvalidArgumentException('Undefined variable $_tmpDestinationDir');
		}

		for($i = 0; $i < $this->_zip->numFiles; $i++){
			$file     = $this->_zip->statIndex($i);
			$pathInfo = pathinfo($file['name']);

			if( array_key_exists('extension', $pathInfo) === true ){
				$mimeFiles = $this->getMimeFiles();

				/**
				 * Include by specific extension or all if empty $this->_mimeFiles
				 */
				if( ( array_key_exists($pathInfo['extension'], $mimeFiles) === true ) || ( empty($mimeFiles) === true ) ){
					$this->setFilesToExtract($file['name']);
				}
			}
		}

		$this->_zip->extractTo($tmpDestinationDir, $this->getFilesToExtract());

		return $this->moveValidMime();
	}




	public function extractSpecificsFiles()
	{
		$files             = array();
		$destinationDir = $this->getDestinationDir();

		if( empty($destinationDir) === true ){
			throw new InvalidArgumentException('Undefined variable $_destinationDir');
		}

		for($i = 0; $i < $this->_zip->numFiles; $i++){
			$file     = $this->_zip->statIndex($i);
			$pathInfo = pathinfo($file['name']);

			if( empty($pathInfo['extension']) === false ){
				$fileName = $pathInfo['filename'] . '.' . $pathInfo['extension'];

				/**
				 * Check first if user write full path or check if user only write the name of the file and want to check everything
				 */
				if( in_array($file, $this->getFilesToExtract()) === true
					|| ( in_array($fileName, $this->getFilesToExtract()) === true && $this->getGreedy() === true ) ){
					$files[] = $file['name'];
				}
			}
		}

		if( $this->getSameStructure() === true ){
			$tmpDestinationDir = $this->getTmpDestinationDir();

			if( empty($tmpDestinationDir) === true ){
				throw new InvalidArgumentException('Undefined variable $_tmpDestinationDir');
			}

			$this->_zip->extractTo($tmpDestinationDir, $files);

			return $this->moveValidMime($files);
		}
		else{
			$this->_zip->extractTo($destinationDir, $files);

			/**
			 * Verify if is a valid mime
			 */
			foreach($files as $file){
				$file = realpath( $destinationDir . DIRECTORY_SEPARATOR . $file );

				if( empty($file) === false && $this->isValidMime($file) === false ){
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
	 */
	public function extractAllFiles()
	{
		$destinationDir = $this->getDestinationDir();

		if( empty($destinationDir) === true ){
			throw new InvalidArgumentException('Undefined variable $_destinationDir');
		}

		$this->_zip->extractTo($destinationDir);

		return $this->listFiles($destinationDir);
	}




	/**
	 * Close object ZipArchive()
	 */
	public function __destruct(){
		if( ( $this->_zip instanceof ZipArchive ) === true ){
			$this->_zip->close();

			if( $this->_removeTmpDir === true ){
				$this->rrmdir($this->getTmpDestinationDir());
			}

			if( $this->_removeZipFile === true ){
				unlink($this->_zipFileName);
			}
		}
	}
}
