<?php

namespace App\SharedKernel\Infrastructure\Tenant;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class TenantContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TenantContextResolver $resolver,
        private readonly TenantContextHolder $holder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onController', 10],
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if ($this->isPublicPath($path)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (!is_object($user)) {
            return;
        }

        if (!$this->requiresTenantContext($path, $request->getMethod())) {
            return;
        }

        $this->holder->set($this->resolver->resolve($user, $request));
    }

    private function isPublicPath(string $path): bool
    {
        return str_starts_with($path, '/api/health')
            || str_starts_with($path, '/api/login')
            || str_starts_with($path, '/api/register')
            || str_starts_with($path, '/api/token/refresh')
            || str_starts_with($path, '/api/units-of-measure');
    }

    private function requiresTenantContext(string $path, string $method): bool
    {
        if (str_starts_with($path, '/api/shops/')) {
            return true;
        }

        if ('POST' === $method && '/api/accounts' === $path) {
            return false;
        }

        if (str_starts_with($path, '/api/accounts/') && str_contains($path, '/shops') && 'POST' === $method) {
            return false;
        }

        return false;
    }
}
