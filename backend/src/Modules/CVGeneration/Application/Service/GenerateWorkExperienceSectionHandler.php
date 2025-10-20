<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Application\Service;

use App\Modules\CVGeneration\Application\Contract\LanguageModelInterface;
use App\Modules\CVGeneration\Application\Contract\WorkLogEntryProviderInterface;
use App\Modules\CVGeneration\Application\Exception\NoWorkLogEntriesException;
use App\Modules\CVGeneration\Application\Query\GenerateWorkExperienceSectionQuery;
use App\Modules\CVGeneration\Domain\WorkLogEntry;

final class GenerateWorkExperienceSectionHandler
{
    private const SYSTEM_PROMPT = 'You are an expert CV writer. Craft a compelling work experience section suitable for a software engineer resume. Summarize achievements with clear bullet points, highlight impact, quantify results when possible, and blend multiple related entries into cohesive role descriptions. Keep the tone professional, confident, and concise.';

    public function __construct(
        private readonly WorkLogEntryProviderInterface $workLogEntryProvider,
        private readonly LanguageModelInterface $languageModel,
    ) {
    }

    public function __invoke(GenerateWorkExperienceSectionQuery $query): string
    {
        $entries = $this->workLogEntryProvider->fetchRecent($query->maxEntries);

        if ($entries === []) {
            throw new NoWorkLogEntriesException('No work log entries available for CV generation.');
        }

        $prompt = $this->buildPrompt($entries);

        $rawOutput = $this->languageModel->generateText($prompt, self::SYSTEM_PROMPT);

        return $this->normalizeOutput($rawOutput);
    }

    /**
     * @param list<WorkLogEntry> $entries
     */
    private function buildPrompt(array $entries): string
    {
        $lines = [
            'Use the following work log entries to author the work experience section.',
            'Organize them chronologically (most recent first) and group related items when appropriate.',
            'Each entry includes context fields:',
            '- Description: narrative of the work.',
            '- Project: project or client name.',
            '- Work type: nature of the work.',
            '- Role: capacity of the contributor.',
            '- Impact: business value delivered.',
            '- Technologies: stack used.',
            '- Collaborators: key collaborators.',
            '- Logged at: timestamp.',
            '',
            'Work log entries:',
        ];

        foreach ($entries as $index => $entry) {
            $lines[] = sprintf('Entry %d:', $index + 1);
            $lines[] = sprintf('  Description: %s', $entry->content);

            if ($entry->projectName !== null) {
                $lines[] = sprintf('  Project: %s', $entry->projectName);
            }

            if ($entry->workType !== null) {
                $lines[] = sprintf('  Work type: %s', $entry->workType);
            }

            if ($entry->role !== null) {
                $lines[] = sprintf('  Role: %s', $entry->role);
            }

            if ($entry->businessImpact !== null) {
                $lines[] = sprintf('  Impact: %s', $entry->businessImpact);
            }

            if ($entry->technologies !== []) {
                $lines[] = sprintf('  Technologies: %s', implode(', ', $entry->technologies));
            }

            if ($entry->collaborators !== []) {
                $lines[] = sprintf('  Collaborators: %s', implode(', ', $entry->collaborators));
            }

            if ($entry->loggedAt !== null) {
                $lines[] = sprintf('  Logged at: %s', $entry->loggedAt->format('c'));
            }

            $lines[] = sprintf('  Entry id: %s', $entry->entryId);
            $lines[] = '';
        }

        $lines[] = 'Return only the final work experience content.';
        $lines[] = 'If you include a section title, write it as plain text "Work Experience" (no Markdown heading symbols) followed by a colon.';
        $lines[] = 'Then, for each role/project, follow this pattern exactly:';
        $lines[] = 'Senior Backend Engineer: Europa Park';
        $lines[] = '- Achievement that highlights measurable impact.';
        $lines[] = '- Additional achievement (if applicable).';
        $lines[] = '';
        $lines[] = 'Do not include narrative sentences outside of bullet points. Avoid phrases like "Here is" or "Below is".';
        $lines[] = 'Ensure every role heading stands alone on its own line, with its achievements listed underneath as "-" bullets.';

        return implode("\n", $lines);
    }

    private function normalizeOutput(string $content): string
    {
        $normalizedContent = preg_replace('/\r\n|\r|\n/', "\n", $content);
        $normalizedContent = preg_replace('/\s*[•·]\s*/u', "\n- ", $normalizedContent);

        $lines = preg_split('/\n/', $normalizedContent) ?: [];
        $normalized = [];
        $started = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($started && $normalized !== [] && end($normalized) !== '') {
                    $normalized[] = '';
                }
                continue;
            }

            $plain = $this->stripFormatting($trimmed);
            $isHeading = $this->isWorkExperienceHeading($plain);
            $isBullet = $this->isBulletLine($trimmed);

            if (!$started && !($isHeading || $isBullet)) {
                continue;
            }

            if ($isHeading) {
                if (!$started) {
                    $normalized[] = 'Work Experience:';
                }
                $started = true;
                continue;
            }

            $started = true;

            if ($isBullet) {
                $normalized[] = preg_replace('/^[-*+]\s*/', '- ', $trimmed, 1);
                continue;
            }

            $normalized[] = $trimmed;
        }

        // Remove leading/trailing blank lines
        while ($normalized !== [] && $normalized[0] === '') {
            array_shift($normalized);
        }

        while ($normalized !== [] && end($normalized) === '') {
            array_pop($normalized);
        }

        if ($normalized === []) {
            return trim($content);
        }

        if (!str_starts_with($normalized[0], 'Work Experience')) {
            array_unshift($normalized, 'Work Experience:');
        }

        // Collapse multiple blank lines
        $collapsed = [];
        foreach ($normalized as $line) {
            if ($line === '' && ($collapsed === [] || end($collapsed) === '')) {
                continue;
            }

            $collapsed[] = $line;
        }

        return implode("\n", $collapsed);
    }

    private function stripFormatting(string $line): string
    {
        $unwrapped = trim($line, "*_ \t#:>-");

        return strtolower($unwrapped);
    }

    private function isWorkExperienceHeading(string $line): bool
    {
        return $line === 'work experience' || $line === 'work experience section';
    }

    private function isBulletLine(string $line): bool
    {
        return preg_match('/^[-*+]\s+/', $line) === 1;
    }
}
