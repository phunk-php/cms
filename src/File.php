<?php

namespace Phunk\Cms;

use Phunk\{Base, Phunk, Result};

class File extends Base
{
    public function clearPath(string $path): Result
    {
        return $this->getFilesFromPath($path)
        ->andThen(Phunk::map($this->delete(...)))
        ->map(fn (array $results) => [
            'deleted' => count(array_filter($results, fn (Result $result) => $result->isOk())),
            'failed'  => count(array_filter($results, fn (Result $result) => $result->isErr())),
        ]);
    }

    public function getFilesFromPath(string $path): Result
    {
        $this->debug('getFilesFromPath', ['path' => $path]);

        try {
            $files = scandir($path);
        } catch (\Exception $e) {
            print_r($e);
            return Result::err('No files found from ' . $path);
        }

        if (! is_array($files)) {
            return Result::err('No files found from ' . $path);
        }
    
        // Remove '.' and '..'
        $files = array_diff($files, ['.', '..']);
    
        // Make paths absolute
        $files = array_map(fn($filename): string => $path . '/' . $filename, $files);

        $result = [];
        foreach ($files as $file) {
            if (is_dir($file)) {
                $result = array_merge($result, $this->getFilesFromPath($file)->unwrap());
            } else {
                $result[] = $file;
            }
        }

        return Result::ok($result);
    }

    public function delete(string $path): Result
    {
        $this->debug('delete', ['filename' => $path]);

        if (is_dir($path)) {
            return Result::err('Error deleting file, ' . $path . ' is a directory.');
        }

        if (unlink($path)) {
            return Result::ok($path);
        }

        return Result::err('Error deleting file ' . $path);
    }

    public static function getContents(string $filename): Result
    {
        $content = file_get_contents($filename);

        if ($content === false) {
            return Result::err('Error getting content from file ' . $filename);
        }

        return Result::ok($content);
    }
}