<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Presentation\Controller;

use App\Modules\CVGeneration\Application\Contract\WorkLogEntryProviderInterface;
use App\Modules\CVGeneration\Application\Exception\NoWorkLogEntriesException;
use App\Modules\CVGeneration\Application\Query\GenerateWorkExperienceSectionQuery;
use App\Modules\CVGeneration\Application\Service\GenerateWorkExperienceSectionHandler;
use App\Modules\CVGeneration\Domain\WorkLogEntry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WorkExperiencePageController extends AbstractController
{
    public function __construct(
        private readonly GenerateWorkExperienceSectionHandler $handler,
        private readonly WorkLogEntryProviderInterface $workLogEntryProvider,
    ) {
    }

    #[Route('/cv/work-experience', name: 'cv_work_experience_page', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $limit = 10;
        $error = null;
        $workExperience = null;
        $formattedWorkExperience = null;

        $limitParam = $request->query->get('limit');
        if ($limitParam !== null) {
            if (is_numeric($limitParam)) {
                $limit = (int) $limitParam;
            } else {
                $error = 'Entries to include must be a positive integer.';
            }
        }

        if ($error === null && $limit < 1) {
            $error = 'Entries to include must be at least 1.';
        }

        if ($error === null) {
            try {
                $sourceEntries = $this->workLogEntryProvider->fetchRecent($limit);
                $dateQueue = $this->buildDateQueue($sourceEntries);

                $workExperience = ($this->handler)(
                    new GenerateWorkExperienceSectionQuery($limit),
                );
                $formattedWorkExperience = $this->formatMarkdown($workExperience, $dateQueue);
            } catch (NoWorkLogEntriesException $exception) {
                $error = $exception->getMessage();
            } catch (\Throwable $exception) {
                $error = sprintf('Generation failed: %s', $exception->getMessage());
            }
        }

        return $this->render(
            'cv/work_experience.html.twig',
            [
                'limit' => $limit,
                'workExperience' => $workExperience,
                'formattedWorkExperience' => $formattedWorkExperience,
                'error' => $error,
            ],
        );
    }

    /**
     * @param array<string, string> $headingMetadata
     */
    private function formatMarkdown(string $content, array $dateQueue = []): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $entries = [];
        $hasSectionHeading = false;
        $pendingHeading = null;
        $currentIndex = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if ($this->isWorkExperienceLabel($trimmed)) {
                $hasSectionHeading = true;
                 $pendingHeading = null;
                continue;
            }

            if (preg_match('/^[-*+]\s+(.+)$/', $trimmed, $matches) === 1) {
                if ($this->isWorkExperienceLabel($matches[1])) {
                    continue;
                }

                [$project, $role, $description] = $this->parseBullet($matches[1]);

                $heading = $this->normalizeHeading(
                    $this->headingFromParts($project, $role, $pendingHeading),
                );

                if ($heading === null) {
                    if ($currentIndex !== null) {
                        $heading = $entries[$currentIndex]['heading'];
                    } else {
                        $heading = 'Work Experience';
                    }
                }

                if ($currentIndex === null || $entries[$currentIndex]['heading'] !== $heading) {
                    $entries[] = [
                        'heading' => $heading,
                        'date' => array_shift($dateQueue),
                        'bullets' => [],
                    ];
                    $currentIndex = array_key_last($entries);
                }
                $bulletText = $this->renderInline(
                    $description !== '' ? $description : $matches[1],
                );

                if ($role !== null) {
                    $bulletText = sprintf(
                        '<strong>%s</strong>: %s',
                        htmlspecialchars($role, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                        $bulletText,
                    );
                }

                $entries[$currentIndex]['bullets'][] = $bulletText;

                $pendingHeading = null;
                continue;
            }

            $normalizedHeading = $this->normalizeHeading(
                $this->convertStandaloneHeading($trimmed),
            );

            if ($normalizedHeading === null) {
                continue;
            }

            $pendingHeading = $normalizedHeading;

            if ($currentIndex === null || $entries[$currentIndex]['heading'] !== $normalizedHeading) {
                $entries[] = [
                    'heading' => $normalizedHeading,
                    'date' => array_shift($dateQueue),
                    'bullets' => [],
                ];
                $currentIndex = array_key_last($entries);
            }
        }

        $entries = array_values(
            array_filter(
                $entries,
                static fn (array $entry): bool => $entry['bullets'] !== [],
            ),
        );

        if ($entries === []) {
            return '';
        }

        $htmlParts = [];

        if ($hasSectionHeading) {
            $htmlParts[] = '<h3>Work Experience</h3>';
        }

        foreach ($entries as $entry) {
            $htmlParts[] = '<div class="experience-item">';

            $headingLabel = $entry['heading'];
            if ($entry['date'] !== null) {
                $headingLabel = sprintf('%s (%s)', $headingLabel, $entry['date']);
            }

            $htmlParts[] = sprintf(
                '<div class="experience-role">%s</div>',
                htmlspecialchars($headingLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );

            if ($entry['bullets'] !== []) {
                $htmlParts[] = '<ul class="experience-list">';
                foreach ($entry['bullets'] as $bulletHtml) {
                    $htmlParts[] = sprintf('<li>%s</li>', $bulletHtml);
                }
                $htmlParts[] = '</ul>';
            }

            $htmlParts[] = '</div>';
        }

        return implode("\n", $htmlParts);
    }

    private function headingFromParts(?string $project, ?string $role, ?string $pending): ?string
    {
        if ($project !== null && $role !== null) {
            return sprintf('%s: %s', $project, $role);
        }

        if ($project !== null) {
            return $project;
        }

        if ($role !== null) {
            return $role;
        }

        return $pending;
    }

    private function convertStandaloneHeading(string $line): ?string
    {
        $line = trim($line);
        $line = trim($line, "* \t");

        if ($line === '' || $this->isWorkExperienceLabel($line)) {
            return null;
        }

        if (str_contains($line, ':')) {
            [$left, $right] = array_map('trim', explode(':', $line, 2));

            return $right !== '' ? sprintf('%s: %s', $right, $left) : $left;
        }

        if (str_contains($line, ',')) {
            [$left, $right] = array_map('trim', explode(',', $line, 2));

            return sprintf('%s: %s', $right, $left);
        }

        if (preg_match('/\s[-–]\s/u', $line) === 1) {
            [$left, $right] = array_map('trim', preg_split('/\s[-–]\s/u', $line, 2) ?: [$line]);

            return sprintf('%s: %s', $right, $left);
        }

        return $line;
    }

    private function normalizeHeading(?string $heading): ?string
    {
        if ($heading === null) {
            return null;
        }

        $trimmed = trim($heading);

        if ($trimmed === '' || $this->isWorkExperienceLabel($trimmed)) {
            return null;
        }

        return $trimmed;
    }

    private function renderInline(string $text): string
    {
        $parts = preg_split('/(\*\*[^*]+\*\*|\*[^*]+\*)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $html = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (str_starts_with($part, '**') && str_ends_with($part, '**')) {
                $inner = substr($part, 2, -2);
                $html .= sprintf('<strong>%s</strong>', htmlspecialchars($inner, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                continue;
            }

            if (str_starts_with($part, '*') && str_ends_with($part, '*')) {
                $inner = substr($part, 1, -1);
                $html .= sprintf('<em>%s</em>', htmlspecialchars($inner, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                continue;
            }

            $html .= htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $html;
    }

    private function isWorkExperienceLabel(string $text): bool
    {
        $normalized = trim($text);
        $normalized = trim($normalized, "* \t");
        $normalized = rtrim($normalized, ' :');

        return strcasecmp($normalized, 'Work Experience') === 0;
    }

    /**
     * @return array{?string, ?string, string}
     */
    private function parseBullet(string $text): array
    {
        $role = null;
        $project = null;
        $description = $text;

        if (preg_match('/^\*\*(.+?)\*\*\s*:?\s*(.*)$/', $text, $matches) === 1) {
            $header = trim($matches[1]);
            $description = $matches[2];

            if ($this->isWorkExperienceLabel($header)) {
                $header = '';
            }

            if (str_contains($header, ',')) {
                [$role, $project] = array_map('trim', explode(',', $header, 2));
            } elseif (preg_match('/\s[-–]\s/u', $header) === 1) {
                [$role, $project] = array_map('trim', preg_split('/\s[-–]\s/u', $header, 2) ?: [$header]);
            } else {
                $role = $header;
            }
        }

        if ($description === '') {
            $description = $text;
        }

        return [
            $project !== '' ? $project : null,
            $role !== '' ? $role : null,
            trim($description),
        ];
    }

    /**
     * @param list<WorkLogEntry> $entries
     * @return list<string|null>
     */
    private function buildDateQueue(array $entries): array
    {
        $queue = [];

        foreach ($entries as $entry) {
            $queue[] = $this->formatDateLabel($entry->loggedAt);
        }

        return $queue;
    }

    private function formatDateLabel(?\DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->format('Y');
    }
}
