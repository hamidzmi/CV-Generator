<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Domain\Entity;

use App\Modules\Indexing\Domain\ValueObject\DocumentId;
use App\Modules\Indexing\Domain\ValueObject\Embedding;
use InvalidArgumentException;

/**
 * Aggregate root representing an indexable document.
 */
final class Document
{
    /** @param array<string, scalar|list<string>|null> $attributes */
    private function __construct(
        private readonly DocumentId $id,
        private readonly string $content,
        private readonly Embedding $embedding,
        private readonly array $attributes,
    ) {
        if ($content === '') {
            throw new InvalidArgumentException('Document content cannot be empty.');
        }
    }

    /** @param array<string, scalar|list<string>|null> $attributes */
    public static function create(DocumentId $id, string $content, Embedding $embedding, array $attributes = []): self
    {
        return new self($id, $content, $embedding, $attributes);
    }

    public function id(): DocumentId
    {
        return $this->id;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function embedding(): Embedding
    {
        return $this->embedding;
    }

    /** @return array<string, scalar|list<string>|null> */
    public function attributes(): array
    {
        return $this->attributes;
    }
}
