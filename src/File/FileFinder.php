<?php

declare(strict_types=1);

namespace Churn\File;

use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FileFinder
{

    /**
     * List of file extensions to look for.
     *
     * @var array<string>
     */
    private $fileExtensions;

    /**
     * List of regular expressions used to filter files to ignore.
     *
     * @var array<string>
     */
    private $filters;

    /**
     * Class constructor.
     *
     * @param array<string> $fileExtensions List of file extensions to look for.
     * @param array<string> $filesToIgnore List of files to ignore.
     */
    public function __construct(array $fileExtensions, array $filesToIgnore)
    {
        $this->fileExtensions = $fileExtensions;
        $this->filters = \array_map(function (string $fileToIgnore): string {
            return $this->patternToRegex($fileToIgnore);
        }, $filesToIgnore);
    }

    /**
     * Recursively finds all files with the .php extension in the provided
     * $paths and returns list as array.
     *
     * @param array<string> $paths Paths in which to look for .php files.
     * @return Generator<int, File>
     */
    public function getPhpFiles(array $paths): Generator
    {
        foreach ($paths as $path) {
            yield from $this->getPhpFilesFromPath($path);
        }
    }

    /**
     * Recursively finds all files with the .php extension in the provided
     * $path adds them to $this->files.
     *
     * @param string $path Path in which to look for .php files.
     * @return Generator<int, File>
     */
    private function getPhpFilesFromPath(string $path): Generator
    {
        if (\is_file($path)) {
            $file = new SplFileInfo($path);

            yield new File($file->getRealPath(), $file->getPathName());

            return;
        }

        if (!\is_dir($path)) {
            // invalid path
            return;
        }

        foreach ($this->findPhpFiles($path) as $file) {
            yield new File($file->getRealPath(), $file->getPathName());
        }
    }

    /**
     * Recursively finds all PHP files in a given directory.
     *
     * @param string $path Path in which to look for .php files.
     * @return Generator<int, SplFileInfo>
     */
    private function findPhpFiles(string $path): Generator
    {
        foreach ($this->findFiles($path) as $file) {
            if (!\in_array($file->getExtension(), $this->fileExtensions, true) || $this->fileShouldBeIgnored($file)) {
                continue;
            }

            yield $file;
        }
    }

    /**
     * Recursively finds all files in a given directory.
     *
     * @param string $path Path in which to look for .php files.
     * @return Generator<int, SplFileInfo>
     */
    private function findFiles(string $path): Generator
    {
        $directoryIterator = new RecursiveDirectoryIterator($path);

        foreach (new RecursiveIteratorIterator($directoryIterator) as $item) {
            if ($item->isDir()) {
                continue;
            }

            yield $item;
        }
    }

    /**
     * Determines if a file should be ignored.
     *
     * @param SplFileInfo $file File.
     */
    private function fileShouldBeIgnored(SplFileInfo $file): bool
    {
        foreach ($this->filters as $regex) {
            if (\preg_match("#{$regex}#", $file->getRealPath())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Translate file path pattern to regex string.
     *
     * @param string $filePattern File pattern to be ignored.
     */
    private function patternToRegex(string $filePattern): string
    {
        $regex = \preg_replace("#(.*)\*([\w.]*)$#", "$1.+$2$", $filePattern);

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $regex = \str_replace('/', '\\\\', $regex);
        }

        return $regex;
    }
}
