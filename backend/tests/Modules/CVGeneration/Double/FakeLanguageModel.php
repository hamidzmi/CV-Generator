<?php

declare(strict_types=1);

namespace App\Tests\Modules\CVGeneration\Double;

use App\Modules\CVGeneration\Application\Contract\LanguageModelInterface;

final class FakeLanguageModel implements LanguageModelInterface
{
    public string $lastPrompt = '';
    public ?string $lastSystemInstructions = null;
    public string $nextResponse = 'Generated work experience section.';

    public function generateText(string $prompt, ?string $systemInstructions = null): string
    {
        $this->lastPrompt = $prompt;
        $this->lastSystemInstructions = $systemInstructions;

        return $this->nextResponse;
    }
}

