<?php

namespace App\IdentityAccess\Security;

use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class EmailVerifiedRequestSubscriber implements EventSubscriberInterface
{
    /** @var list<string> */
    private const ALLOWED_PATHS = [
        '/api/me',
        '/api/auth/verification/resend',
        '/api/auth/verification/sync',
        '/api/login',
        '/api/login_check',
        '/api/token/refresh',
    ];

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/api/public')) {
            return;
        }

        foreach (self::ALLOWED_PATHS as $allowedPath) {
            if ($request->getPathInfo() === $allowedPath) {
                return;
            }
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (!$user instanceof User) {
            return;
        }

        if (null === $user->getIdentityId()) {
            return;
        }

        if ($user->isEmailVerified()) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => 'Email address is not verified.',
            'code' => 'email_not_verified',
        ], Response::HTTP_FORBIDDEN));
    }
}
