<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Presentation\Controller;

use App\Modules\Indexing\Application\Command\IndexWorkLogEntryCommand;
use App\Modules\Indexing\Application\Command\IndexWorkLogEntryHandler;
use App\Modules\Indexing\Application\Service\EmbeddingGeneratorInterface;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class WorkLogFormController extends AbstractController
{
    public function __construct(
        private readonly IndexWorkLogEntryHandler $handler,
        private readonly EmbeddingGeneratorInterface $embeddingGenerator,
    ) {
    }

    #[Route('/work-log/new', name: 'work_log_new', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $data = [
            'text' => $request->request->get('text', ''),
            'technologies' => $request->request->get('technologies', ''),
            'projectName' => $request->request->get('projectName', ''),
            'workType' => $request->request->get('workType', ''),
            'businessImpact' => $request->request->get('businessImpact', ''),
            'role' => $request->request->get('role', ''),
            'collaborators' => $request->request->get('collaborators', ''),
        ];

        $errors = [];

        if ($request->isMethod('POST')) {
            $normalizedTechnologies = $this->splitList($data['technologies']);
            $normalizedCollaborators = $this->splitList($data['collaborators']);

            if (trim($data['text']) === '') {
                $errors['text'] = 'Description is required.';
            }

            $entryId = Uuid::v4()->toRfc4122();

            $embeddingVector = [];
            if ($errors === []) {
                try {
                    $embeddingVector = $this->embeddingGenerator->generate(trim((string) $data['text']));
                } catch (\Throwable $exception) {
                    $errors['embedding'] = 'Embedding could not be generated: ' . $exception->getMessage();
                }

                if ($embeddingVector === []) {
                    $errors['embedding'] = 'Embedding service returned an empty vector.';
                }
            }

            if ($errors === []) {
                $command = new IndexWorkLogEntryCommand(
                    entryId: $entryId,
                    text: trim((string) $data['text']),
                    technologies: $normalizedTechnologies,
                    loggedAt: new DateTimeImmutable(),
                    projectName: $this->nullableString($data['projectName']),
                    workType: $this->nullableString($data['workType']),
                    businessImpact: $this->nullableString($data['businessImpact']),
                    role: $this->nullableString($data['role']),
                    collaborators: $normalizedCollaborators,
                    embedding: $embeddingVector,
                );

                ($this->handler)($command);

                $this->addFlash('success', 'Work log entry indexed successfully.');

                return new RedirectResponse($this->generateUrl('work_log_new'));
            }

            $this->addFlash('error', 'Please fix the highlighted issues and try again.');
        }

        return $this->render(
            'indexing/work_log_form.html.twig',
            [
                'data' => $data,
                'errors' => $errors,
            ],
        );
    }

    /**
     * @return list<string>
     */
    private function splitList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $parts = preg_split('/[\n,]+/', $value) ?: [];

        return array_values(
            array_unique(
                array_filter(
                    array_map(static fn (string $item): string => trim($item), $parts),
                    static fn (string $item): bool => $item !== '',
                ),
            ),
        );
    }

    private function nullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
