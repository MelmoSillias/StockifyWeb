<?php

namespace App\Entity;

use App\Repository\PaiementCreanceFournisseurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementCreanceFournisseurRepository::class)]
class PaiementCreanceFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CreanceFournisseur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?CreanceFournisseur $creance = null;

    #[ORM\Column]
    private ?float $montant_paye_devise = null;

    #[ORM\Column]
    private ?float $taux_applique = null;

    #[ORM\Column]
    private ?float $montant_en_caisse = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreance(): ?CreanceFournisseur
    {
        return $this->creance;
    }

    public function setCreance(?CreanceFournisseur $creance): static
    {
        $this->creance = $creance;
        return $this;
    }

    public function getMontantPayeDevise(): ?float
    {
        return $this->montant_paye_devise;
    }

    public function setMontantPayeDevise(float $montant_paye_devise): static
    {
        $this->montant_paye_devise = $montant_paye_devise;
        return $this;
    }

    public function getTauxApplique(): ?float
    {
        return $this->taux_applique;
    }

    public function setTauxApplique(float $taux_applique): static
    {
        $this->taux_applique = $taux_applique;
        return $this;
    }

    public function getMontantEnCaisse(): ?float
    {
        return $this->montant_en_caisse;
    }

    public function setMontantEnCaisse(float $montant_en_caisse): static
    {
        $this->montant_en_caisse = $montant_en_caisse;
        return $this;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}