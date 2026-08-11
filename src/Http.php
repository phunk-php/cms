<?php

namespace Phunk\Cms;

use Symfony\Component\HttpFoundation\{Response, JsonResponse, RedirectResponse};
use Psr\Log\LoggerInterface;
use Phunk\{Base, Result};

class Http extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected Html $html,
    ) {
    }

    public function htmlResponse(string $content): Result
    {
        $this->debug('htmlResponse', ['content' => $content]);

        $response = new Response();
        $response->setContent($content);
        
        return Result::ok($response);
    }

    public function render(string $template, array $context = []): Result
    {
        return $this->html->render($template, $context)
        ->andThen($this->htmlResponse(...));
    }

    public function jsonResponse(array $data): Result
    {
        $this->debug('jsonResponse', $data);
        return Result::ok(new JsonResponse($data));
    }

    public function eTag(Response $response, string $etag): Result
    {
        $response->headers->set('etag', $etag);
        return Result::ok($response);
    }

    public function eTagContent(Response $response): Result
    {
        $etag = md5($response->getContent());
        return $this->eTag($response, $etag);
    }

    public function redirect(string $url, int $statusCode = 302): Result
    {
        return Result::ok(new RedirectResponse($url, $statusCode));
    }

    public function status(Response $response, int $statusCode): Result
    {
        $response->setStatusCode($statusCode);
        return Result::ok($response);
    }
}
