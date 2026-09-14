<?php

namespace Phunk\Cms;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Phunk\{Base, Result, NotFound};
use Phunk\Cms\Entity\BaseContent as Content;
use Phunk\Cms\Repository\AbstractContentRepository;
use Psr\Log\LoggerInterface;

/**
 * Magic Doctrine finders (findBy<Field>, findOneBy<Field>, countBy<Field>) are also
 * available and are forwarded to the repository, wrapped in a Result. 
 */
#[AutoconfigureTag('phunk.content_service')]
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

    public function getContentType(): string
    {
        return strtolower(substr(strrchr($this->getEntityClass(), '\\'), 1));
    }

    public function findOneBySlug(string $slug): Result
    {
        return $this->findOneByLocaleAndSlug($this->getCurrentLocale(), $slug);
    }

    public function findOneByLocaleAndSlug(string $locale, string $slug): Result
    {
        return $this->findOneBy([
            'locale' => $locale,
            'slug'   => $slug,
        ]);
    }

    public function findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null): Result
    {
        $result = $this->repository->findBy($criteria, $orderBy, $limit, $offset);

        if ($result) {
            return Result::ok($result);
        }

        return Result::err(new NotFound());
    }

    public function updateOrInsert(Content $entity, bool $flush = false): Result
    {
        return $this->assertNoLocaleSlugConflict($entity)
        ->map(fn(Content $content) =>
            $content
            ->setContent($entity->getContent())
            ->setTitle($entity->getTitle())
            ->setDate($entity->getDate())
            ->setData($entity->getData())
        )
        ->andThen($this->save(...), $flush)
        ;
    }

    /**
     * Guards against two different content files independently resolving to
     * the same locale+slug (a UNIQUE constraint in the DB) but different ids.
     * $entity is always the object mutated -- never whatever this lookup
     * finds -- so this never substitutes a second, independently-hydrated
     * instance for the one ContentParser already resolved by id.
     */
    private function assertNoLocaleSlugConflict(Content $entity): Result
    {
        return $this->findOneByLocaleAndSlug($entity->getLocale(), $entity->getSlug())
        ->inspect($this->debug(...), 'Tried to find existing content')
        ->orElse(fn() => Result::ok(null))
        ->andThen(function (?Content $existing) use ($entity) {
            if ($existing !== null && $existing->getId() !== $entity->getId()) {
                return Result::err(new \RuntimeException(sprintf(
                    'Locale/slug "%s/%s" already belongs to id "%s", cannot assign to id "%s".',
                    $entity->getLocale(),
                    $entity->getSlug(),
                    $existing->getId(),
                    $entity->getId()
                )));
            }

            return Result::ok($entity);
        })
        ;
    }

    public function save(Content $entity, bool $flush = false): Result
    {
        $this->repository->save($entity, $flush);
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
