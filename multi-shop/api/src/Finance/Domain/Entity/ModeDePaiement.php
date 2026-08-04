<?php

namespace App\Finance\Domain\Entity;

use App\Finance\Infrastructure\Persistence\Doctrine\DoctrineModeDePaiementRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineModeDePaiementRepository::class)]
#[ORM\Table(name: 'modes_de_paiement')]
#[ORM\UniqueConstraint(name: 'uniq_mode_de_paiement_code', columns: ['code'])]
class ModeDePaiement implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 50)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(type: 'uuid')]
    private Uuid $compteId;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $generatesTransaction = true;

    public function __construct(string $code, string $label, Uuid $compteId, bool $generatesTransaction = true)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->code = $code;
        $this->label = $label;
        $this->compteId = $compteId;
        $this->generatesTransaction = $generatesTransaction;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
        $this->touch();
    }

    public function getCompteId(): Uuid
    {
        return $this->compteId;
    }

    public function setCompteId(Uuid $compteId): void
    {
        $this->compteId = $compteId;
        $this->touch();
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->touch();
    }

    public function generatesTransaction(): bool
    {
        return $this->generatesTransaction;
    }

    public function setGeneratesTransaction(bool $generatesTransaction): void
    {
        $this->generatesTransaction = $generatesTransaction;
        $this->touch();
    }
}
