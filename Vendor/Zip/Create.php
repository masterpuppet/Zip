<?php

namespace Zip;

use ZipArchive;

class Create extends Zip
{
    /**
     * @param null|string $fileName
     * @return \Zip
     */
    public function create($fileName = null)
    {
        $fileName = (empty($fileName) === true)
                  ? $this->getZipFileName()
                  : $fileName;
        $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.zip';
        $open = $this->open($fileName, ZIPARCHIVE::CREATE);

        return $this;
    }

    /**
     * @param string $dir
     * @return \Zip
     */
    public function addFullDir($dir)
    {
        $dir = realpath($dir);
        if (empty($dir) === true) {
            throw new \RuntimeException('Directory do not exists');
        }
        $dirName = pathinfo($dir, PATHINFO_FILENAME);

        $files = $this->iterateDir($dir);
        foreach ($files as $k => $v) {
            $file = $dirName . str_replace($dir, '', $v->getRealPath());
            if ($v->isFile() === true) { 
                $this->zip->addFile($v->getRealPath(), $file);
            } elseif ($v->isDir() === true) {
                $this->zip->addEmptyDir($file);
            }
        }

        return $this;
    }
}