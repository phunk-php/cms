<?php

namespace Phunk\Cms\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Phunk\Cms\{Html, ContentService};

#[AsEventListener(event: KernelEvents::CONTROLLER)]
final class TemplateGlobalsListener
{
    public function __construct(
        private readonly ContentService $content,
        private readonly Html $html,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $this->content
            ->findOneByLocaleTypeAndSlug('fi', 'site', 'innota')
            ->andThen(fn($site) => $this->html->addGlobalData(['site' => $site]))
        ;
    }
}
