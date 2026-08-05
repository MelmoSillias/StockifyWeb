<?php

namespace App\Tests\Integration;

use App\IdentityAccess\Security\IdentityAssertionValidator;
use App\Tests\ApiTestCase;
use App\Tests\Support\IdentityAssertionTestHelper;

final class IdentityAssertionValidatorTest extends ApiTestCase
{
    public function testValidAssertionIsAccepted(): void
    {
        IdentityAssertionTestHelper::ensureKeyPair();
        self::bootKernel(['environment' => 'test']);

        /** @var IdentityAssertionValidator $validator */
        $validator = static::getContainer()->get(IdentityAssertionValidator::class);
        $token = IdentityAssertionTestHelper::createAssertion(
            '00000000-0000-4000-8000-000000000099',
            'owner@test.local',
        );

        $claims = $validator->validate($token);
        self::assertSame('00000000-0000-4000-8000-000000000099', $claims->subject);
        self::assertSame('owner@test.local', $claims->email);
    }

    public function testWrongAudienceIsRejected(): void
    {
        IdentityAssertionTestHelper::ensureKeyPair();
        self::bootKernel(['environment' => 'test']);

        /** @var IdentityAssertionValidator $validator */
        $validator = static::getContainer()->get(IdentityAssertionValidator::class);
        $token = IdentityAssertionTestHelper::createAssertion(
            '00000000-0000-4000-8000-000000000099',
            'owner@test.local',
            [],
            'other-app',
        );

        $this->expectException(\InvalidArgumentException::class);
        $validator->validate($token);
    }
}
