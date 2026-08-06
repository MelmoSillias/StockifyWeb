<?php

namespace App\Onboarding\Presentation\Api;

use App\Onboarding\Application\Service\ControlPlaneException;
use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
final class PublicQuoteRequestController extends AbstractController
{
    public function __construct(
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
    ) {
    }

    #[Route('/quote-requests', name: 'api_public_quote_requests_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
            if (!isset($payload['applicationSlug'])) {
                $payload['applicationSlug'] = 'stockify';
            }

            return $this->json(
                $this->controlPlaneClient->submitQuoteRequest($payload),
                Response::HTTP_CREATED,
            );
        } catch (ControlPlaneException $exception) {
            return $this->json(['error' => $exception->getMessage()], $exception->getStatusCode());
        }
    }
}
