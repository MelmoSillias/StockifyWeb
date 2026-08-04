<?php

namespace App\Facturation\Domain\Entity;

use App\Facturation\Infrastructure\Persistence\Doctrine\DoctrineFactureRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A legally binding invoice. Strictly immutable once issued: it is never
 * modified nor deleted. Corrections are made through an Avoir (credit note).
 */
#[ORM\Entity(repositoryClass: DoctrineFactureRepository::class)]
#[ORM\Table(name: 'factures')]
class Facture
{
    use UuidEntityTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $numero;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $venteId = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $commandeId = null;

    #[ORM\Column(length: 30)]
    private string $sourceReference;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $clientId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $anonymousInfo = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column]
    private \DateTimeImmutable $issuedAt;

    #[ORM\Column(options: ['default' => false])]
    private bool $isCreance = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isCreanceFinalized = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $creditClosedAt = null;

    /** @var Collection<int, FactureLine> */
    #[ORM\OneToMany(targetEntity: FactureLine::class, mappedBy: 'facture', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    private function __construct(string $sourceReference)
    {
        $this->initializeUuid();
        $this->numero = 'FCT-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->sourceReference = $sourceReference;
        $this->issuedAt = new \DateTimeImmutable();
        $this->lines = new ArrayCollection();
    }

    /**
     * @param array{client_id: string|null, anonymous_info: string|null} $acheteur
     */
    public static function forVente(Uuid $venteId, string $reference, array $acheteur): self
    {
        $facture = new self($reference);
        $facture->venteId = $venteId;
        $facture->applyAcheteur($acheteur);

        return $facture;
    }

    /**
     * @param array{client_id: string|null, anonymous_info: string|null} $acheteur
     */
    public static function forCommande(Uuid $commandeId, string $reference, array $acheteur): self
    {
        $facture = new self($reference);
        $facture->commandeId = $commandeId;
        $facture->applyAcheteur($acheteur);

        return $facture;
    }

    public function addLine(Uuid $variantId, string $label, string $quantity, string $unitPrice, string $lineTotal): void
    {
        $this->lines->add(new FactureLine($this, $variantId, $label, $quantity, $unitPrice, $lineTotal));
        $this->totalAmount = bcadd($this->totalAmount, $lineTotal, 2);
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getVenteId(): ?Uuid
    {
        return $this->venteId;
    }

    public function getCommandeId(): ?Uuid
    {
        return $this->commandeId;
    }

    public function getSourceReference(): string
    {
        return $this->sourceReference;
    }

    public function getClientId(): ?Uuid
    {
        return $this->clientId;
    }

    public function getAnonymousInfo(): ?string
    {
        return $this->anonymousInfo;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    /** @return Collection<int, FactureLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function isCreance(): bool
    {
        return $this->isCreance;
    }

    public function getCreditClosedAt(): ?\DateTimeImmutable
    {
        return $this->creditClosedAt;
    }

    public function isCreanceStatusFinalized(): bool
    {
        return $this->isCreanceFinalized;
    }

    public function finalizeCreanceStatus(bool $isCreance): void
    {
        if ($this->isCreanceFinalized) {
            throw new \DomainException('Credit status is already finalized for this invoice.');
        }

        $this->isCreance = $isCreance;
        $this->isCreanceFinalized = true;
    }

    public function closeCredit(\DateTimeImmutable $at): void
    {
        if (!$this->isCreance || null !== $this->creditClosedAt) {
            return;
        }

        $this->creditClosedAt = $at;
    }

    public function reopenCredit(): void
    {
        if (!$this->isCreance) {
            return;
        }

        $this->creditClosedAt = null;
    }

    /**
     * @param array{client_id: string|null, anonymous_info: string|null} $acheteur
     */
    private function applyAcheteur(array $acheteur): void
    {
        $this->clientId = !empty($acheteur['client_id']) ? Uuid::fromString($acheteur['client_id']) : null;
        $this->anonymousInfo = $acheteur['anonymous_info'] ?? null;
    }
}
