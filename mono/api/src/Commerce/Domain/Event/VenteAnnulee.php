<?php

namespace App\Commerce\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class VenteAnnulee implements DomainEventInterface
{
    /**
     * @param list<OperationLine> $lines
     */
    public function __construct(
        private Uuid $venteId,
        private string $reference,
        private array $lines,
        private Uuid $factureId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function venteId(): Uuid
    {
        return $this->venteId;
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

    public function factureId(): Uuid
    {
        return $this->factureId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
