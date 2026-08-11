<?php

namespace Phunk\Cms;

use Phunk\{Result, Error};
use Phunk\Cms\Entity\BaseContent as Content;

class MediaService extends ContentService
{
    public function findBanner(Content $content): Result
    {
        if (! isset($content->getData()['image'])) {
            return Result::err(new Error('Content has no banner.'));
        }

        return $this->findOneByLocaleTypeAndSlug($content->getLocale(), 'media', $content->getData()['image']);
    }
}