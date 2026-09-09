<?php

namespace Phunk\Cms;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Phunk\{Result, Error};
use Phunk\Cms\Entity\BaseContent as Content;
use Phunk\Cms\Repository\MediaRepository;

class MediaService extends AbstractContentService
{
    public function __construct(
        LoggerInterface $logger,
        RequestStack $requestStack,
        MediaRepository $repository,
    ) {
        parent::__construct($logger, $requestStack, $repository);
    }

    public function findBanner(Content $content): Result
    {
        if (! isset($content->getData()['image'])) {
            return Result::err(new Error('Content has no banner.'));
        }

        return $this->findOneByLocaleAndSlug($content->getLocale(), $content->getData()['image']);
    }
}
