<?php

namespace Phunk\Cms;

use League\Glide\{Server, ServerFactory};
use League\Glide\Responses\SymfonyResponseFactory;
use Phunk\{Base, Result};
use Psr\Log\LoggerInterface;

class ImageServer extends Base
{
    protected Server $server;

    public function __construct(
        protected LoggerInterface $logger,
        protected string $source,
        protected string $cache
    ) {
        $this->server = ServerFactory::create([
            'source'   => $this->source,
            'cache'    => $this->cache,
            'response' => new SymfonyResponseFactory(),
        ]);
    }

    public function server(): Server
    {
        return ServerFactory::create([
            'source'   => $this->source,
            'cache'    => $this->cache,
            'response' => new SymfonyResponseFactory(),
        ]);
    }

    public function image(string $path, array $params): Result
    {
        return Result::ok($this->server->getImageResponse($path, $params));
    }
}