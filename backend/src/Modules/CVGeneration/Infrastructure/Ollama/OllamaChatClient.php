<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Infrastructure\Ollama;

use App\Modules\CVGeneration\Application\Contract\LanguageModelInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OllamaChatClient implements LanguageModelInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $endpoint,
        private readonly string $model,
    ) {
    }

    public function generateText(string $prompt, ?string $systemInstructions = null): string
    {
        if ($prompt === '') {
            throw new RuntimeException('Prompt cannot be empty.');
        }

        $messages = [];

        if ($systemInstructions !== null && trim($systemInstructions) !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => $systemInstructions,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $this->httpClient->request(
            'POST',
            sprintf('%s/api/chat', rtrim($this->endpoint, '/')),
            [
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ],
            ],
        );

        $status = $response->getStatusCode();
        if ($status >= 400) {
            $body = $response->getContent(false);
            throw new RuntimeException(sprintf('Language model request failed with %d: %s', $status, $body));
        }

        $payload = $response->toArray(false);

        $content = $payload['message']['content'] ?? null;
        if (!\is_string($content)) {
            throw new RuntimeException('Unexpected response from language model.');
        }

        return trim($content);
    }
}

