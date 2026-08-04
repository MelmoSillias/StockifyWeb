<?php

namespace App\IdentityAccess\Domain\Entity;

use App\AccessAudit\Domain\Entity\UserPermission;
use App\AccessAudit\Domain\Entity\UserRole;
use App\AccessAudit\Domain\PermissionCatalog;
use App\IdentityAccess\Domain\Enum\UserStatus;
use App\IdentityAccess\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineUserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'uniq_user_shop_username', columns: ['shop_id', 'username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 50)]
    private string $username;

    #[ORM\Column]
    private string $passwordHash;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(enumType: UserStatus::class)]
    private UserStatus $status = UserStatus::Pending;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $shopId = null;

    #[ORM\Column]
    private bool $isPlatformOwner = false;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $tenantAccountId = null;

    #[ORM\Column]
    private bool $mustChangePassword = false;

    /** @var Collection<int, UserRole> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserRole::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userRoles;

    /** @var Collection<int, UserPermission> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserPermission::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userPermissions;

    public function __construct(
        string $email,
        string $username,
        string $passwordHash,
        string $firstName,
        string $lastName,
    ) {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->email = strtolower($email);
        $this->username = strtolower($username);
        $this->passwordHash = $passwordHash;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->userRoles = new ArrayCollection();
        $this->userPermissions = new ArrayCollection();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = strtolower($email);
        $this->touch();
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = strtolower($username);
        $this->touch();
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
        $this->touch();
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->touch();
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
        $this->touch();
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
        $this->touch();
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function activate(): void
    {
        $this->status = UserStatus::Active;
        $this->emailVerifiedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function recordLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function eraseCredentials(): void
    {
    }

    /** @return Collection<int, UserRole> */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    /** @return Collection<int, UserPermission> */
    public function getUserPermissions(): Collection
    {
        return $this->userPermissions;
    }

    public function suspend(): void
    {
        $this->status = UserStatus::Suspended;
        $this->touch();
    }

    /**
     * @param list<string> $roleCodes
     */
    public function syncSymfonyRoles(array $roleCodes): void
    {
        $symfonyRoles = array_map(
            static fn (string $code): string => PermissionCatalog::symfonyRole($code),
            $roleCodes,
        );
        $this->roles = array_values(array_unique($symfonyRoles));
        $this->touch();
    }

    public function getShopId(): ?Uuid
    {
        return $this->shopId;
    }

    public function assignToShop(Uuid $shopId): void
    {
        if ($this->isPlatformOwner) {
            throw new \DomainException('Platform owner cannot be assigned to a shop.');
        }

        $this->shopId = $shopId;
        $this->touch();
    }

    public function isPlatformOwner(): bool
    {
        return $this->isPlatformOwner;
    }

    public function promoteToPlatformOwner(): void
    {
        $this->isPlatformOwner = true;
        $this->shopId = null;
        $roles = $this->roles;
        if (!in_array('ROLE_PLATFORM_OWNER', $roles, true)) {
            $roles[] = 'ROLE_PLATFORM_OWNER';
            $this->roles = $roles;
        }
        $this->touch();
    }

    public function belongsToShop(Uuid $shopId): bool
    {
        return null !== $this->shopId && $this->shopId->equals($shopId);
    }

    public function getTenantAccountId(): ?Uuid
    {
        return $this->tenantAccountId;
    }

    public function assignToTenantAccount(Uuid $tenantAccountId): void
    {
        if ($this->isPlatformOwner) {
            throw new \DomainException('Platform owner cannot be assigned to a tenant account.');
        }

        $this->tenantAccountId = $tenantAccountId;
        $this->touch();
    }

    public function mustChangePassword(): bool
    {
        return $this->mustChangePassword;
    }

    public function requirePasswordChange(): void
    {
        $this->mustChangePassword = true;
        $this->touch();
    }

    public function clearPasswordChangeRequirement(): void
    {
        $this->mustChangePassword = false;
        $this->touch();
    }
}
