<?php

namespace Phunk\Cms;

use Phunk\{Base, Result};
use Phunk\Cms\{Html, Http};
use Psr\Log\LoggerInterface;

abstract class Controller extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected Html $html,
        protected Http $http,
    ) {
    }

    public function json(array $data = []): Result
    {
        $this->debug('json', $data);
        return $this->http->jsonResponse($data);
    }

    public function redirect(string $url, int $status = 302): Result
    {
        $this->debug('redirect', ['url' => $url, 'status' => $status]);
        return $this->http->redirect($url, $status);
    }

    public function render(string $template, array $context = []): Result
    {
        $this->debug('render', ['template' => $template]);
        return $this->http->render($template, $context);
    }
}
