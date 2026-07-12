<?php

namespace App\Entity;

use App\Repository\CreanceFournisseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CreanceFournisseurRepository::class)]
class CreanceFournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fournisseur_nom = null;

    #[ORM\Column(length: 3)]
    private ?string $devise = null;

    #[ORM\Column]
    private ?float $taux_change = null;

    #[ORM\Column]
    private ?float $montant_total = null;

    #[ORM\Column]
    private ?float $montant_restant = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\OneToMany(mappedBy: 'creance', targetEntity: PaiementCreanceFournisseur::class)]
    #[ORM\JoinColumn(nullable: true)]
    private Collection $paiement;

    public function __construct()
    {
        $this->montant_total = 0;
        $this->montant_restant = 0;
        $this->statut = 'En cours';
        $this->fournisseur_nom = 'N/A';  
        $this->paiement = new ArrayCollection();
    }

    public function addPaiement(PaiementCreanceFournisseur $paiement): static
    {
        if (!$this->paiement->contains($paiement)) {
            $this->paiement[] = $paiement;
            $paiement->setCreance($this);
        }
        return $this;
    }
    public function removePaiement(PaiementCreanceFournisseur $paiement): static
    {
        if ($this->paiement->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getCreance() === $this) {
                $paiement->setCreance(null);
            }
        }
        return $this;
    }
    public function getPaiements(): Collection
    {
        return $this->paiement;
    }

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFournisseurNom(): ?string
    {
        return $this->fournisseur_nom;
    }

    public function setFournisseurNom(string $fournisseur_nom): static
    {
        $this->fournisseur_nom = $fournisseur_nom;
        return $this;
    }

    public function getDevise(): ?string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): static
    {
        $this->devise = $devise;
        return $this;
    }

    public function getTauxChange(): ?float
    {
        return $this->taux_change;
    }

    public function setTauxChange(float $taux_change): static
    {
        $this->taux_change = $taux_change;
        return $this;
    }

    public function getMontantTotal(): ?float
    {
        return $this->montant_total;
    }

    public function setMontantTotal(float $montant_total): static
    {
        $this->montant_total = $montant_total;
        return $this;
    }

    public function getMontantRestant(): ?float
    {
        return $this->montant_restant;
    }

    public function setMontantRestant(float $montant_restant): static
    {
        $this->montant_restant = $montant_restant;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }
    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }
}