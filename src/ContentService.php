<?php

namespace Phunk\Cms;

use Phunk\{Base, Result, NotFound};
use Phunk\Cms\Entity\BaseContent as Content;
use Phunk\Cms\Repository\AbstractContentRepository;
use Psr\Log\LoggerInterface;

class ContentService extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected AbstractContentRepository $contentRepository,
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->contentRepository->getClassName();
    }

    public function findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null): Result
    {
        $result = $this->contentRepository->findBy($criteria, $orderBy, $limit, $offset);

        if ($result) {
            return Result::ok($result);
        }

        return Result::err(new NotFound());
    }

    public function findOneByLocaleTypeAndSlug(string $locale, string $type, string $slug): Result
    {
        $entity = $this->contentRepository->findOneBy([
            'locale' => $locale,
            'type'   => $type,
            'slug'   => $slug,
        ]);

        if (! $entity) {
            return Result::err(new NotFound('The Content was not found.'));
        }

        return Result::ok($entity);
    }

    public function updateOrInsert(Content $entity): Result
    {
        return $this->findOneByLocaleTypeAndSlug($entity->getLocale(), $entity->getType(), $entity->getSlug())
        ->inspect($this->debug(...), 'Tried to find existing content')
        ->orElse(fn() => Result::ok($entity))
        ->map(fn(Content $content) => 
            $content
            ->setContent($entity->getContent())
            ->setTitle($entity->getTitle())
            ->setDate($entity->getDate())
            ->setData($entity->getData())
        )
        ->andThen($this->save(...))
        ;
    }

    public function save(Content $entity): Result
    {
        $this->contentRepository->save($entity, true);
        return Result::ok($entity);
    }
}