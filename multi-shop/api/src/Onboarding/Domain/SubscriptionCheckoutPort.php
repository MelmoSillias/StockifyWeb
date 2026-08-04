<?php

namespace App\Onboarding\Domain;

/**
 * Future payment gateway integration point.
 */
interface SubscriptionCheckoutPort
{
    /**
     * @return array<string, mixed>
     */
    public function createCheckoutSession(string $accountSlug, string $planCode): array;
}
