<?php

namespace Phunk\Cms;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MarkdownExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('md2html', [MarkdownRuntime::class, 'md2html'], ['is_safe' => ['html']]),
            new TwigFunction('content2html', [MarkdownRuntime::class, 'content2html'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('md2html', [MarkdownRuntime::class, 'md2html'], ['is_safe' => ['html']]),
            new TwigFilter('content2html', [MarkdownRuntime::class, 'content2html'], ['is_safe' => ['html']]),
        ];
    }
}
