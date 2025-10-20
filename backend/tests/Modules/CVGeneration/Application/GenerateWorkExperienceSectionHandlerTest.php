<?php

declare(strict_types=1);

namespace App\Tests\Modules\CVGeneration\Application;

use App\Modules\CVGeneration\Application\Exception\NoWorkLogEntriesException;
use App\Modules\CVGeneration\Application\Query\GenerateWorkExperienceSectionQuery;
use App\Modules\CVGeneration\Application\Service\GenerateWorkExperienceSectionHandler;
use App\Modules\CVGeneration\Domain\WorkLogEntry;
use App\Tests\Modules\CVGeneration\Double\FakeLanguageModel;
use App\Tests\Modules\CVGeneration\Double\InMemoryWorkLogEntryProvider;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GenerateWorkExperienceSectionHandlerTest extends TestCase
{
    public function testGeneratesSectionFromEntries(): void
    {
        $provider = new InMemoryWorkLogEntryProvider();
        $languageModel = new FakeLanguageModel();
        $languageModel->nextResponse = 'Work experience draft.';

        $provider->seed([
            new WorkLogEntry(
                entryId: 'entry-1',
                content: 'Implemented vector indexing pipeline.',
                projectName: 'CV Generator',
                workType: 'feature',
                businessImpact: 'Improved retrieval accuracy for work logs.',
                role: 'Backend Engineer',
                technologies: ['PHP', 'Symfony'],
                collaborators: ['Alice'],
                loggedAt: new DateTimeImmutable('2024-05-10T12:00:00Z'),
            ),
        ]);

        $handler = new GenerateWorkExperienceSectionHandler($provider, $languageModel);

        $result = $handler(new GenerateWorkExperienceSectionQuery(5));

        self::assertSame('Work experience draft.', $result);
        self::assertStringContainsString('Implemented vector indexing pipeline.', $languageModel->lastPrompt);
        self::assertStringContainsString('Work Experience', $languageModel->lastPrompt);
        self::assertStringContainsString('Avoid phrases like "Here is"', $languageModel->lastPrompt);
        self::assertNotEmpty($languageModel->lastSystemInstructions);
    }

    public function testSanitizesModelOutput(): void
    {
        $provider = new InMemoryWorkLogEntryProvider();
        $languageModel = new FakeLanguageModel();
        $languageModel->nextResponse = "Here is the crafted Work Experience section:\n\n**Work Experience**\n\n* **Senior Engineer – CV Generator**: Delivered results.";

        $provider->seed([
            new WorkLogEntry(
                entryId: 'entry-1',
                content: 'Implemented vector indexing pipeline.',
                projectName: 'CV Generator',
                workType: 'feature',
                businessImpact: 'Improved retrieval accuracy for work logs.',
                role: 'Backend Engineer',
                technologies: ['PHP', 'Symfony'],
                collaborators: ['Alice'],
                loggedAt: new DateTimeImmutable('2024-05-10T12:00:00Z'),
            ),
        ]);

        $handler = new GenerateWorkExperienceSectionHandler($provider, $languageModel);

        $result = $handler(new GenerateWorkExperienceSectionQuery(3));

        self::assertStringStartsWith('Work Experience:', $result);
        self::assertStringNotContainsString('Here is the crafted', $result);
        self::assertStringContainsString('- **Senior Engineer – CV Generator**: Delivered results.', $result);
    }

    public function testConvertsInlineBulletsToList(): void
    {
        $provider = new InMemoryWorkLogEntryProvider();
        $languageModel = new FakeLanguageModel();
        $languageModel->nextResponse = 'Work Experience • **Senior Backend Engineer, Europa Park**: Fixed a bug. · **Backend Engineer, CV Generation**: Completed indexing service.';

        $provider->seed([
            new WorkLogEntry(
                entryId: 'entry-1',
                content: 'Example entry.',
                projectName: 'CV Generator',
                workType: 'feature',
                businessImpact: null,
                role: 'Backend Engineer',
                technologies: [],
                collaborators: [],
                loggedAt: new DateTimeImmutable('2024-05-10T12:00:00Z'),
            ),
        ]);

        $handler = new GenerateWorkExperienceSectionHandler($provider, $languageModel);

        $result = $handler(new GenerateWorkExperienceSectionQuery(2));

        $lines = explode("\n", $result);

        self::assertSame('Work Experience:', $lines[0]);
        self::assertSame('- **Senior Backend Engineer, Europa Park**: Fixed a bug.', $lines[1]);
        self::assertSame('- **Backend Engineer, CV Generation**: Completed indexing service.', $lines[2]);
    }

    public function testThrowsWhenNoEntriesAvailable(): void
    {
        $handler = new GenerateWorkExperienceSectionHandler(
            new InMemoryWorkLogEntryProvider(),
            new FakeLanguageModel(),
        );

        $this->expectException(NoWorkLogEntriesException::class);

        $handler(new GenerateWorkExperienceSectionQuery(3));
    }
}
