<?php

namespace Phunk\Cms\EventListener;

use Phunk\{Result, NotFound};
use Phunk\Cms\Http;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Error\LoaderError;

#[AsEventListener(event: KernelEvents::VIEW)]
final class ResultResponseListener
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Http $http,
    ) {
    }

    public function __invoke(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if (! $result instanceof Result) {
            return; // let Symfony's normal view-handling deal with it
        }

        $event->setResponse($result->match(
            onOk:  fn (mixed $value) => $value instanceof Response ? $value : new Response((string) $value),
            onErr: fn (mixed $error) => $this->errorToResponse($error),
        ));
    }

    private function errorToResponse(mixed $error): Response
    {
        $status = match (true) {
            $error instanceof NotFound => 404,
            default => 500,
        };

        return $this->http->htmlResponse($this->renderErrorTemplate($status))
        ->andThen($this->http->status(...), $status)
        ->unwrap();
    }

    private function renderErrorTemplate(int $status): string
    {
        $template = "errors/{$status}.html.twig";

        try {
            return $this->twig->render($template);
        } catch (LoaderError) {
            return $this->twig->render('errors/500.html.twig');
        }
    }
}
