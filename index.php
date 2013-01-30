<?php
require_once 'Zip.php';

/**
 * Example 1
 *     Extract specifics files
 *     Do not remove zip file
 */
$zip = new Zip('./', 'a.zip', 'allowed', 'zip');
$zip->removeZipFile(false);
$zip->open();
$files = $zip->extractSpecificsFiles();
echo '<pre>';
var_dump($files);
echo '<pre>';

/**
 * Example 2
 *     Extract all files
 *     Do not remove zip file and temporary file
 */
$zip = new Zip('./', 'b.zip', 'allowed2', 'zip2');
$zip->removeZipFile(false);
$zip->removeTmpDir(false);
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
$zip = new Zip('./', 'c.zip', 'allowed3', 'zip3');
$zip->removeZipFile(false);
$zip->removeTmpDir(false);
$zip->setMagicMime( __DIR__ . DIRECTORY_SEPARATOR . 'magic.mime' );
$zip->open();
$files = $zip->extractAllFiles();
echo '<pre>';
var_dump($files);
echo '<pre>';