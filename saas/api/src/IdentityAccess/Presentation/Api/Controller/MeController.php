<?php

namespace App\IdentityAccess\Presentation\Api\Controller;

use App\IdentityAccess\Application\Query\GetUserProfile\GetUserProfileHandler;
use App\IdentityAccess\Application\Query\GetUserProfile\GetUserProfileQuery;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class MeController extends AbstractController
{
    public function __construct(
        private readonly GetUserProfileHandler $getUserProfileHandler,
    ) {
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json($this->getUserProfileHandler->handle(new GetUserProfileQuery($user)));
    }
}
