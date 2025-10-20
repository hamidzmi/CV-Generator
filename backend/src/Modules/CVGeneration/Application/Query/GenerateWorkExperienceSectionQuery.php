<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Application\Query;

final class GenerateWorkExperienceSectionQuery
{
    public function __construct(public readonly int $maxEntries = 10)
    {
        if ($this->maxEntries < 1) {
            throw new \InvalidArgumentException('maxEntries must be at least 1.');
        }
    }
}

