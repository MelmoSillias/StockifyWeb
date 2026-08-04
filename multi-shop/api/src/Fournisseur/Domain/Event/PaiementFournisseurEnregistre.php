<?php

namespace App\Fournisseur\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class PaiementFournisseurEnregistre implements DomainEventInterface
{
    public function __construct(
        private Uuid $paiementFournisseurId,
        private Uuid $detteFournisseurId,
        private Uuid $modeDePaiementId,
        private string $amount,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function paiementFournisseurId(): Uuid
    {
        return $this->paiementFournisseurId;
    }

    public function detteFournisseurId(): Uuid
    {
        return $this->detteFournisseurId;
    }

    public function modeDePaiementId(): Uuid
    {
        return $this->modeDePaiementId;
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
