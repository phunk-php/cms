<?php

namespace Phunk;

use Phunk\{Phunk, Base, Result};
use Phunk\Model\Content as ContentModel;
use Phunk\Repository\ContentRepository;

class Content extends Base
{
    private static string $path = '';

    public static function configure(string $path): void
    {
        static::$path = $path;
    }

    public static function processContent(): Result
    {
        return File::getFilesFromPath(static::$path)
        ->andThen(Phunk::map(static::parseFileToModel(...)))
        ->andThen(Phunk::map(fn(Result $result) => $result->unwrap()))
        ->andThen(Phunk::map(fn(ContentModel $content) =>
            ContentRepository::findOneByLocaleTypeAndSlug($content->locale, $content->type, $content->slug)
            ->inspect(self::debug(...), 'Tried to find existing content')
            ->andThen(fn(ContentModel $existing) => ContentRepository::update($content->copy(['id' => $existing->id])))
            ->orElse(fn() => ContentRepository::insert($content))
        ));
    }

    private static function parseFileToModel(string $filename): Result
    {
        if (preg_match('#(?<type>[^/]+)/(?<slug>[^/]+)\.(?<locale>[a-z]{2}(?:_[A-Z]{2})?)\.yaml$#', $filename, $matches)) {
            $type   = $matches['type'];
            $slug   = $matches['slug'];
            $locale = $matches['locale'];

            $content = ContentModel::draft(
                type: $type,
                title: $slug,
                slug: $slug,
                locale: $locale,
            );

            return File::getContents($filename)
            ->andThen(Yaml::parse(...))
            ->map(fn(array $document) => $content->copy(array_merge(
                static::parseContentProperties($document['yaml']),
                ['content' => $document['content']],
            )));
        }

        return Result::err('Filename ' . $filename . ' cannot be parsed.');
    }

    private static function parseContentProperties(array $data): array
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