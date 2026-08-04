<?php

namespace App\Commerce\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class VenteRealisee implements DomainEventInterface
{
    /**
     * @param list<OperationLine>                             $lines
     * @param array{client_id: string|null, anonymous_info: string|null} $acheteur
     */
    public function __construct(
        private Uuid $venteId,
        private string $reference,
        private array $lines,
        private string $totalAmount,
        private array $acheteur,
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

    public function totalAmount(): string
    {
        return $this->totalAmount;
    }

    /** @return array{client_id: string|null, anonymous_info: string|null} */
    public function acheteur(): array
    {
        return $this->acheteur;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
