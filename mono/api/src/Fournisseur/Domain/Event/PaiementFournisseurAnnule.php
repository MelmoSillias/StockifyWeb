<?php

namespace App\Fournisseur\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class PaiementFournisseurAnnule implements DomainEventInterface
{
    public function __construct(
        private Uuid $paiementFournisseurId,
        private Uuid $detteFournisseurId,
        private string $amount,
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

    public function amount(): string
    {
        return $this->amount;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
