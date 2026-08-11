<?php

namespace Phunk\Cms;

use Psr\Log\LoggerInterface;
use Phunk\{Phunk, Base, Result};
use Phunk\Cms\Entity\BaseContent as Content;

class ContentParser extends Base
{
    public function __construct(
        protected LoggerInterface $logger,
        protected File $file,
        protected ContentService $contentService,
        protected string $path,
    ) {
    }

    public function processContent(): Result
    {
        return $this->file->getFilesFromPath($this->path)
        ->andThen(Phunk::map($this->parseFileToEntity(...)))
        ->map(fn (array $files) => array_filter($files, fn(Result $file) => $file->isOk()))
        ->andThen(Phunk::map(fn(Result $result) => $result->unwrap()))
        ->andThen(Phunk::map(fn(Content $content) => $this->contentService->updateOrInsert($content)));
        ;
    }

    private function parseFileToEntity(string $filename): Result
    {
        if (preg_match('#(?<type>[^/]+)/(?<slug>[^/]+)\.(?<locale>[a-z]{2}(?:_[A-Z]{2})?)\.md$#', $filename, $matches)) {
            $type   = $matches['type'];
            $slug   = $matches['slug'];
            $locale = $matches['locale'];

            $entityClass = $this->contentService->getEntityClass();
            $content = new $entityClass();
            $content
            ->setType($type)
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

        $dataJson = [];
        foreach ($data['yaml'] as $key => $value) {
            if (in_array($key, ['slug', 'title', 'date'])) {
                continue;
            }

            $dataJson[$key] = $value;
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