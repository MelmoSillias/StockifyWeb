<?php

namespace App\Impression\Application\Command\UpdatePrintSettings;

final readonly class UpdatePrintSettingsCommand
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public array $payload,
    ) {
    }
}
