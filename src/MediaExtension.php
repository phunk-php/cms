<?php

namespace Phunk\Cms;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MediaExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('imageUrl', $this->imageUrl(...)),
        ];
    }

    public function imageUrl(string $slug, array $options = []): string
    {
        $defaults = ['size' => 'default'];
        $options = array_merge($defaults, $options);

        return '/image/' . $options['size'] . '/' . $slug;
    }
}
