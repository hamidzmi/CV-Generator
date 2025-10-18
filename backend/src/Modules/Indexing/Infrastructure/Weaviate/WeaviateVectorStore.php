<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Infrastructure\Weaviate;

use App\Modules\Indexing\Domain\Entity\Document;
use App\Modules\Indexing\Domain\Repository\VectorStore;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeaviateVectorStore implements VectorStore
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $endpoint,
        private readonly string $className,
    ) {
    }

    public function upsert(Document $document): void
    {
        $properties = array_merge(
            ['content' => $document->content()],
            $document->attributes(),
        );

        $properties = array_filter(
            $properties,
            static fn (mixed $value): bool => $value !== null,
        );

        $payload = [
            'class' => $this->className,
            'id' => (string) $document->id(),
            'properties' => $properties,
            'vector' => $document->embedding()->toArray(),
        ];

        $response = $this->httpClient->request(
            'POST',
            sprintf('%s/v1/objects', rtrim($this->endpoint, '/')),
            ['json' => $payload],
        );

        $status = $response->getStatusCode();
        if ($status >= 400) {
            $body = $response->getContent(false);
            throw new RuntimeException(sprintf('Failed to upsert document in Weaviate: %s', $body));
        }
    }
}
