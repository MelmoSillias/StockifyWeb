<?php

namespace App\IdentityAccess\Application\Service;

use App\IdentityAccess\Domain\Entity\RefreshToken;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\RefreshTokenRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class RefreshTokenService
{
    public const COOKIE_NAME = 'stockify_refresh_token';
    private const TTL_DAYS = 30;

    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly bool $cookieSecure,
    ) {
    }

    /**
     * @return array{access_token: string, refresh_token: string, cookie: Cookie}
     */
    public function createTokenPair(User $user, ?Request $request = null): array
    {
        $plainRefresh = bin2hex(random_bytes(32));
        $refreshToken = new RefreshToken(
            $user,
            hash('sha256', $plainRefresh),
            new \DateTimeImmutable(sprintf('+%d days', self::TTL_DAYS)),
        );
        $this->refreshTokenRepository->save($refreshToken);

        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue($plainRefresh)
            ->withHttpOnly(true)
            ->withSecure($this->cookieSecure)
            ->withSameSite('lax')
            ->withPath('/api')
            ->withExpires(new \DateTimeImmutable(sprintf('+%d days', self::TTL_DAYS)));

        return [
            'access_token' => $this->jwtManager->create($user),
            'refresh_token' => $plainRefresh,
            'cookie' => $cookie,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string, cookie: Cookie}
     */
    public function refresh(string $plainRefreshToken, ?Request $request = null): array
    {
        $token = $this->refreshTokenRepository->findValidByHash(hash('sha256', $plainRefreshToken));
        if (null === $token) {
            throw new \InvalidArgumentException('Invalid refresh token.');
        }

        $user = $token->getUser();
        $token->revoke();
        $this->refreshTokenRepository->save($token);

        return $this->createTokenPair($user, $request);
    }

    public function revoke(string $plainRefreshToken): void
    {
        $token = $this->refreshTokenRepository->findValidByHash(hash('sha256', $plainRefreshToken));
        if (null === $token) {
            return;
        }

        $token->revoke();
        $this->refreshTokenRepository->save($token);
    }

    public static function extractRefreshTokenFromRequest(Request $request): ?string
    {
        $content = $request->getContent();
        if ('' !== $content) {
            $data = json_decode($content, true);
            if (is_array($data) && isset($data['refresh_token']) && is_string($data['refresh_token']) && '' !== trim($data['refresh_token'])) {
                return trim($data['refresh_token']);
            }
        }

        return $request->cookies->get(self::COOKIE_NAME);
    }
}
