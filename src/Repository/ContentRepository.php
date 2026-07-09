<?php

namespace Phunk\Repository;

use DateTimeImmutable;
use Phunk\{Result, Model, Repository};
use Phunk\Model\Content as ContentModel;

class ContentRepository extends Repository
{
    public const TABLE = 'content';
    public const MODEL = ContentModel::class;

    public static function findOneByLocaleTypeAndSlug(string $locale, string $type, string $slug): Result
    {
        $params = [
            'locale' => $locale,
            'type'   => $type,
            'slug'   => $slug,
        ];

        return static::findOne($params);
    }

    protected static function toRow(array $row): array
    {
        $row['data'] = json_encode($row['data'] ?? []);
        return $row;
    }

    protected static function rowToModel(array $row): Model
    {
        $row['data'] = json_decode($row['data'] ?? '[]', true) ?? [];
        return parent::rowToModel($row);
    }
}