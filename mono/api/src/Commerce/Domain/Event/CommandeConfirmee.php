<?php

namespace App\Commerce\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CommandeConfirmee implements DomainEventInterface
{
    /**
     * @param list<OperationLine>                             $lines
     * @param array{client_id: string|null, anonymous_info: string|null} $acheteur
     */
    public function __construct(
        private Uuid $commandeId,
        private string $reference,
        private array $lines,
        private string $totalAmount,
        private array $acheteur,
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
