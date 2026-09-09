<?php

namespace Phunk\Cms\Entity;

use Phunk\Cms\Entity\BaseContent;
use Phunk\Cms\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'media_locale_slug_unique', columns: ['locale', 'slug'])]
class Media extends BaseContent
{
}
