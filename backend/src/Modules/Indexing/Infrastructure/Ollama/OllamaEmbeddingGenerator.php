<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Infrastructure\Ollama;

use App\Modules\Indexing\Application\Service\EmbeddingGeneratorInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OllamaEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $endpoint,
        private readonly string $model,
    ) {
    }

    public function generate(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $response = $this->httpClient->request(
            'POST',
            sprintf('%s/api/embeddings', rtrim($this->endpoint, '/')),
            [
                'json' => [
                    'model' => $this->model,
                    'prompt' => $text,
                ],
            ],
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            $body = $response->getContent(false);
            throw new RuntimeException(sprintf('Embedding service responded with %d: %s', $statusCode, $body));
        }

        $payload = $response->toArray(false);

        $embedding = $payload['embedding'] ?? $payload['data'][0]['embedding'] ?? null;

        if (!\is_array($embedding)) {
            throw new RuntimeException('Embedding response did not contain a vector.');
        }

        return array_map(static fn (float|int|string $value): float => (float) $value, $embedding);
    }
}
