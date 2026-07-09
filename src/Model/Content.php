<?php

namespace Phunk\Model;

use DateTimeImmutable;
use Phunk\Model;

class Content extends Model
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $type,
        public readonly string $slug,
        public readonly string $title,
        public readonly string $locale,
        public readonly string $content,
        public readonly null|string|DateTimeImmutable $date,
        public readonly array $data,
        public readonly ?DateTimeImmutable $createdAt,
        public readonly ?DateTimeImmutable $updatedAt,
    ) {
    }

    public static function draft(
        string $type = '',
        string $slug = '',
        string $title = '',
        string $locale = '',
        string $content = '',
        null|string|DateTimeImmutable $date = null,
        array $data = [],
    ): self {
        return new self(
            id: null,
            type: $type,
            slug: $slug,
            title: $title,
            locale: $locale,
            content: $content,
            date: $date,
            data: $data,
            createdAt: null,
            updatedAt: null,
        );
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['date'] = $this->formatDate($this->date);
        $data['createdAt'] = $this->formatDate($this->createdAt);
        $data['updatedAt'] = $this->formatDate($this->updatedAt);

        return $data;
    }
}