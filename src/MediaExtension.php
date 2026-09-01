<?php

namespace Phunk\Cms;

use Phunk\Cms\Entity\BaseContent;
use Phunk\Cms\MediaService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MediaExtension extends AbstractExtension
{
    public function __construct(private MediaService $media)
    {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('imageUrl', $this->imageUrl(...)),
            new TwigFunction('banner', $this->banner(...)),
        ];
    }

    public function banner(BaseContent $content): ?BaseContent
    {
        return $this->media->findBanner($content)->unwrapOr(null);
    }

    public function imageUrl(string $slug, array $options = []): string
    {
        $defaults = ['size' => 'default'];
        $options = array_merge($defaults, $options);

        return '/image/' . $options['size'] . '/' . $slug;
    }
}
