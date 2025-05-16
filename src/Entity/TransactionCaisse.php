<?php

namespace App\Entity;

use App\Repository\TransactionCaisseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionCaisseRepository::class)]
class TransactionCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelle = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $motif = null;

    #[ORM\ManyToOne(targetEntity: Vente::class)]
    private ?Vente $vente = null;

    #[ORM\OneToOne(targetEntity: PaiementCreditClient::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private ?PaiementCreditClient $paiement_credit = null;

    #[ORM\OneToOne(targetEntity: PaiementCreanceFournisseur::class)]
    private ?PaiementCreanceFournisseur $paiement_fournisseur = null;

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getVente(): ?Vente
    {
        return $this->vente;
    }

    public function setVente(?Vente $vente): static
    {
        $this->vente = $vente;
        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
        return $this;
    }
    

    public function getPaiementCredit(): ?PaiementCreditClient
    {
        return $this->paiement_credit;
    }

    public function setPaiementCredit(?PaiementCreditClient $paiement_credit): static
    {
        $this->paiement_credit = $paiement_credit;
        return $this;
    }

    public function getPaiementFournisseur(): ?PaiementCreanceFournisseur
    {
        return $this->paiement_fournisseur;
    }

    public function setPaiementFournisseur(?PaiementCreanceFournisseur $paiement_fournisseur): static
    {
        $this->paiement_fournisseur = $paiement_fournisseur;
        return $this;
    }
}