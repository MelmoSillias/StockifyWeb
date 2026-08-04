<?php

namespace App\Onboarding\Application\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ControlPlaneClient implements ControlPlaneGatewayInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $controlPlaneBaseUrl = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchPublicPlans(string $applicationSlug = 'stockify'): array
    {
        $baseUrl = $this->requireBaseUrl();

        $response = $this->httpClient->request('GET', rtrim($baseUrl, '/').'/api/public/plans', [
            'query' => ['application' => $applicationSlug],
            'timeout' => 10,
        ]);

        $statusCode = $response->getStatusCode();
        $payload = $response->toArray(false);

        if ($statusCode >= 400) {
            $message = is_array($payload) ? (string) ($payload['error'] ?? $payload['message'] ?? 'Control plane error') : 'Control plane error';

            throw new ControlPlaneException($message, $statusCode);
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function signup(array $payload): array
    {
        $baseUrl = $this->requireBaseUrl();

        $response = $this->httpClient->request('POST', rtrim($baseUrl, '/').'/api/public/signup', [
            'json' => $payload,
            'timeout' => 30,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $response->toArray(false);

        if ($statusCode >= 400) {
            $message = is_array($body) ? (string) ($body['error'] ?? $body['message'] ?? 'Signup failed') : 'Signup failed';

            throw new ControlPlaneException($message, $statusCode);
        }

        return $body;
    }

    private function requireBaseUrl(): string
    {
        $baseUrl = trim((string) ($this->controlPlaneBaseUrl ?? ''));

        if ('' === $baseUrl) {
            throw new ControlPlaneException('Control plane is not configured.', 503);
        }

        return $baseUrl;
    }
}
