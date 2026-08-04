<?php

namespace App\Integration\Application\Service;

use App\Integration\Domain\Entity\IntegrationRequestLog;
use App\Integration\Domain\Repository\IntegrationRequestLogRepositoryInterface;

final class IntegrationRequestLogger
{
    public function __construct(
        private readonly IntegrationRequestLogRepositoryInterface $requestLogRepository,
    ) {
    }

    public function start(
        string $method,
        string $path,
        ?string $externalAccountId = null,
        ?string $idempotencyKey = null,
        ?array $requestSummary = null,
    ): IntegrationRequestLog {
        $log = new IntegrationRequestLog(
            method: $method,
            path: $path,
            externalAccountId: $externalAccountId,
            idempotencyKey: $idempotencyKey,
            requestSummary: $requestSummary,
        );
        $this->requestLogRepository->save($log);

        return $log;
    }

    /** @param array<string, mixed>|null $responseBody */
    public function complete(IntegrationRequestLog $log, int $status, ?array $responseBody, int $durationMs): void
    {
        $log->recordResponse($status, $responseBody, $durationMs);
        $this->requestLogRepository->save($log);
    }

    public function findIdempotentResponse(string $idempotencyKey): ?IntegrationRequestLog
    {
        $log = $this->requestLogRepository->findByIdempotencyKey($idempotencyKey);
        if (null === $log || null === $log->getResponseBody() || null === $log->getResponseStatus()) {
            return null;
        }

        return $log;
    }
}
