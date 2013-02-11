<?php
require_once __DIR__ . '/Vendor/Loader/AutoLoading.php';

$loader = new \Loader\Autoloading(__DIR__ . DIRECTORY_SEPARATOR . 'Vendor');

$zip = new \Zip\Extract('./', 'test.zip', 'zip', 'tmp');
$zip->removeTmpDir(true);
$zip->open();
$files = $zip->extractByExtension();