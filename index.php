<?php
require_once 'Zip.php';

/**
 * Example 1
 *     Extract by extension
 *     Remove temporary directory
 */
$zip = new Zip('./', 'a.zip', 'allowed', 'zip');
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
$zip = new Zip('./', 'b.zip', 'allowed2', 'zip2');
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
$zip->setMagicMime( __DIR__ . DIRECTORY_SEPARATOR . 'magic.mime' );
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
$zip = new Zip('./', 'a.zip', 'zip', 'tmp');
$zip->open();
$zip->removeTmpDir(true);
$zip->setSameStructure(true);
$zip->setFilesToExtract('108888.txt');
$files = $zip->extractSpecificsFiles();
echo '<pre>';
var_dump($files);
echo '<pre>';
