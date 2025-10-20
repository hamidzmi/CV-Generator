<?php

declare(strict_types=1);

namespace App\Tests\Modules\CVGeneration\Presentation;

use App\Modules\CVGeneration\Domain\WorkLogEntry;
use App\Tests\Modules\CVGeneration\Double\FakeLanguageModel;
use App\Tests\Modules\CVGeneration\Double\InMemoryWorkLogEntryProvider;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GenerateWorkExperienceControllerTest extends WebTestCase
{
    public function testReturnsGeneratedSection(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var InMemoryWorkLogEntryProvider $provider */
        $provider = static::getContainer()->get(InMemoryWorkLogEntryProvider::class);
        /** @var FakeLanguageModel $languageModel */
        $languageModel = static::getContainer()->get(FakeLanguageModel::class);

        $languageModel->nextResponse = 'Generated section content.';

        $provider->seed([
            new WorkLogEntry(
                entryId: 'entry-1',
                content: 'Implemented document indexing workflow.',
                projectName: 'CV Generator',
                workType: 'feature',
                businessImpact: 'Improved resume relevance.',
                role: 'Backend Engineer',
                technologies: ['PHP'],
                collaborators: [],
                loggedAt: new DateTimeImmutable('2024-05-10T12:00:00Z'),
            ),
        ]);

        $client->request(
            'POST',
            '/cv/work-experience',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['limit' => 5], JSON_THROW_ON_ERROR),
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Generated section content.', $payload['workExperience']);
        self::assertStringContainsString('Implemented document indexing workflow.', $languageModel->lastPrompt);
    }

    public function testReturnsNotFoundWhenNoEntries(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var InMemoryWorkLogEntryProvider $provider */
        $provider = static::getContainer()->get(InMemoryWorkLogEntryProvider::class);
        $provider->seed([]);

        $client->request('POST', '/cv/work-experience');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}

