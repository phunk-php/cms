<?php

namespace Phunk;

use Phunk\{Phunk, Base, Result};

class NginxCache extends Base
{
    private static string $path = '';

    public static function configure(string $path): void
    {
        static::$path = $path;
    }

    public static function clear(): Result
    {
        return static::getCachePath()
        ->andThen(File::getFilesFromPath(...))
        ->andThen(Phunk::map(File::delete(...)))
        ;

    }

    private static function getCachePath(): Result
    {
        if (! static::$path) {
            return Result::err('Nginx cache path is not configured.');
        }

        return Result::ok(static::$path);
    }


}