<?php

declare(strict_types=1);

namespace App\Tests\Modules\Indexing\Presentation;

use App\Tests\Shared\InMemoryVectorStore;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class IndexWorkLogEntryControllerTest extends WebTestCase
{
    public function testSuccessfulIndexingRequest(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $store = static::getContainer()->get(InMemoryVectorStore::class);
        $store->reset();

        $payload = [
            'entryId' => 'functional-001',
            'text' => 'Implemented functional test for IndexWorkLogEntryController.',
            'technologies' => ['PHP', 'Symfony'],
            'projectName' => 'CV Generator',
            'workType' => 'feature',
            'businessImpact' => 'Ensures reliable indexing contract.',
            'role' => 'Backend Engineer',
            'collaborators' => ['QA Team'],
            'loggedAt' => '2025-10-14T12:00:00Z',
            'embedding' => [0.1, 0.2, 0.3],
        ];

        $client->request(
            method: 'POST',
            uri: '/index/work-log-entry',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);

        $documents = $store->all();
        self::assertCount(1, $documents);
        self::assertSame('functional-001', $documents[0]->attributes()['sourceEntryId']);
    }

    public function testMissingTextReturnsBadRequest(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        static::getContainer()->get(InMemoryVectorStore::class)->reset();

        $payload = [
            'entryId' => 'functional-002',
            'technologies' => ['PHP'],
            'embedding' => [0.1, 0.2],
        ];

        $client->request(
            method: 'POST',
            uri: '/index/work-log-entry',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
