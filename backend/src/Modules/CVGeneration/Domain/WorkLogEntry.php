<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Domain;

use DateTimeImmutable;

/**
 * Immutable representation of a work log entry suitable for CV generation.
 */
final class WorkLogEntry
{
    /**
     * @param list<string> $technologies
     * @param list<string> $collaborators
     */
    public function __construct(
        public readonly string $entryId,
        public readonly string $content,
        public readonly ?string $projectName,
        public readonly ?string $workType,
        public readonly ?string $businessImpact,
        public readonly ?string $role,
        public readonly array $technologies,
        public readonly array $collaborators,
        public readonly ?DateTimeImmutable $loggedAt,
    ) {
    }
}

