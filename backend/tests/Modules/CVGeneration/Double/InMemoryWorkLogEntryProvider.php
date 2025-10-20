<?php

declare(strict_types=1);

namespace App\Tests\Modules\CVGeneration\Double;

use App\Modules\CVGeneration\Application\Contract\WorkLogEntryProviderInterface;
use App\Modules\CVGeneration\Domain\WorkLogEntry;

final class InMemoryWorkLogEntryProvider implements WorkLogEntryProviderInterface
{
    /** @var list<WorkLogEntry> */
    private array $entries = [];

    /**
     * @param list<WorkLogEntry> $entries
     */
    public function seed(array $entries): void
    {
        $this->entries = $entries;
    }

    public function fetchRecent(int $limit): array
    {
        return array_slice($this->entries, 0, $limit);
    }
}

