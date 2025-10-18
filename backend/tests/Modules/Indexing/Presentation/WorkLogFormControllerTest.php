<?php

declare(strict_types=1);

namespace App\Tests\Modules\Indexing\Presentation;

use App\Tests\Shared\InMemoryVectorStore;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class WorkLogFormControllerTest extends WebTestCase
{
    public function testFormRenders(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('GET', '/work-log/new');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Log Work Entry', $client->getResponse()->getContent());
    }

    public function testSuccessfulSubmissionIndexesEntry(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $store = static::getContainer()->get(InMemoryVectorStore::class);
        $store->reset();

        $client->request('POST', '/work-log/new', [
            'text' => 'Captured via HTML form.',
            'technologies' => 'PHP, Twig',
            'projectName' => 'CV Generator',
            'workType' => 'feature',
            'collaborators' => 'Alice, Bob',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $documents = $store->all();

        self::assertCount(1, $documents);
        $attributes = $documents[0]->attributes();
        self::assertNotEmpty($attributes['sourceEntryId']);
        self::assertSame(['PHP', 'Twig'], $attributes['technologies']);
        self::assertArrayHasKey('loggedAt', $attributes);
        self::assertNotNull($attributes['loggedAt']);
    }
}
