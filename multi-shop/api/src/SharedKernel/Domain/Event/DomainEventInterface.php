<?php

namespace App\SharedKernel\Domain\Event;

interface DomainEventInterface
{
    public function occurredAt(): \DateTimeImmutable;
}
