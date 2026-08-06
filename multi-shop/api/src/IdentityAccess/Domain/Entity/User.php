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
#[ORM\UniqueConstraint(name: 'uniq_user_identity_id', columns: ['identity_id'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use UuidEntityTrait;
    use TimestampableTrait;

    private static string $authIdentifierMode = 'email';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'legacy_email', length: 180, nullable: true)]
    private ?string $legacyEmail = null;

    #[ORM\Column(length: 50)]
    private string $username;

    #[ORM\Column]
    private string $passwordHash;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $phone = null;

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

    #[ORM\Column(name: 'identity_id', type: 'uuid', nullable: true)]
    private ?Uuid $identityId = null;

    #[ORM\Column]
    private bool $mustChangePassword = false;

    /** @var Collection<int, UserRole> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserRole::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userRoles;

    /** @var Collection<int, UserPermission> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserPermission::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userPermissions;

    /** @var Collection<int, UserShopMembership> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserShopMembership::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $shopMemberships;

    public function __construct(
        ?string $email,
        string $username,
        string $passwordHash,
        string $firstName,
        string $lastName,
    ) {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->email = self::normalizeEmail($email);
        $this->username = strtolower($username);
        $this->passwordHash = $passwordHash;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->userRoles = new ArrayCollection();
        $this->userPermissions = new ArrayCollection();
        $this->shopMemberships = new ArrayCollection();
    }

    public static function setAuthIdentifierMode(string $mode): void
    {
        self::$authIdentifierMode = $mode;
    }

    public static function getAuthIdentifierMode(): string
    {
        return self::$authIdentifierMode;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = self::normalizeEmail($email);
        $this->touch();
    }

    public function getLegacyEmail(): ?string
    {
        return $this->legacyEmail;
    }

    public function setLegacyEmail(?string $legacyEmail): void
    {
        $this->legacyEmail = self::normalizeEmail($legacyEmail);
        $this->touch();
    }

    /**
     * Moves a synthetic .local address into legacy_email and clears email.
     */
    public function nullifySyntheticEmail(): void
    {
        if (null === $this->email || !str_ends_with($this->email, '.local')) {
            return;
        }

        $this->legacyEmail = $this->email;
        $this->email = null;
        $this->touch();
    }

    public function getUserIdentifier(): string
    {
        if ('username' === self::$authIdentifierMode) {
            return $this->username;
        }

        return $this->email ?? $this->username;
    }

    private static function normalizeEmail(?string $email): ?string
    {
        if (null === $email) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return '' === $normalized ? null : $normalized;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        if (null === $phone) {
            $this->phone = null;
            $this->touch();

            return;
        }

        $normalized = trim($phone);
        $this->phone = '' === $normalized ? null : $normalized;
        $this->touch();
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function activate(): void
    {
        $this->status = UserStatus::Active;
        $this->touch();
    }

    public function isEmailVerified(): bool
    {
        return null !== $this->emailVerifiedAt;
    }

    public function syncEmailVerification(?\DateTimeImmutable $verifiedAt): void
    {
        $this->emailVerifiedAt = $verifiedAt;
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
        $this->addShopMembership($shopId, true);
        $this->touch();
    }

    /** @return Collection<int, UserShopMembership> */
    public function getShopMemberships(): Collection
    {
        return $this->shopMemberships;
    }

    /** @return list<Uuid> */
    public function getShopIds(): array
    {
        $ids = [];
        foreach ($this->shopMemberships as $membership) {
            $ids[$membership->getShopId()->toRfc4122()] = $membership->getShopId();
        }

        // The legacy column is still authoritative for users not yet backfilled.
        if (null !== $this->shopId) {
            $ids[$this->shopId->toRfc4122()] = $this->shopId;
        }

        return array_values($ids);
    }

    public function addShopMembership(Uuid $shopId, bool $primary = false): void
    {
        if ($this->isPlatformOwner) {
            throw new \DomainException('Platform owner cannot be a member of a shop.');
        }

        foreach ($this->shopMemberships as $membership) {
            if ($membership->getShopId()->equals($shopId)) {
                if ($primary) {
                    $this->makePrimary($membership);
                }

                return;
            }
        }

        $membership = new UserShopMembership($this, $shopId, $primary);
        $this->shopMemberships->add($membership);

        if ($primary) {
            $this->makePrimary($membership);
        }

        $this->touch();
    }

    public function removeShopMembership(Uuid $shopId): void
    {
        foreach ($this->shopMemberships as $membership) {
            if (!$membership->getShopId()->equals($shopId)) {
                continue;
            }

            $this->shopMemberships->removeElement($membership);

            if (null !== $this->shopId && $this->shopId->equals($shopId)) {
                $remaining = $this->shopMemberships->first();
                $this->shopId = false !== $remaining ? $remaining->getShopId() : null;
                if (false !== $remaining) {
                    $remaining->markAsPrimary();
                }
            }

            $this->touch();

            return;
        }
    }

    private function makePrimary(UserShopMembership $primary): void
    {
        foreach ($this->shopMemberships as $membership) {
            if ($membership === $primary) {
                $membership->markAsPrimary();
                continue;
            }

            $membership->demoteFromPrimary();
        }

        $this->shopId = $primary->getShopId();
    }

    public function isPlatformOwner(): bool
    {
        return $this->isPlatformOwner;
    }

    public function promoteToPlatformOwner(): void
    {
        $this->isPlatformOwner = true;
        $this->shopId = null;
        $this->shopMemberships->clear();
        $roles = $this->roles;
        if (!in_array('ROLE_PLATFORM_OWNER', $roles, true)) {
            $roles[] = 'ROLE_PLATFORM_OWNER';
            $this->roles = $roles;
        }
        $this->touch();
    }

    public function belongsToShop(Uuid $shopId): bool
    {
        if (null !== $this->shopId && $this->shopId->equals($shopId)) {
            return true;
        }

        foreach ($this->shopMemberships as $membership) {
            if ($membership->getShopId()->equals($shopId)) {
                return true;
            }
        }

        return false;
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

    public function getIdentityId(): ?Uuid
    {
        return $this->identityId;
    }

    public function setIdentityId(?Uuid $identityId): void
    {
        $this->identityId = $identityId;
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
