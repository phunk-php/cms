<?php

namespace Phunk\Cms\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Inline\Newline;
use League\CommonMark\Node\Node;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\StringContainerInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Phunk\Cms\MediaService;
use Twig\Environment;

final class MediaImageRenderer implements NodeRendererInterface
{
    public function __construct(
        private readonly MediaService $media,
        private readonly Environment $twig,
        private readonly string $locale,
    ) {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        Image::assertInstanceOf($node);

        $slug  = $node->getUrl();
        $alt   = $this->getAltText($node);
        $media = $this->media->findOneByLocaleAndSlug($this->locale, $slug)->unwrapOr(null);

        return $this->twig->render('element/inline-image.html.twig', [
            'slug'  => $slug,
            'alt'   => $alt !== '' ? $alt : ($media?->getTitle() ?? ''),
            'media' => $media,
        ]);
    }

    private function getAltText(Image $node): string
    {
        $text = '';
        foreach (new NodeIterator($node) as $n) {
            if ($n instanceof StringContainerInterface) {
                $text .= $n->getLiteral();
            } elseif ($n instanceof Newline) {
                $text .= "\n";
            }
        }

        return $text;
    }
}
