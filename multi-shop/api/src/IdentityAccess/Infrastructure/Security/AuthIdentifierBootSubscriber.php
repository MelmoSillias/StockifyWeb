<?php

namespace App\IdentityAccess\Infrastructure\Security;

use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Boots AUTH_IDENTIFIER onto User so getUserIdentifier() can honour the flag
 * without injecting the container into the entity.
 */
final class AuthIdentifierBootSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%env(AUTH_IDENTIFIER)%')]
        private readonly string $authIdentifier = 'email',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 1024],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        User::setAuthIdentifierMode($this->authIdentifier);
    }
}
