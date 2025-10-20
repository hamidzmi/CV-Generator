<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Application\Contract;

interface LanguageModelInterface
{
    public function generateText(string $prompt, ?string $systemInstructions = null): string;
}

