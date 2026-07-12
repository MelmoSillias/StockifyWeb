<?php

namespace App\IdentityAccess\Presentation\Api\Controller;

use App\IdentityAccess\Application\Service\RefreshTokenService;
use App\IdentityAccess\Application\Service\RegisterUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly RegisterUserService $registerUserService,
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $request->toArray();
        foreach (['email', 'password', 'first_name', 'last_name'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => sprintf('Field %s is required.', $field)], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $user = $this->registerUserService->register(
                $data['email'],
                $data['password'],
                $data['first_name'],
                $data['last_name'],
                $data['username'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        $tokens = $this->refreshTokenService->createTokenPair($user);

        return $this->json($tokens, Response::HTTP_CREATED);
    }

    #[Route('/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data['refresh_token'])) {
            return $this->json(['error' => 'refresh_token is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokens = $this->refreshTokenService->refresh($data['refresh_token']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($tokens);
    }
}
