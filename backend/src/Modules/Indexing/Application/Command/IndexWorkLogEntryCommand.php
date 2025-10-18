<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Application\Command;

use DateTimeImmutable;

final class IndexWorkLogEntryCommand
{
    /**
     * @param list<string> $technologies
     * @param list<string> $collaborators
     * @param list<float>  $embedding
     */
    public function __construct(
        public readonly string $entryId,
        public readonly string $text,
        public readonly array $technologies,
        public readonly ?DateTimeImmutable $loggedAt,
        public readonly ?string $projectName,
        public readonly ?string $workType,
        public readonly ?string $businessImpact,
        public readonly ?string $role,
        public readonly array $collaborators,
        public readonly array $embedding,
    ) {
    }
}
