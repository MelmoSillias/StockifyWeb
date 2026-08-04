<?php

namespace App\System\Presentation\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends AbstractController
{
    #[Route(name: 'api_health', path: '/api/health')]
    public function healthCheck(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
        ]);
    }
}
