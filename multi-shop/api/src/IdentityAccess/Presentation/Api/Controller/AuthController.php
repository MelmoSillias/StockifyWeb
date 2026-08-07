<?php

namespace App\IdentityAccess\Presentation\Api\Controller;

use App\IdentityAccess\Application\Service\EmailVerificationSyncService;
use App\IdentityAccess\Application\Service\GlobalAuthDisabledException;
use App\IdentityAccess\Application\Service\GlobalAuthFailedException;
use App\IdentityAccess\Application\Service\GlobalAuthService;
use App\IdentityAccess\Application\Service\RefreshTokenService;
use App\IdentityAccess\Application\Service\RegisterUserService;
use App\IdentityAccess\Application\Service\ResendVerificationEmailService;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly RegisterUserService $registerUserService,
        private readonly RefreshTokenService $refreshTokenService,
        private readonly GlobalAuthService $globalAuthService,
        private readonly ResendVerificationEmailService $resendVerificationEmailService,
        private readonly EmailVerificationSyncService $emailVerificationSyncService,
    ) {
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    #[IsGranted('access.users.create')]
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

    #[Route('/auth/global', name: 'api_auth_global', methods: ['POST'])]
    public function globalLogin(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $email = isset($data['email']) ? (string) $data['email'] : '';
        $password = isset($data['password']) ? (string) $data['password'] : '';

        if ('' === trim($email) || '' === $password) {
            return $this->json(['error' => 'email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->globalAuthService->authenticate(
                $email,
                $password,
                (string) ($data['application'] ?? 'stockify'),
            );
        } catch (GlobalAuthDisabledException) {
            return $this->json(['error' => 'Global identity authentication is disabled.'], Response::HTTP_NOT_FOUND);
        } catch (GlobalAuthFailedException $exception) {
            $status = $exception->getCode() >= 400 ? $exception->getCode() : Response::HTTP_UNAUTHORIZED;

            return $this->json(['error' => $exception->getMessage()], $status);
        }

        return $this->json($result);
    }

    #[Route('/auth/verification/resend', name: 'api_auth_verification_resend', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function resendVerificationEmail(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isEmailVerified()) {
            return $this->json(['message' => 'Email is already verified.']);
        }

        try {
            $this->resendVerificationEmailService->resend($user);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'If your account requires verification, a new email has been sent.']);
    }

    #[Route('/auth/verification/sync', name: 'api_auth_verification_sync', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function syncVerificationEmail(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        $verified = $this->emailVerificationSyncService->syncFromControlPlane($user);

        return $this->json([
            'email_verified' => $verified,
            'email_verified_at' => $user->getEmailVerifiedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}
