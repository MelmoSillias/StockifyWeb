<?php

namespace App\Onboarding\Application\Service;

final class ControlPlaneException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 502,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
