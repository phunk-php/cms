<?php

namespace Phunk\Cms;

use Phunk\Cms\Entity\BaseContent;
use Phunk\Cms\MediaService;
use Soap\Url;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MediaExtension extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private MediaService $media,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('imageUrl', $this->imageUrl(...)),
            new TwigFunction('imagePath', $this->imagePath(...)),
            new TwigFunction('banner', $this->banner(...)),
        ];
    }

    public function banner(BaseContent $content): ?BaseContent
    {
        return $this->media->findBanner($content)->unwrapOr(null);
    }

    public function imagePath(string $slug, array $options = []): string
    {
        return $this->getImageUrl($slug, $options, UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    public function imageUrl(string $slug, array $options = []): string
    {
        return $this->getImageUrl($slug, $options, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function getImageUrl(string $slug, array $options, int $mode): string
    {
        $defaults = ['size' => 'default'];
        $options = array_merge($defaults, $options);

        return $this->urlGenerator->generate(
            'image',
            ['size' => $options['size'], 'slug' => $slug],
            $mode,
        );
    }
}
