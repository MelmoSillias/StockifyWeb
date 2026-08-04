<?php

namespace App\Livraison\Domain\Event;

use App\Commerce\Domain\Event\OperationLine;
use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class BonDeLivraisonEnvoye implements DomainEventInterface
{
    /**
     * @param list<OperationLine> $lines
     */
    public function __construct(
        private Uuid $bonDeLivraisonId,
        private string $reference,
        private Uuid $commandeId,
        private string $commandeReference,
        private array $lines,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function bonDeLivraisonId(): Uuid
    {
        return $this->bonDeLivraisonId;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function commandeId(): Uuid
    {
        return $this->commandeId;
    }

    public function commandeReference(): string
    {
        return $this->commandeReference;
    }

    /** @return list<OperationLine> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
