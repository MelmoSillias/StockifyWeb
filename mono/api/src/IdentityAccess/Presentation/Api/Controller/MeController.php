<?php

namespace App\IdentityAccess\Presentation\Api\Controller;

use App\IdentityAccess\Application\Command\ChangePassword\ChangePasswordCommand;
use App\IdentityAccess\Application\Command\ChangePassword\ChangePasswordHandler;
use App\IdentityAccess\Application\Query\GetLoginHistory\GetLoginHistoryHandler;
use App\IdentityAccess\Application\Query\GetLoginHistory\GetLoginHistoryQuery;
use App\IdentityAccess\Application\Query\GetUserProfile\GetUserProfileHandler;
use App\IdentityAccess\Application\Query\GetUserProfile\GetUserProfileQuery;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class MeController extends AbstractController
{
    public function __construct(
        private readonly GetUserProfileHandler $getUserProfileHandler,
        private readonly ChangePasswordHandler $changePasswordHandler,
        private readonly GetLoginHistoryHandler $getLoginHistoryHandler,
    ) {
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($this->getUserProfileHandler->handle(new GetUserProfileQuery($user)));
    }

    #[Route('/me/password', name: 'api_me_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Corps de requête invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $currentPassword = $payload['current_password'] ?? null;
        $newPassword = $payload['new_password'] ?? null;

        if (!is_string($currentPassword) || $currentPassword === '') {
            return $this->json(['error' => 'Le mot de passe actuel est requis.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_string($newPassword) || $newPassword === '') {
            return $this->json(['error' => 'Le nouveau mot de passe est requis.'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($newPassword) < 8) {
            return $this->json(['error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->changePasswordHandler->handle(new ChangePasswordCommand($user, $currentPassword, $newPassword));
        } catch (\DomainException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        }

        return $this->json(['message' => 'Mot de passe mis à jour.']);
    }

    #[Route('/me/login-history', name: 'api_me_login_history', methods: ['GET'])]
    public function loginHistory(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));

        return $this->json($this->getLoginHistoryHandler->handle(new GetLoginHistoryQuery($user, $page, $limit)));
    }
}
