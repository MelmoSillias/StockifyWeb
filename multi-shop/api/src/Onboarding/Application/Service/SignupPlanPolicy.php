<?php

namespace App\Onboarding\Application\Service;

final class SignupPlanPolicy
{
    public const DEFAULT_PLAN_CODE = 'starter';

    public function resolvePlanCode(?string $requestedPlanCode): string
    {
        // Payment integration will replace this policy to honour paid plan selection.
        return self::DEFAULT_PLAN_CODE;
    }
}
