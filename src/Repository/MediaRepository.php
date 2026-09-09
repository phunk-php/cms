<?php

namespace Phunk\Cms\Repository;

use Phunk\Cms\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Phunk\Cms\Repository\AbstractContentRepository;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends AbstractContentRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }
}
