<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Infrastructure\Weaviate;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class WeaviateSchemaConfigurator
{
    /**
     * @param array<string, mixed> $schemaDefinition
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $endpoint,
        private readonly array $schemaDefinition,
    ) {
    }

    /**
     * Ensures the target class exists in Weaviate. Returns true if it was created.
     *
     * @throws TransportExceptionInterface
     */
    public function ensureSchema(): bool
    {
        $className = $this->schemaDefinition['class'] ?? null;
        if (!\is_string($className) || $className === '') {
            throw new RuntimeException('Weaviate schema configuration must contain a non-empty class name.');
        }

        $classUrl = sprintf(
            '%s/v1/schema/%s',
            rtrim($this->endpoint, '/'),
            rawurlencode($className),
        );

        $response = $this->httpClient->request('GET', $classUrl);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 200) {
            return false;
        }

        if ($statusCode !== 404) {
            $body = $response->getContent(false);
            throw new RuntimeException(
                sprintf('Failed to inspect Weaviate schema for class "%s": %s', $className, $body),
            );
        }

        $createResponse = $this->httpClient->request(
            'POST',
            sprintf('%s/v1/schema', rtrim($this->endpoint, '/')),
            ['json' => $this->schemaDefinition],
        );

        if ($createResponse->getStatusCode() >= 300) {
            $body = $createResponse->getContent(false);
            throw new RuntimeException(
                sprintf('Unable to create Weaviate class "%s": %s', $className, $body),
            );
        }

        return true;
    }
}
