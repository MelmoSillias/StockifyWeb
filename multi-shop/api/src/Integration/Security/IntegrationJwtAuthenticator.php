<?php

namespace App\Integration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class IntegrationJwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly IntegrationJwtValidator $jwtValidator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if (str_starts_with($request->getPathInfo(), '/integration/v1/health')) {
            return false;
        }

        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        if (!$this->jwtValidator->isEnabled()) {
            throw new CustomUserMessageAuthenticationException('Integration API is disabled.');
        }

        $authorization = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($authorization, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('Invalid Authorization header.');
        }

        $token = trim(substr($authorization, 7));
        if ('' === $token) {
            throw new CustomUserMessageAuthenticationException('Missing bearer token.');
        }

        try {
            $claims = $this->jwtValidator->validate($token);
        } catch (\InvalidArgumentException $exception) {
            throw new CustomUserMessageAuthenticationException('Invalid integration token.');
        } catch (\RuntimeException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }

        $identifier = '' !== $claims->subject ? $claims->subject : 'integration-control-plane';

        return new SelfValidatingPassport(
            new UserBadge($identifier, fn () => new IntegrationUser($identifier, $claims->toRoles())),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
