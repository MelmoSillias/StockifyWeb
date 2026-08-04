<?php

namespace App\Onboarding\Presentation\Api;

use App\Onboarding\Application\Service\ControlPlaneGatewayInterface;
use App\Onboarding\Application\Service\ControlPlaneException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
final class PublicPlansController extends AbstractController
{
    public function __construct(
        private readonly ControlPlaneGatewayInterface $controlPlaneClient,
    ) {
    }

    #[Route('/plans', name: 'api_public_plans_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $applicationSlug = (string) ($request->query->get('application') ?? 'stockify');

        try {
            return $this->json($this->controlPlaneClient->fetchPublicPlans($applicationSlug));
        } catch (ControlPlaneException $exception) {
            return $this->json(['error' => $exception->getMessage()], $exception->getStatusCode());
        }
    }
}
