<?php
require_once __DIR__ . '/Vendor/Loader/AutoLoading.php';

$loader = new \Loader\Autoloading(__DIR__ . DIRECTORY_SEPARATOR . 'Vendor');

echo '<pre>';

/**
 * Example 1
 *     Extract all files
 */
$zip = new \Zip\Create('./');
$zip->create('zipfile')
    ->addFullDir('zip')
    ->addFromString('test.txt', 'work')
    ->addEmptyDir('directory');
echo $zip->getZip()->numFiles;exit;

/**
 * Example 2
 *     Extract by extension
 *     Add suffix to filename
 */
$zip   = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$files = $zip->open()
             ->setSuffix(date('Y-m-d'))
             ->extractByExtension();
var_dump($files);

/**
 * Example 3
 *     Extract specifics files
 */

/**
 * If you want to add an extension and mime. This is optional
 */
\Zip\Mime::setMimeFiles('extension_value', 'mime_value');

/**
 * Another way is using: $zip->setFilesToExtract(array('file1.ext', 'file2.ext', '...'));
 */
$zip   = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$files = $zip->open()
             ->sameStructure(true)
             ->setFileToExtract('file.ext')
             ->extractSpecificsFiles();
var_dump($files);


/**
 * Example 4
 *     Extract all files
 *     Set up magic.mime
 */
\Zip\Mime::setMagicMime(__DIR__ . DIRECTORY_SEPARATOR . 'magic.mime');

$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$files = $zip->open()
             ->extractAllFiles();
var_dump($files);


/**
 * Example 5
 *     create zip file
 */
$zip = new \Zip\Create('./');
$zip->create('zipfile')
    ->addFullDir('zip')
    ->addFromString('test.txt', 'work')
    ->addEmptyDir('directory');
echo $zip->getZip()->numFiles;

echo '</pre>';