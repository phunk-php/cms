<?php

namespace Phunk\Cms;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use Phunk\Cms\Entity\BaseContent as Content;
use Phunk\Cms\Markdown\MediaImageRenderer;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class MarkdownRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly MediaService $media,
        private readonly Environment $twig,
    ) {
    }

    public function content2html(Content $content): string
    {
        return $this->md2html($content->getContent() ?? '', $content->getLocale());
    }

    public function md2html(string $content, string $locale): string
    {
        $converter = new CommonMarkConverter();
        $converter->getEnvironment()->addRenderer(Image::class, new MediaImageRenderer($this->media, $this->twig, $locale), 1);

        return (string) $converter->convert($content);
    }
}
