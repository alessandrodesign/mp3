<?php

namespace Core\Utils;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RuntimeException;

class Directories
{
    public static function validAndCreate(?string &$dir, int $mode = 0775): void
    {
        if (is_null($dir)) {
            throw new RuntimeException("Directory can't be empty");
        }

        if (!is_dir($dir)) {
            mkdir($dir, $mode, true);
            chmod($dir, $mode);
        }

        if (!is_writable($dir)) {
            throw new RuntimeException(sprintf("Directory %s is not writable", $dir));
        }
    }

    public static function listClasses(string $dir, string $namespace): array
    {
        Directories::validAndCreate($dir);
        return ClassFinder::findConcreteClasses($dir, $namespace);
    }

    public static function findFiles(string $dir): array
    {
        $files = [];

        $dirIterator = new RecursiveDirectoryIterator($dir);
        $iterator = new RecursiveIteratorIterator($dirIterator);
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH);

        foreach ($regex as $list) {
            $files = array_merge($files, $list);
        }

        return $files;
    }
}