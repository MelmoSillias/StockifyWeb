<?php

namespace App\Shop\Domain\Entity;

use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use App\Shop\Domain\Enum\ShopStatus;
use App\Shop\Infrastructure\Persistence\Doctrine\DoctrineShopRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineShopRepository::class)]
#[ORM\Table(name: 'shops')]
#[ORM\UniqueConstraint(name: 'uniq_shop_slug', columns: ['slug'])]
class Shop
{
    use UuidEntityTrait;
    use TimestampableTrait;

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

    #[ORM\Column(name: 'tenant_account_id', type: 'uuid', nullable: true)]
    private ?Uuid $tenantAccountId = null;

    public function __construct(string $name, string $slug)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->name = trim($name);
        $this->slug = strtolower(trim($slug));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
        $this->touch();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = strtolower(trim($slug));
        $this->touch();
    }

    public function getStatus(): ShopStatus
    {
        return $this->status;
    }

    public function activate(): void
    {
        $this->status = ShopStatus::Active;
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->status = ShopStatus::Inactive;
        $this->touch();
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
        $this->touch();
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
        $this->touch();
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->touch();
    }

    public function getTenantAccountId(): ?Uuid
    {
        return $this->tenantAccountId;
    }

    public function setTenantAccountId(?Uuid $tenantAccountId): void
    {
        $this->tenantAccountId = $tenantAccountId;
        $this->touch();
    }
}
