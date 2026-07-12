<?php

namespace App\Tenancy\Domain\Entity;

use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use App\Tenancy\Domain\Enum\ShopStatus;
use App\Tenancy\Infrastructure\Persistence\Doctrine\DoctrineShopRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineShopRepository::class)]
#[ORM\Table(name: 'shops')]
#[ORM\UniqueConstraint(name: 'uniq_shop_slug_account', columns: ['account_id', 'slug'])]
class Shop
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'shops')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $slug;

    #[ORM\Column(enumType: ShopStatus::class)]
    private ShopStatus $status = ShopStatus::Active;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    /** @var Collection<int, ShopMember> */
    #[ORM\OneToMany(targetEntity: ShopMember::class, mappedBy: 'shop', cascade: ['persist'])]
    private Collection $members;

    public function __construct(Account $account, string $name, string $slug)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->account = $account;
        $this->name = $name;
        $this->slug = $slug;
        $this->members = new ArrayCollection();
        $account->addShop($this);
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getStatus(): ShopStatus
    {
        return $this->status;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
}
