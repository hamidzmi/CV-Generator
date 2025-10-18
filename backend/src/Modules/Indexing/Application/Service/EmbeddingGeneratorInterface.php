<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Application\Service;

interface EmbeddingGeneratorInterface
{
    /**
     * @return list<float>
     */
    public function generate(string $text): array;
}
