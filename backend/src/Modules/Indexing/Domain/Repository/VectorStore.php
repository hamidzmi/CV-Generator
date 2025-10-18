<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Domain\Repository;

use App\Modules\Indexing\Domain\Entity\Document;

interface VectorStore
{
    public function upsert(Document $document): void;
}
