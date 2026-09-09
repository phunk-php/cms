<?php

namespace Phunk\Cms;

use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Phunk\{Phunk, Base, Result};
use Phunk\Cms\Entity\BaseContent as Content;

class ContentParser extends Base
{
    protected array $servicesByType = [];

    /**
     * @param AbstractContentService[] $contentServices One per content type, wired
     *                                                  explicitly in the app's
     *                                                  services.yaml. Dispatch is
     *                                                  keyed by AbstractContentService::getContentType().
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected File $file,
        #[AutowireIterator('phunk.content_service')]
        protected iterable $contentServices,
        protected string $path,
    ) {
        foreach ($this->contentServices as $service) {
            $this->servicesByType[$service->getContentType()] = $service;
        }
    }

    public function processContent(): Result
    {
        return $this->file->getFilesFromPath($this->path)
        ->andThen(Phunk::map($this->parseFileToEntity(...)))
        ->map(fn (array $files) => array_filter($files, fn(Result $file) => $file->isOk()))
        ->andThen(Phunk::map(fn(Result $result) => $result->unwrap()))
        ->andThen(Phunk::map($this->updateOrInsertEntity(...)))
        ;
    }

    private function updateOrInsertEntity(Content $content): Result
    {
        $services = iterator_to_array($this->contentServices->getIterator());
        $services = array_filter($services, fn(AbstractContentService $service) => 
            $service->getEntityClass() === get_class($content)
        );
        $service = array_first($services);

        if (! $service) {
            $this->debug('updateOrInsertEntity: no content service found for entity', [
                'class' => get_class($content),
            ]);
            return Result::err('No content service found for entity "' . get_class($content) . '".');
        }

        return $service->updateOrInsert($content);
    }

    private function parseFileToEntity(string $filename): Result
    {
        if (preg_match('#(?<type>[^/]+)/(?<slug>[^/]+)\.(?<locale>[a-z]{2}(?:_[A-Z]{2})?)\.md$#', $filename, $matches)) {
            $type   = $matches['type'];
            $slug   = $matches['slug'];
            $locale = $matches['locale'];

            $contentService = $this->servicesByType[$type] ?? null;

            if (! isset($this->servicesByType[$type])) {
                $this->debug('parseFileToEntity: no content service registered for type', ['type' => $type, 'filename' => $filename]);
                return Result::err('No content service registered for type "' . $type . '".');
            }

            $entityClass = $contentService->getEntityClass();
            $content = new $entityClass();
            $content
            ->setSlug($slug)
            ->setLocale($locale)
            ->setTitle($slug);

            return $this->file->getContents($filename)
            ->andThen(Yaml::parse(...))
            ->map(fn(array $document) => $this->applyDocumentToEntity($document, $content));
        }

        $this->debug('parseFileToModel: fail', ['filename' => $filename]);
        return Result::err('Filename ' . $filename . ' cannot be parsed.');
    }

    private function applyDocumentToEntity(array $data, Content $entity): Content
    {
        // Check if we have a title or slug override
        $entity->setSlug($data['yaml']['slug'] ?? $entity->getSlug());
        $entity->setTitle($data['yaml']['title'] ?? $entity->getTitle());

        if (isset($data['yaml']['date']) && strtotime($data['yaml']['date'])) {
            $ts = strtotime($data['yaml']['date']);
            $entity->setDate(new DateTime('@' . $ts));
        }

        $dataJson = [];
        foreach ($data['yaml'] as $key => $value) {
            if (in_array($key, ['slug', 'title', 'date'])) {
                continue;
            }

            // Check if a setter exists
            $method = 'set' . ucfirst($key);
            if (is_callable([$entity, $method])) {
                $entity->$method($value);
            } else {
                $dataJson[$key] = $value;
            }
        }

        $entity
        ->setContent($data['content'])
        ->setData($dataJson)
        ;

        return $entity;
    }

    private function parseContentProperties(array $data): array
    {
        $keys = ['slug', 'title', 'date'];

        $properties = ['data' => []];
        foreach ($data as $key => $value) {
            if (in_array($key, $keys)) {
                if ($key === 'date') {
                    $value = new \DateTimeImmutable($value);
                }
                
                $properties[$key] = $value;
            } else {
                $properties['data'][$key] = $value;
            }
        }

        return $properties;
    }
}