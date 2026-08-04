<?php

namespace App\Commerce\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CommandeAnnulee implements DomainEventInterface
{
    /**
     * @param list<OperationLine> $lines
     */
    public function __construct(
        private Uuid $commandeId,
        private string $reference,
        private array $lines,
        private bool $stockWasImpacted,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function commandeId(): Uuid
    {
        return $this->commandeId;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    /** @return list<OperationLine> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function stockWasImpacted(): bool
    {
        return $this->stockWasImpacted;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
