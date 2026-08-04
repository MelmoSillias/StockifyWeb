<?php

namespace App\AccessAudit\Infrastructure\EventSubscriber;

use App\AccessAudit\Application\Service\AuditLoggerService;
use App\AccessAudit\Domain\Enum\AuditStatus;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AuditEventSubscriber implements EventSubscriberInterface
{
    private float $requestStart = 0.0;

    public function __construct(
        private readonly AuditLoggerService $auditLogger,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 0],
            KernelEvents::TERMINATE => ['onTerminate', -10],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->requestStart = microtime(true);
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $method = $request->getMethod();

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api')) {
            return;
        }

        if (in_array($path, ['/api/login_check', '/api/token/refresh'], true)) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        $durationMs = $this->requestStart > 0
            ? (int) round((microtime(true) - $this->requestStart) * 1000)
            : null;

        $route = $request->attributes->get('_route');
        $action = is_string($route) ? $route : $method.' '.$path;

        $status = $response->getStatusCode() >= 400 ? AuditStatus::Failure : AuditStatus::Success;

        $this->auditLogger->logAction(
            action: 'api.'.$action,
            status: $status,
            user: $user instanceof User ? $user : null,
            resourceType: 'api',
            request: $request,
            payloadSummary: [
                'status_code' => $response->getStatusCode(),
                'path' => $path,
            ],
            durationMs: $durationMs,
        );
    }
}
