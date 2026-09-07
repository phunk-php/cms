<?php

namespace Phunk\Cms;

use Symfony\Component\HttpFoundation\RequestStack;
use Phunk\{Base, Result, NotFound};
use Phunk\Cms\Entity\BaseContent as Content;
use Phunk\Cms\Repository\AbstractContentRepository;
use Psr\Log\LoggerInterface;

/**
 * Magic Doctrine finders (findBy<Field>, findOneBy<Field>, countBy<Field>) are also
 * available and are forwarded to the repository, wrapped in a Result. 
 */
abstract class AbstractContentService extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected RequestStack $requestStack,
        protected AbstractContentRepository $repository,
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->repository->getClassName();
    }

    public function findOneBySlug(string $slug): Result
    {
        $result = $this->repository->findOneBy([
            'locale' => $this->getCurrentLocale(),
            'slug'   => $slug,
        ]);

        if ($result) {
            return Result::ok($result);
        }

        return Result::err(new NotFound());
    }

    public function findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null): Result
    {
        $result = $this->repository->findBy($criteria, $orderBy, $limit, $offset);

        if ($result) {
            return Result::ok($result);
        }

        return Result::err(new NotFound());
    }

    public function findOneByLocaleTypeAndSlug(string $locale, string $type, string $slug): Result
    {
        $entity = $this->repository->findOneBy([
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
        $this->repository->save($entity, true);
        return Result::ok($entity);
    }

    private function getCurrentLocale(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en';
    }

    public function __call(string $method, array $arguments): Result
    {
        if (! str_starts_with($method, 'find') && ! str_starts_with($method, 'countBy')) {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
        }

        // Re-enters Doctrine's own EntityRepository::__call(), which parses the field
        // name and validates it against the entity's metadata. Let it throw uncaught
        // for a bogus method/field name -- that's a programmer error, not a "not found"
        // business outcome, and shouldn't be silently swallowed into a Result.
        $result = $this->repository->$method(...$arguments);

        if (str_starts_with($method, 'countBy')) {
            // A count is always a valid answer, including 0 -- never "not found".
            return Result::ok($result);
        }

        if (str_starts_with($method, 'findOneBy') || $method === 'findOne') {
            return $result !== null ? Result::ok($result) : Result::err(new NotFound());
        }

        // findBy*/find* -- preserved for consistency with findBy() above: an empty
        // array is also treated as Err(NotFound) rather than Ok([]).
        return $result ? Result::ok($result) : Result::err(new NotFound());
    }
}
