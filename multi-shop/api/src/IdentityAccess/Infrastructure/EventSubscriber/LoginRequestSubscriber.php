<?php

namespace App\IdentityAccess\Infrastructure\EventSubscriber;

use App\IdentityAccess\Infrastructure\Security\LoginContextHolder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LoginRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoginContextHolder $loginContextHolder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 7],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ('/api/login_check' !== $request->getPathInfo()) {
            $this->loginContextHolder->clear();

            return;
        }

        $this->loginContextHolder->clear();

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return;
        }

        $shopSlug = $payload['shop_slug'] ?? null;
        $this->loginContextHolder->setShopSlug(is_string($shopSlug) ? $shopSlug : null);

        $identifier = $payload['identifier']
            ?? $payload['email']
            ?? $payload['username']
            ?? null;

        $password = $payload['password'] ?? null;

        if (is_string($password)) {
            $this->loginContextHolder->setPassword($password);
        }

        if (!is_string($identifier) || !is_string($password)) {
            return;
        }

        $normalized = json_encode([
            'email' => strtolower(trim($identifier)),
            'password' => $password,
        ], JSON_THROW_ON_ERROR);

        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $normalized,
        );
    }
}
