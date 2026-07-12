<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    private ?string $categorie = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $stock_actuel = null;

    #[ORM\Column]
    private ?float $pme = null;

    #[ORM\Column(nullable: true)]
    private ?int $seuil_alerte = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix_de_vente = null;

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
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

    public function getStockActuel(): ?int
    {
        return $this->stock_actuel;
    }

    public function setStockActuel(int $stock_actuel): static
    {
        $this->stock_actuel = $stock_actuel;
        return $this;
    }

    public function getPme(): ?float
    {
        return $this->pme;
    }

    public function setPme(float $pme): static
    {
        $this->pme = $pme;
        return $this;
    }

    public function getSeuilAlerte(): ?int
    {
        return $this->seuil_alerte;
    }

    public function setSeuilAlerte(?int $seuil_alerte): static
    {
        $this->seuil_alerte = $seuil_alerte;
        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;
        return $this;
    }

    public function getPrixDeVente(): ?string
    {
        return $this->prix_de_vente;
    }

    public function setPrixDeVente(string $prix_de_vente): static
    {
        $this->prix_de_vente = $prix_de_vente;

        return $this;
    }
}

