<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Infrastructure\Weaviate;

use App\Modules\CVGeneration\Application\Contract\WorkLogEntryProviderInterface;
use App\Modules\CVGeneration\Domain\WorkLogEntry;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeaviateWorkLogEntryProvider implements WorkLogEntryProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $endpoint,
        private readonly string $className,
    ) {
        if (!\preg_match('/^[a-zA-Z0-9_]+$/', $this->className)) {
            throw new RuntimeException('Invalid Weaviate class name.');
        }
    }

    public function fetchRecent(int $limit): array
    {
        $query = sprintf(
            <<<'GRAPHQL'
            {
                Get {
                    %s(
                        limit: %d,
                        sort: [{ path: ["loggedAt"], order: desc }]
                    ) {
                        sourceEntryId
                        content
                        projectName
                        workType
                        businessImpact
                        role
                        technologies
                        collaborators
                        loggedAt
                    }
                }
            }
            GRAPHQL,
            $this->className,
            $limit,
        );

        $response = $this->httpClient->request(
            'POST',
            sprintf('%s/v1/graphql', rtrim($this->endpoint, '/')),
            [
                'json' => ['query' => $query],
            ],
        );

        $status = $response->getStatusCode();
        if ($status >= 400) {
            $body = $response->getContent(false);
            throw new RuntimeException(sprintf('Failed to query Weaviate: %s', $body));
        }

        $payload = $response->toArray(false);

        $entries = $payload['data']['Get'][$this->className] ?? null;
        if (!\is_array($entries)) {
            return [];
        }

        $results = [];
        foreach ($entries as $rawEntry) {
            if (!\is_array($rawEntry)) {
                continue;
            }

            $results[] = new WorkLogEntry(
                entryId: (string) ($rawEntry['sourceEntryId'] ?? $rawEntry['id'] ?? ''),
                content: (string) ($rawEntry['content'] ?? ''),
                projectName: $this->nullableString($rawEntry['projectName'] ?? null),
                workType: $this->nullableString($rawEntry['workType'] ?? null),
                businessImpact: $this->nullableString($rawEntry['businessImpact'] ?? null),
                role: $this->nullableString($rawEntry['role'] ?? null),
                technologies: $this->stringList($rawEntry['technologies'] ?? []),
                collaborators: $this->stringList($rawEntry['collaborators'] ?? []),
                loggedAt: $this->nullableDate($rawEntry['loggedAt'] ?? null),
            );
        }

        return $results;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $results = [];
        foreach ($value as $item) {
            if (\is_string($item)) {
                $trimmed = trim($item);
                if ($trimmed !== '') {
                    $results[] = $trimmed;
                }
            }
        }

        return $results;
    }

    private function nullableDate(mixed $value): ?DateTimeImmutable
    {
        if (!\is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

