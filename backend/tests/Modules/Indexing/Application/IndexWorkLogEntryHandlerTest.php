<?php

declare(strict_types=1);

namespace App\Tests\Modules\Indexing\Application;

use App\Modules\Indexing\Application\Command\IndexWorkLogEntryCommand;
use App\Modules\Indexing\Application\Command\IndexWorkLogEntryHandler;
use App\Modules\Indexing\Domain\Entity\Document;
use App\Modules\Indexing\Domain\Repository\VectorStore;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class IndexWorkLogEntryHandlerTest extends TestCase
{
    public function testHandlerBuildsDocumentWithMetadata(): void
    {
        $capturedDocument = null;

        $vectorStore = new class($capturedDocument) implements VectorStore {
            public function __construct(private ?Document &$document)
            {
            }

            public function upsert(Document $document): void
            {
                $this->document = $document;
            }
        };

        $handler = new IndexWorkLogEntryHandler($vectorStore);

        $command = new IndexWorkLogEntryCommand(
            entryId: 'demo-identifier',
            text: 'Integrated Weaviate with Symfony indexing module.',
            technologies: ['PHP', 'Symfony'],
            loggedAt: new DateTimeImmutable('2025-10-14T09:30:00Z'),
            projectName: 'CV Generator',
            workType: 'feature',
            businessImpact: 'Unlocks richer CV narratives.',
            role: 'Backend Engineer',
            collaborators: ['Alice', 'Bob'],
            embedding: [0.12, 0.34, 0.56],
        );

        $handler($command);

        self::assertInstanceOf(Document::class, $capturedDocument);

        $properties = $capturedDocument->attributes();

        self::assertSame('demo-identifier', $properties['sourceEntryId']);
        self::assertSame(['PHP', 'Symfony'], $properties['technologies']);
        self::assertSame('CV Generator', $properties['projectName']);
        self::assertSame('feature', $properties['workType']);
        self::assertSame('Unlocks richer CV narratives.', $properties['businessImpact']);
        self::assertSame('Backend Engineer', $properties['role']);
        self::assertSame(['Alice', 'Bob'], $properties['collaborators']);
        self::assertArrayHasKey('loggedAt', $properties);

        self::assertStringContainsString('Technologies: PHP, Symfony', $capturedDocument->content());
        self::assertStringContainsString('Project: CV Generator', $capturedDocument->content());
    }
}
