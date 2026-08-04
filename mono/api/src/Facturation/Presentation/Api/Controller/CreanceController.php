<?php

namespace App\Facturation\Presentation\Api\Controller;

use App\Facturation\Application\Service\CreanceDetailMapper;
use App\Facturation\Domain\Enum\CreanceFilterStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class CreanceController extends AbstractController
{
    public function __construct(
        private readonly CreanceDetailMapper $creanceDetailMapper,
    ) {
    }

    #[Route('/creances', name: 'api_creances_list', methods: ['GET'])]
    #[IsGranted('client.creances.view')]
    public function list(Request $request): JsonResponse
    {
        $clientId = $request->query->get('client_id');
        $status = $this->resolveStatus($request->query->get('status'));

        try {
            $clientUuid = null !== $clientId && '' !== $clientId
                ? Uuid::fromString((string) $clientId)
                : null;
        } catch (\InvalidArgumentException) {
            return $this->json(['error' => 'Invalid client_id.'], Response::HTTP_BAD_REQUEST);
        }

        $items = $this->creanceDetailMapper->mapAll($clientUuid, $status);

        return $this->json(array_map(
            static fn ($item) => $item->toArray(),
            $items,
        ));
    }

    private function resolveStatus(mixed $value): CreanceFilterStatus
    {
        if (null === $value || '' === $value) {
            return CreanceFilterStatus::Open;
        }

        return CreanceFilterStatus::from((string) $value);
    }
}
