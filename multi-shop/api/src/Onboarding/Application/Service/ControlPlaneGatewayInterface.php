<?php

namespace App\Onboarding\Application\Service;

interface ControlPlaneGatewayInterface
{
    /**
     * @return array<string, mixed>
     */
    public function fetchPublicPlans(string $applicationSlug = 'stockify'): array;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function signup(array $payload): array;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function completeSignup(array $payload): array;
}
