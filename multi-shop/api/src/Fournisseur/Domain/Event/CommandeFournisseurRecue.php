<?php

namespace App\Fournisseur\Domain\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CommandeFournisseurRecue implements DomainEventInterface
{
    public function __construct(
        private Uuid $commandeFournisseurId,
        private Uuid $fournisseurId,
        private string $totalAmount,
        private string $paidAmount,
        private ?Uuid $modeDePaiementId,
        private ?\DateTimeImmutable $paidAt = null,
    ) {
    }

    public function commandeFournisseurId(): Uuid
    {
        return $this->commandeFournisseurId;
    }

    public function fournisseurId(): Uuid
    {
        return $this->fournisseurId;
    }

    public function totalAmount(): string
    {
        return $this->totalAmount;
    }

    public function paidAmount(): string
    {
        return $this->paidAmount;
    }

    public function modeDePaiementId(): ?Uuid
    {
        return $this->modeDePaiementId;
    }

    public function paidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
