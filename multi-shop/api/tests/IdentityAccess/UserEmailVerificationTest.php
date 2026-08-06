<?php

namespace App\Tests\IdentityAccess;

use App\IdentityAccess\Domain\Entity\User;
use App\IdentityAccess\Domain\Enum\UserStatus;
use PHPUnit\Framework\TestCase;

final class UserEmailVerificationTest extends TestCase
{
    public function testActivateDoesNotSetEmailVerifiedAt(): void
    {
        $user = new User('owner@test.local', 'owner', 'hash', 'Owner', 'Test');
        $user->activate();

        self::assertSame(UserStatus::Active, $user->getStatus());
        self::assertFalse($user->isEmailVerified());
        self::assertNull($user->getEmailVerifiedAt());
    }

    public function testSyncEmailVerificationMirrorsControlPlaneState(): void
    {
        $user = new User('owner@test.local', 'owner', 'hash', 'Owner', 'Test');
        $verifiedAt = new \DateTimeImmutable('2026-08-06T12:00:00+00:00');

        $user->syncEmailVerification($verifiedAt);

        self::assertTrue($user->isEmailVerified());
        self::assertSame($verifiedAt, $user->getEmailVerifiedAt());
    }
}
