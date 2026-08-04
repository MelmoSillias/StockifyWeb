<?php

namespace App\AccessAudit\Infrastructure\EventSubscriber;

use App\AccessAudit\Application\Service\AuditLoggerService;
use App\AccessAudit\Domain\Enum\AuditStatus;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\UserRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

final class LoginAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuditLoggerService $auditLogger,
        private readonly RequestStack $requestStack,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
            'lexik_jwt_authentication.on_authentication_failure' => 'onAuthenticationFailure',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $user->recordLogin();
        $this->userRepository->save($user);
        $this->auditLogger->logLogin($user, AuditStatus::Success, $this->requestStack->getCurrentRequest());
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = json_decode($request?->getContent() ?? '{}', true);
        $identifier = is_array($payload) ? ($payload['email'] ?? $payload['username'] ?? null) : null;

        $this->auditLogger->logLogin(null, AuditStatus::Failure, $request, is_string($identifier) ? $identifier : null);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        if ($event->getRequest()->getPathInfo() !== '/api/login_check') {
            return;
        }

        $payload = json_decode($event->getRequest()->getContent(), true);
        $identifier = is_array($payload) ? ($payload['email'] ?? $payload['username'] ?? null) : null;

        $this->auditLogger->logLogin(null, AuditStatus::Failure, $event->getRequest(), is_string($identifier) ? $identifier : null);
    }
}
