<?php

namespace Phunk\Command;

use Phunk\{Phunk, Command, Console, Result};
use Phunk\NginxCache;

class NginxCacheClear extends Command
{
    public static $name = 'nginx:cache:clear';
    public static $description = 'Clear nginx HTTP cache.';

    public static function execute(array $args): Result
    {
        static::info('execute');
        Console::out('Clearing nginx cache...');

        return NginxCache::clear()
        ->andThen(fn() => Result::ok('Nginx cache cleared!'));
    }
}