<?php

namespace App\Onboarding\Application\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ControlPlaneClient implements ControlPlaneGatewayInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $controlPlaneBaseUrl = null,
        private readonly ?string $webhookSecret = null,
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

    public function exchangeIdentityToken(string $email, string $password, string $applicationSlug = 'stockify'): string
    {
        $baseUrl = $this->requireBaseUrl();
        $payload = json_encode([
            'email' => $email,
            'password' => $password,
            'application' => $applicationSlug,
        ], JSON_THROW_ON_ERROR);

        $body = $this->signedPost(rtrim($baseUrl, '/').'/api/identity/v1/token', $payload, 30);

        $assertion = (string) ($body['data']['assertion'] ?? '');
        if ('' === $assertion) {
            throw new ControlPlaneException('Control plane did not return an identity assertion.', 502);
        }

        return $assertion;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function submitQuoteRequest(array $payload): array
    {
        $baseUrl = $this->requireBaseUrl();

        $response = $this->httpClient->request('POST', rtrim($baseUrl, '/').'/api/public/quote-requests', [
            'json' => $payload,
            'timeout' => 15,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $this->decodeJsonResponse($response->getContent(false), $statusCode);

        if ($statusCode >= 400) {
            $message = is_array($body)
                ? (string) ($body['error'] ?? $body['message'] ?? 'Quote request failed')
                : 'Quote request failed';

            throw new ControlPlaneException($message, $statusCode);
        }

        return $body;
    }

    /**
     * @return array{features: list<string>, quotas: array<string, int|float>, updated_at: ?string}
     */
    public function pullEntitlements(string $externalAccountId, string $applicationSlug = 'stockify'): array
    {
        $baseUrl = $this->requireBaseUrl();
        $payload = json_encode([
            'application' => $applicationSlug,
        ], JSON_THROW_ON_ERROR);

        $path = '/api/integration/v1/accounts/'.rawurlencode($externalAccountId).'/entitlements/pull';
        $body = $this->signedPost(rtrim($baseUrl, '/').$path, $payload, 15);

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];
        $features = is_array($data['features'] ?? null) ? array_values(array_map('strval', $data['features'])) : [];
        $quotas = [];
        if (is_array($data['quotas'] ?? null)) {
            foreach ($data['quotas'] as $key => $value) {
                $quotas[(string) $key] = is_numeric($value) ? $value + 0 : 0;
            }
        }

        return [
            'features' => $features,
            'quotas' => $quotas,
            'updated_at' => isset($data['updated_at']) ? (string) $data['updated_at'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function signedPost(string $url, string $payload, float $timeout): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $secret = trim((string) ($this->webhookSecret ?? ''));
        if ('' === $secret) {
            throw new ControlPlaneException('INTEGRATION_WEBHOOK_SECRET is not configured.', 503);
        }

        $headers['X-Integration-Signature'] = hash_hmac('sha256', $payload, $secret);

        $response = $this->httpClient->request('POST', $url, [
            'headers' => $headers,
            'body' => $payload,
            'timeout' => $timeout,
        ]);

        $statusCode = $response->getStatusCode();
        $body = $this->decodeJsonResponse($response->getContent(false), $statusCode);

        if ($statusCode >= 400) {
            $message = is_array($body)
                ? (string) ($body['error'] ?? $body['message'] ?? 'Control plane request failed')
                : 'Control plane request failed';

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
