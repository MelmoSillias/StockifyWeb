<?php

namespace App\Entity;

use App\Repository\VenteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
class Vente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_client = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?float $total = null;

    #[ORM\Column]
    private ?float $montant_paye = null;

    #[ORM\Column]
    private ?float $reste = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'vente', targetEntity: DetailVente::class)]
    private iterable $detailsVente;

    #[ORM\Column]
    private ?float $benefice = null;

    public function __construct()
    {
        $this->detailsVente = [];
    }
    public function addDetailVente(DetailVente $detail): static
    {
        $this->detailsVente[] = $detail;
        $detail->setVente($this);
        return $this;
    }
    public function removeDetailVente(DetailVente $detail): static
    {
        if (($key = array_search($detail, $this->detailsVente, true)) !== false) {
            unset($this->detailsVente[$key]);
            $detail->setVente(null);
        }
        return $this;
    }
    public function getDetailsVente(): iterable
    {
        return $this->detailsVente;
    }
    

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

    public function getNomClient(): ?string
    {
        return $this->nom_client;
    }

    public function setNomClient(?string $nom_client): static
    {
        $this->nom_client = $nom_client;
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

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getMontantPaye(): ?float
    {
        return $this->montant_paye;
    }

    public function setMontantPaye(float $montant_paye): static
    {
        $this->montant_paye = $montant_paye;
        return $this;
    }

    public function getReste(): ?float
    {
        return $this->reste;
    }

    public function setReste(float $reste): static
    {
        $this->reste = $reste;
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

    public function getBenefice() : float
    {
        return $this->benefice;
    }

    public function setBenefice(float $benefice): static
    {
        $this->benefice = $benefice;
        return $this;
    }
}