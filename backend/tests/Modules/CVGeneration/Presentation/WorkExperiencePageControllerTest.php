<?php

declare(strict_types=1);

namespace App\Tests\Modules\CVGeneration\Presentation;

use App\Modules\CVGeneration\Domain\WorkLogEntry;
use App\Tests\Modules\CVGeneration\Double\FakeLanguageModel;
use App\Tests\Modules\CVGeneration\Double\InMemoryWorkLogEntryProvider;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class WorkExperiencePageControllerTest extends WebTestCase
{
    public function testPageDisplaysGeneratedSection(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var InMemoryWorkLogEntryProvider $provider */
        $provider = static::getContainer()->get(InMemoryWorkLogEntryProvider::class);
        $provider->seed([
            new WorkLogEntry(
                entryId: 'entry-visual',
                content: 'Implemented CV generation request flow.',
                projectName: 'CV Generator',
                workType: 'feature',
                businessImpact: 'Enabled resume drafting from work logs.',
                role: 'Backend Engineer',
                technologies: ['PHP', 'Symfony'],
                collaborators: ['Alice'],
                loggedAt: new DateTimeImmutable('2024-06-01T09:00:00Z'),
            ),
        ]);

        /** @var FakeLanguageModel $languageModel */
        $languageModel = static::getContainer()->get(FakeLanguageModel::class);
        $languageModel->nextResponse = "Work Experience:\n- **Senior Engineer, CV Generator**: Delivered CV generation pipeline.";
        $languageModel->lastPrompt = '';
        $languageModel->lastSystemInstructions = null;

        $client->request('GET', '/cv/work-experience', ['limit' => 5]);

        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent();

        self::assertStringContainsString('value="5"', $html);
        self::assertStringContainsString('<div class="experience-role">CV Generator: Senior Engineer (2024)</div>', $html);
        self::assertStringContainsString('<li><strong>Senior Engineer</strong>: Delivered CV generation pipeline.</li>', $html);
        self::assertStringContainsString('Implemented CV generation request flow.', $languageModel->lastPrompt);
    }

    public function testPageShowsErrorWhenNoEntries(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var InMemoryWorkLogEntryProvider $provider */
        $provider = static::getContainer()->get(InMemoryWorkLogEntryProvider::class);
        $provider->seed([]);

        $client->request('GET', '/cv/work-experience');

        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent();
        self::assertStringContainsString('No work log entries available for CV generation.', $html);
    }

    public function testRejectsInvalidLimit(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var InMemoryWorkLogEntryProvider $provider */
        $provider = static::getContainer()->get(InMemoryWorkLogEntryProvider::class);
        $provider->seed([]);

        $client->request('GET', '/cv/work-experience', ['limit' => 0]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $html = $client->getResponse()->getContent();
        self::assertStringContainsString('Entries to include must be at least 1.', $html);
    }

    public function testInlineBoldIsRenderedAsStrong(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var InMemoryWorkLogEntryProvider $provider */
        $provider = static::getContainer()->get(InMemoryWorkLogEntryProvider::class);
        $provider->seed([
            new WorkLogEntry(
                entryId: 'entry-bold',
                content: 'Added UI polish to Resume builder.',
                projectName: 'CV Generator',
                workType: 'feature',
                businessImpact: 'Improved usability.',
                role: 'Frontend Engineer',
                technologies: [],
                collaborators: [],
                loggedAt: new DateTimeImmutable('2024-06-03T09:00:00Z'),
            ),
        ]);

        /** @var FakeLanguageModel $languageModel */
        $languageModel = static::getContainer()->get(FakeLanguageModel::class);
        $languageModel->nextResponse = "Work Experience\n- **Frontend Engineer, CV Generator**: Crafted the resume UI.";

        $client->request('GET', '/cv/work-experience');

        self::assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent();
        self::assertStringContainsString('<div class="experience-role">CV Generator: Frontend Engineer (2024)</div>', $html);
        self::assertStringContainsString('<strong>Frontend Engineer</strong>: Crafted the resume UI.', $html);
    }
}
