<?php
require_once __DIR__ . '/Vendor/Loader/AutoLoading.php';

$loader = new \Loader\Autoloading(__DIR__ . DIRECTORY_SEPARATOR . 'Vendor');


/**
 * Example 1
 *     Extract by extension
 *     Remove temporary directory
 */
$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$zip->removeTmpDir(true);
$zip->open();
$files = $zip->extractByExtension();
echo '<pre>';
var_dump($files);
echo '<pre>';





/**
 * Example 2
 *     Extract all files
 *     Do not remove zip file and temporary file
 */
$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$zip->open();
$files = $zip->extractAllFiles();
echo '<pre>';
var_dump($files);
echo '<pre>';




/**
 * Example 3
 *     Extract all files
 *     Do not remove zip file and temporary file
 *     Set up magic.mime
 */
$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
\Zip\Mime::setMagicMime(__DIR__ . DIRECTORY_SEPARATOR . 'magic.mime');
$zip->open();
$files = $zip->extractAllFiles();
echo '<pre>';
var_dump($files);
echo '<pre>';




/**
 * Example 4
 *     Extract specifics files
 *     Remove temporary directory
 *     Set same structure
 */
$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$zip->open();
$zip->removeTmpDir(true);
$zip->setSameStructure(true);
/**
 * Another way is using
 * $zip->setFilesToExtract(array('file1.ext', 'file2.ext', '...'));
 */
$zip->setFileToExtract('file.ext');
$files = $zip->extractSpecificsFiles();
echo '<pre>';
var_dump($files);
echo '<pre>';