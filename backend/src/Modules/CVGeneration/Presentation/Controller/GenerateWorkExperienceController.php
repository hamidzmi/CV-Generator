<?php

declare(strict_types=1);

namespace App\Modules\CVGeneration\Presentation\Controller;

use App\Modules\CVGeneration\Application\Exception\NoWorkLogEntriesException;
use App\Modules\CVGeneration\Application\Query\GenerateWorkExperienceSectionQuery;
use App\Modules\CVGeneration\Application\Service\GenerateWorkExperienceSectionHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GenerateWorkExperienceController extends AbstractController
{
    public function __construct(private readonly GenerateWorkExperienceSectionHandler $handler)
    {
    }

    #[Route('/cv/work-experience', name: 'cv_generate_work_experience', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $limit = 10;

        if ($request->getContent() !== '') {
            try {
                $payload = $request->toArray();
            } catch (\JsonException) {
                return new JsonResponse(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
            }

            if (isset($payload['limit'])) {
                $rawLimit = $payload['limit'];
                if (!\is_int($rawLimit)) {
                    if (\is_string($rawLimit) && is_numeric($rawLimit)) {
                        $rawLimit = (int) $rawLimit;
                    } else {
                        return new JsonResponse(
                            ['message' => 'limit must be an integer'],
                            Response::HTTP_BAD_REQUEST,
                        );
                    }
                }

                if ($rawLimit < 1) {
                    return new JsonResponse(
                        ['message' => 'limit must be at least 1'],
                        Response::HTTP_BAD_REQUEST,
                    );
                }

                $limit = $rawLimit;
            }
        }

        try {
            $section = ($this->handler)(
                new GenerateWorkExperienceSectionQuery($limit),
            );
        } catch (NoWorkLogEntriesException $exception) {
            return new JsonResponse(
                ['message' => $exception->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        return new JsonResponse([
            'workExperience' => $section,
        ]);
    }
}

