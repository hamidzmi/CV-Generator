<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Modules\Indexing\Domain\Entity\Document;
use App\Modules\Indexing\Domain\Repository\VectorStore;

final class InMemoryVectorStore implements VectorStore
{
    /** @var list<Document> */
    private array $documents = [];

    public function upsert(Document $document): void
    {
        $this->documents[] = $document;
    }

    /**
     * @return list<Document>
     */
    public function all(): array
    {
        return $this->documents;
    }

    public function reset(): void
    {
        $this->documents = [];
    }
}
