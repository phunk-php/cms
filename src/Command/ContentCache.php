<?php

namespace Phunk\Command;

use Phunk\{Phunk, Command, Console, Result, Content};
use Phunk\Model\Content as ContentModel;

class ContentCache extends Command
{
    public static $name = 'content:cache';
    public static $description = 'Process all content and build the SQLite cache.';

    public static function execute(array $args): Result
    {
        static::info('execute');
        Console::out('Process all content into SQLite cache...');

        return Content::processContent()
        ->andThen(Phunk::map(function(Result $row): array {
            $content = $row->unwrap()->toArray();
            $content['data'] = json_encode($content['data']);
            return $content;
        }))
        ->andThen(Console::table(...))
        ;
    }
}