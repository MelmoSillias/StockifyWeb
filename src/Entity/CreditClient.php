<?php

namespace App\Entity;

use App\Repository\CreditClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CreditClientRepository::class)]
class CreditClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Vente::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vente $vente = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $client_nom = null;

    #[ORM\Column]
    private ?float $montant_total = null;

    #[ORM\Column]
    private ?float $montant_restant = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\OneToMany(targetEntity: PaiementCreditClient::class, mappedBy :"credit", orphanRemoval : true)]
    #[ORM\JoinColumn(nullable: true)]
    private Collection $paiementCreditClients;

    public function __construct()
    {
        $this->montant_total = 0;
        $this->montant_restant = 0;
        $this->statut = 'En cours';
        $this->client_nom = 'N/A'; 
        $this->paiementCreditClients = new ArrayCollection();
    }

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
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

    public function getClientNom(): ?string
    {
        return $this->client_nom;
    }

    public function setClientNom(?string $client_nom): static
    {
        $this->client_nom = $client_nom;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getPaiementCreditClients(): Collection
    {
        return $this->paiementCreditClients;
    }
    public function addPaiementCreditClient(PaiementCreditClient $paiementCreditClient): static
    {
        if (!$this->paiementCreditClients->contains($paiementCreditClient)) {
            $this->paiementCreditClients->add($paiementCreditClient);
            $paiementCreditClient->setCredit($this);
        }

        return $this;
    }
    public function removePaiementCreditClient(PaiementCreditClient $paiementCreditClient): static
    {
        if ($this->paiementCreditClients->removeElement($paiementCreditClient)) {
            // set the owning side to null (unless already changed)
            if ($paiementCreditClient->getCredit() === $this) {
                $paiementCreditClient->setCredit(null);
            }
        }

        return $this;
    }
}