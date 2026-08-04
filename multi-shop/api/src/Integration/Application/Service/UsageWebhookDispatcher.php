<?php

namespace App\Integration\Application\Service;

final class UsageWebhookDispatcher
{
    public function __construct(
        private readonly ?string $controlPlaneBaseUrl = null,
        private readonly ?string $webhookSecret = null,
        private readonly bool $enabled = false,
    ) {
    }

    /**
     * @param array<string, int> $metrics
     */
    public function dispatchUsageUpdated(string $externalAccountId, array $metrics): void
    {
        $baseUrl = (string) ($this->controlPlaneBaseUrl ?? '');
        if (!$this->enabled || '' === trim($baseUrl)) {
            return;
        }

        $payload = json_encode([
            'event' => 'usage.updated',
            'external_account_id' => $externalAccountId,
            'application_slug' => 'stockify',
            'metrics' => $metrics,
            'occurred_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);

        $headers = "Content-Type: application/json\r\n";
        $secret = (string) ($this->webhookSecret ?? '');
        if ('' !== trim($secret)) {
            $headers .= 'X-Integration-Signature: '.hash_hmac('sha256', $payload, $secret)."\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => $payload,
                'ignore_errors' => true,
                'timeout' => 5,
            ],
        ]);

        @file_get_contents(
            rtrim($baseUrl, '/').'/api/integration/webhooks/usage',
            false,
            $context,
        );
    }
}
