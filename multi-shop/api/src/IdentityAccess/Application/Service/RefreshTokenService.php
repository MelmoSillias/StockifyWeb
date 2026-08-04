<?php

namespace App\IdentityAccess\Application\Service;

use App\IdentityAccess\Domain\Entity\RefreshToken;
use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Repository\RefreshTokenRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class RefreshTokenService
{
    private const TTL_DAYS = 30;

    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function createTokenPair(User $user): array
    {
        $plainRefresh = bin2hex(random_bytes(32));
        $refreshToken = new RefreshToken(
            $user,
            hash('sha256', $plainRefresh),
            new \DateTimeImmutable(sprintf('+%d days', self::TTL_DAYS)),
        );
        $this->refreshTokenRepository->save($refreshToken);

        return [
            'access_token' => $this->jwtManager->create($user),
            'refresh_token' => $plainRefresh,
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string}
     */
    public function refresh(string $plainRefreshToken): array
    {
        $token = $this->refreshTokenRepository->findValidByHash(hash('sha256', $plainRefreshToken));
        if (null === $token) {
            throw new \InvalidArgumentException('Invalid refresh token.');
        }

        $user = $token->getUser();
        $token->revoke();
        $this->refreshTokenRepository->save($token);

        return $this->createTokenPair($user);
    }
}
