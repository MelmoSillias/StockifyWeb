<?php

namespace App\System\Presentation\Api;

use App\System\Application\Service\DeploymentReadinessChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends AbstractController
{
    public function __construct(
        private readonly DeploymentReadinessChecker $deploymentReadinessChecker,
    ) {
    }

    #[Route(name: 'api_health', path: '/api/health')]
    public function healthCheck(): JsonResponse
    {
        $missing = $this->deploymentReadinessChecker->missingRequirements();

        if ([] !== $missing) {
            return $this->json([
                'status' => 'degraded',
                'missing' => $missing,
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json([
            'status' => 'ok',
        ]);
    }
}
