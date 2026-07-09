<?php

namespace Phunk;

use Phunk\{Base, Result};

class File extends Base
{
    public static function getFilesFromPath(string $path): Result
    {
        self::debug('getFilesFromPath', ['path' => $path]);

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
                $result = array_merge($result, static::getFilesFromPath($file)->unwrap());
            } else {
                $result[] = $file;
            }
        }

        return Result::ok($result);
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