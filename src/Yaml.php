<?php

namespace Phunk\Cms;

use Phunk\{Base, Result};
use Spatie\YamlFrontMatter\YamlFrontMatter;

class Yaml extends Base
{
    public static function parse(string $content): Result
    {
        $document = YamlFrontMatter::parse($content);

        return Result::ok([
            'yaml'    => $document->matter(),
            'content' => $document->body(),
        ]);
    }
}