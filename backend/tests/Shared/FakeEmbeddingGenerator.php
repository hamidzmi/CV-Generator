<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Modules\Indexing\Application\Service\EmbeddingGeneratorInterface;

final class FakeEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    /** @var list<float> */
    private array $vector;

    /**
     * @param list<float> $vector
     */
    public function __construct(array $vector = [0.1, 0.2, 0.3])
    {
        $this->vector = $vector;
    }

    /**
     * @return list<float>
     */
    public function generate(string $text): array
    {
        return $this->vector;
    }
}
