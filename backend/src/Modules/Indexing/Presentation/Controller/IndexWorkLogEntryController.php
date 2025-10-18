<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Presentation\Controller;

use App\Modules\Indexing\Application\Command\IndexWorkLogEntryCommand;
use App\Modules\Indexing\Application\Command\IndexWorkLogEntryHandler;
use DateTimeImmutable;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexWorkLogEntryController extends AbstractController
{
    public function __construct(private readonly IndexWorkLogEntryHandler $handler)
    {
    }

    #[Route('/index/work-log-entry', name: 'index_work_log_entry', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $payload = $request->toArray();
        } catch (JsonException) {
            return new JsonResponse(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $entryId = $payload['entryId'] ?? null;
        $text = $payload['text'] ?? null;
        $embedding = $payload['embedding'] ?? null;
        $technologies = $payload['technologies'] ?? [];
        $projectName = $payload['projectName'] ?? null;
        $workType = $payload['workType'] ?? null;
        $businessImpact = $payload['businessImpact'] ?? null;
        $role = $payload['role'] ?? null;
        $collaborators = $payload['collaborators'] ?? [];
        $loggedAtRaw = $payload['loggedAt'] ?? null;

        if (!\is_string($entryId) || trim($entryId) === '') {
            return new JsonResponse(['message' => 'entryId is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!\is_string($text) || trim($text) === '') {
            return new JsonResponse(['message' => 'text is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!\is_array($embedding)) {
            return new JsonResponse(
                ['message' => 'embedding must be an array of numbers'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $normalizedEmbedding = [];
        foreach ($embedding as $dimension) {
            if (!\is_int($dimension) && !\is_float($dimension) && !(\is_string($dimension) && is_numeric($dimension))) {
                return new JsonResponse(
                    ['message' => 'embedding must contain only numeric values'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $normalizedEmbedding[] = (float) $dimension;
        }

        if (!\is_array($technologies)) {
            return new JsonResponse(['message' => 'technologies must be an array'], Response::HTTP_BAD_REQUEST);
        }

        $normalizedTechnologies = [];
        foreach ($technologies as $technology) {
            if (!\is_string($technology)) {
                return new JsonResponse(
                    ['message' => 'technologies must contain only strings'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $trimmed = trim($technology);
            if ($trimmed !== '') {
                $normalizedTechnologies[] = $trimmed;
            }
        }

        $normalizedTechnologies = array_values(array_unique($normalizedTechnologies));

        if ($projectName !== null && (!\is_string($projectName) || trim($projectName) === '')) {
            return new JsonResponse(['message' => 'projectName must be a non-empty string when provided'], Response::HTTP_BAD_REQUEST);
        }

        if ($workType !== null && (!\is_string($workType) || trim($workType) === '')) {
            return new JsonResponse(['message' => 'workType must be a non-empty string when provided'], Response::HTTP_BAD_REQUEST);
        }

        if ($businessImpact !== null && (!\is_string($businessImpact) || trim($businessImpact) === '')) {
            return new JsonResponse(['message' => 'businessImpact must be a non-empty string when provided'], Response::HTTP_BAD_REQUEST);
        }

        if ($role !== null && (!\is_string($role) || trim($role) === '')) {
            return new JsonResponse(['message' => 'role must be a non-empty string when provided'], Response::HTTP_BAD_REQUEST);
        }

        if (!\is_array($collaborators)) {
            return new JsonResponse(['message' => 'collaborators must be an array'], Response::HTTP_BAD_REQUEST);
        }

        $normalizedCollaborators = [];
        foreach ($collaborators as $collaborator) {
            if (!\is_string($collaborator)) {
                return new JsonResponse(
                    ['message' => 'collaborators must contain only strings'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $trimmedCollaborator = trim($collaborator);
            if ($trimmedCollaborator !== '') {
                $normalizedCollaborators[] = $trimmedCollaborator;
            }
        }

        $normalizedCollaborators = array_values(array_unique($normalizedCollaborators));

        $loggedAt = null;
        if ($loggedAtRaw !== null) {
            if (!\is_string($loggedAtRaw)) {
                return new JsonResponse(['message' => 'loggedAt must be a string'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $loggedAt = new DateTimeImmutable($loggedAtRaw);
            } catch (Exception) {
                return new JsonResponse(
                    ['message' => 'loggedAt must be a valid date-time string'],
                    Response::HTTP_BAD_REQUEST,
                );
            }
        }

        ($this->handler)(
            new IndexWorkLogEntryCommand(
                $entryId,
                $text,
                $normalizedTechnologies,
                $loggedAt,
                $projectName ? trim($projectName) : null,
                $workType ? trim($workType) : null,
                $businessImpact ? trim($businessImpact) : null,
                $role ? trim($role) : null,
                $normalizedCollaborators,
                $normalizedEmbedding,
            ),
        );

        return new JsonResponse(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }
}
