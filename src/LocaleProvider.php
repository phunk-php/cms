<?php

namespace Phunk\Cms;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the current locale both inside and outside a request. Order:
 * an explicit override (e.g. from a CLI --locale option), the current
 * request's locale, then framework.default_locale.
 */
final class LocaleProvider
{
    private ?string $override = null;

    public function __construct(
        private RequestStack $requestStack,
        #[Autowire('%kernel.default_locale%')]
        private string $defaultLocale,
    ) {
    }

    public function getLocale(): string
    {
        return $this->override
            ?? $this->requestStack->getCurrentRequest()?->getLocale()
            ?? $this->defaultLocale;
    }

    /**
     * Pass null to clear the override.
     */
    public function setLocale(?string $locale): void
    {
        $this->override = $locale;
    }
}
