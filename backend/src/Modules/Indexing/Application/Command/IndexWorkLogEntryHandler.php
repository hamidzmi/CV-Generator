<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Application\Command;

use App\Modules\Indexing\Domain\Entity\Document;
use App\Modules\Indexing\Domain\Repository\VectorStore;
use App\Modules\Indexing\Domain\ValueObject\DocumentId;
use App\Modules\Indexing\Domain\ValueObject\Embedding;

final class IndexWorkLogEntryHandler
{
    public function __construct(private readonly VectorStore $vectorStore)
    {
    }

    public function __invoke(IndexWorkLogEntryCommand $command): void
    {
        $contentSegments = [$command->text];

        if ($command->technologies !== []) {
            $contentSegments[] = 'Technologies: ' . implode(', ', $command->technologies);
        }

        if ($command->loggedAt !== null) {
            $contentSegments[] = sprintf('Logged at: %s', $command->loggedAt->format('c'));
        }

        if ($command->projectName !== null) {
            $contentSegments[] = sprintf('Project: %s', $command->projectName);
        }

        if ($command->workType !== null) {
            $contentSegments[] = sprintf('Work type: %s', $command->workType);
        }

        if ($command->businessImpact !== null) {
            $contentSegments[] = sprintf('Impact: %s', $command->businessImpact);
        }

        if ($command->role !== null) {
            $contentSegments[] = sprintf('Role: %s', $command->role);
        }

        if ($command->collaborators !== []) {
            $contentSegments[] = 'Collaborators: ' . implode(', ', $command->collaborators);
        }

        $document = Document::create(
            DocumentId::fromString($command->entryId),
            implode("\n\n", $contentSegments),
            Embedding::fromArray($command->embedding),
            array_filter(
                [
                    'sourceEntryId' => $command->entryId,
                    'technologies' => $command->technologies,
                    'loggedAt' => $command->loggedAt?->format('c'),
                    'projectName' => $command->projectName,
                    'workType' => $command->workType,
                    'businessImpact' => $command->businessImpact,
                    'role' => $command->role,
                    'collaborators' => $command->collaborators,
                ],
                static fn (mixed $value): bool => $value !== null,
            ),
        );

        $this->vectorStore->upsert($document);
    }
}
