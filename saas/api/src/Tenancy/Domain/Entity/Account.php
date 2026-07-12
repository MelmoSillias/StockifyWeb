<?php

namespace App\Tenancy\Domain\Entity;

use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use App\Tenancy\Domain\Enum\AccountStatus;
use App\Tenancy\Infrastructure\Persistence\Doctrine\DoctrineAccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineAccountRepository::class)]
#[ORM\Table(name: 'accounts')]
#[ORM\UniqueConstraint(name: 'uniq_account_slug', fields: ['slug'])]
class Account
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $slug;

    #[ORM\Column(enumType: AccountStatus::class)]
    private AccountStatus $status = AccountStatus::Trial;

    #[ORM\Column(length: 3)]
    private string $defaultCurrency;

    #[ORM\Column(length: 50)]
    private string $timezone;

    /** @var Collection<int, Shop> */
    #[ORM\OneToMany(targetEntity: Shop::class, mappedBy: 'account', cascade: ['persist'])]
    private Collection $shops;

    /** @var Collection<int, AccountMember> */
    #[ORM\OneToMany(targetEntity: AccountMember::class, mappedBy: 'account', cascade: ['persist'])]
    private Collection $members;

    public function __construct(string $name, string $slug, string $defaultCurrency = 'XOF', string $timezone = 'Africa/Abidjan')
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->name = $name;
        $this->slug = $slug;
        $this->defaultCurrency = $defaultCurrency;
        $this->timezone = $timezone;
        $this->shops = new ArrayCollection();
        $this->members = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getStatus(): AccountStatus
    {
        return $this->status;
    }

    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /** @return Collection<int, Shop> */
    public function getShops(): Collection
    {
        return $this->shops;
    }

    public function addShop(Shop $shop): void
    {
        if (!$this->shops->contains($shop)) {
            $this->shops->add($shop);
        }
    }
}
