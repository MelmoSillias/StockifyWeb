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
        $payload = $this->decodeJsonResponse($response->getContent(false), $statusCode);

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
            'timeout' => 60,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $this->decodeJsonResponse($response->getContent(false), $statusCode);

        if ($statusCode >= 400) {
            $message = is_array($body) ? (string) ($body['error'] ?? $body['message'] ?? 'Signup failed') : 'Signup failed';

            throw new ControlPlaneException($message, $statusCode);
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function completeSignup(array $payload): array
    {
        $baseUrl = $this->requireBaseUrl();

        $response = $this->httpClient->request('POST', rtrim($baseUrl, '/').'/api/public/signup/complete', [
            'json' => $payload,
            'timeout' => 30,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $this->decodeJsonResponse($response->getContent(false), $statusCode);

        if ($statusCode >= 400) {
            $message = is_array($body) ? (string) ($body['error'] ?? $body['message'] ?? 'Signup completion failed') : 'Signup completion failed';

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

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(string $content, int $statusCode): array
    {
        if ('' === trim($content)) {
            if ($statusCode >= 400) {
                throw new ControlPlaneException(
                    sprintf('Control plane returned an empty error response (HTTP %d).', $statusCode),
                    $statusCode,
                );
            }

            return [];
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ControlPlaneException(
                sprintf('Invalid JSON response from control plane (HTTP %d).', $statusCode),
                $statusCode >= 400 ? $statusCode : 502,
            );
        }

        return is_array($decoded) ? $decoded : [];
    }
}
