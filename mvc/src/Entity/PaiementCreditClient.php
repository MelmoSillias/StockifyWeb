<?php

namespace App\Entity;

use App\Repository\PaiementCreditClientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementCreditClientRepository::class)]
class PaiementCreditClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CreditClient::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?CreditClient $credit = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    #[ORM\OneToOne(targetEntity: PaiementCreditClient::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?PaiementCreditClient $paiementCreditClient = null;

    public function __construct()
    {
        $this->montant = 0;
        $this->date = new \DateTime();
    }

    public function setPaiementCreditClient(?PaiementCreditClient $paiementCreditClient): static
    {
        $this->paiementCreditClient = $paiementCreditClient;
        return $this;
    }
    public function getPaiementCreditClient(): ?PaiementCreditClient
    {
        return $this->paiementCreditClient;
    }
    

    // Getters and setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCredit(): ?CreditClient
    {
        return $this->credit;
    }

    public function setCredit(?CreditClient $credit): static
    {
        $this->credit = $credit;
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