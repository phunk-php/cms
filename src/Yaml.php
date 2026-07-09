<?php

namespace Phunk;

use Phunk\{Phunk, Base, Result};
use Spatie\YamlFrontMatter\YamlFrontMatter;

class Yaml extends Base
{
    public static function configure(string $path): void
    {
        static::$path = $path;
    }

    public static function parse(string $content): Result
    {
        $document = YamlFrontMatter::parse($content);

        return Result::ok([
            'yaml'    => $document->matter(),
            'content' => $document->body(),
        ]);
    }
}