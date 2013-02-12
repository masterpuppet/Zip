<?php
require_once __DIR__ . '/Vendor/Loader/AutoLoading.php';

$loader = new \Loader\Autoloading(__DIR__ . DIRECTORY_SEPARATOR . 'Vendor');

echo '<pre>';

/**
 * Example 1
 *     Extract all files
 */
$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$zip->open();
$files = $zip->extractAllFiles();
var_dump($files);


/**
 * Example 2
 *     Extract by extension
 *     Add suffix to filename
 */
$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$zip->open();
$zip->setSuffix(date('Y-m-d'));
$files = $zip->extractByExtension();
var_dump($files);


/**
 * Example 3
 *     Extract specifics files
 */

/**
 * If you want to add an extension and mime. This is optional
 */
\Zip\Mime::setMimeFiles('extension_value', 'mime_value');

$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$zip->open();
$zip->sameStructure(true);

/**
 * Another way is using: $zip->setFilesToExtract(array('file1.ext', 'file2.ext', '...'));
 */
$zip->setFileToExtract('file.ext');

$files = $zip->extractSpecificsFiles();
var_dump($files);


/**
 * Example 4
 *     Extract all files
 *     Set up magic.mime
 */
\Zip\Mime::setMagicMime(__DIR__ . DIRECTORY_SEPARATOR . 'magic.mime');

$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$zip->open();
$files = $zip->extractAllFiles();
var_dump($files);

echo '</pre>';