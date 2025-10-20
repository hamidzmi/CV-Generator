<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Application\Contract;

use App\Modules\CVGeneration\Domain\WorkLogEntry;

interface WorkLogEntryProviderInterface
{
    /**
     * @return list<WorkLogEntry>
     */
    public function fetchRecent(int $limit): array;
}

