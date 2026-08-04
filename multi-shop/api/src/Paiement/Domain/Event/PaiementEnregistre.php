<?php

namespace App\Paiement\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class PaiementEnregistre implements DomainEventInterface
{
    public function __construct(
        private Uuid $paiementId,
        private Uuid $modeDePaiementId,
        private ?Uuid $factureId,
        private ?Uuid $commandeId,
        private string $amount,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function paiementId(): Uuid
    {
        return $this->paiementId;
    }

    public function modeDePaiementId(): Uuid
    {
        return $this->modeDePaiementId;
    }

    public function factureId(): ?Uuid
    {
        return $this->factureId;
    }

    public function commandeId(): ?Uuid
    {
        return $this->commandeId;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
