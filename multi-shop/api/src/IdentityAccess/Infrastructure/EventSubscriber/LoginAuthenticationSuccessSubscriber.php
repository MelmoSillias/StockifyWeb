<?php

namespace App\IdentityAccess\Infrastructure\EventSubscriber;

use App\IdentityAccess\Application\Service\RefreshTokenService;
use App\IdentityAccess\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class LoginAuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $pair = $this->refreshTokenService->createTokenPair($user, $request);

        $data = $event->getData();
        $data['access_token'] = $pair['access_token'];
        $data['refresh_token'] = $pair['refresh_token'];
        unset($data['token']);
        $event->setData($data);

        $response = $event->getResponse();
        if ($response instanceof JWTAuthenticationSuccessResponse) {
            $response->headers->setCookie($pair['cookie']);
        }
    }
}
