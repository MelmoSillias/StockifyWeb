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
#[ORM\UniqueConstraint(name: 'uniq_user_username', fields: ['username'])]
#[ORM\UniqueConstraint(name: 'uniq_user_identity_id', columns: ['identity_id'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use UuidEntityTrait;
    use TimestampableTrait;

    private static string $authIdentifierMode = 'email';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    private string $username;

    #[ORM\Column(nullable: true)]
    private ?string $passwordHash = null;

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
        ?string $passwordHash,
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

    /**
     * Clears a synthetic .local address so username becomes the login identifier.
     */
    public function nullifySyntheticEmail(): void
    {
        if (null === $this->email || !str_ends_with($this->email, '.local')) {
            return;
        }

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
        $roles = ['ROLE_USER'];

        if ($this->isPlatformOwner) {
            $roles[] = 'ROLE_PLATFORM_OWNER';
        }

        foreach ($this->userRoles as $userRole) {
            $roles[] = PermissionCatalog::symfonyRole($userRole->getRole()->getCode());
        }

        return array_values(array_unique($roles));
    }

    public function getPassword(): ?string
    {
        if (null !== $this->identityId) {
            return '';
        }

        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): void
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

    public function assignToShop(Uuid $shopId): void
    {
        if ($this->isPlatformOwner) {
            throw new \DomainException('Platform owner cannot be assigned to a shop.');
        }

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

        return array_values($ids);
    }

    public function getPrimaryShopId(): ?Uuid
    {
        foreach ($this->shopMemberships as $membership) {
            if ($membership->isPrimary()) {
                return $membership->getShopId();
            }
        }

        $first = $this->shopMemberships->first();

        return false !== $first ? $first->getShopId() : null;
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

            $wasPrimary = $membership->isPrimary();
            $this->shopMemberships->removeElement($membership);

            if ($wasPrimary) {
                $remaining = $this->shopMemberships->first();
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
    }

    public function isPlatformOwner(): bool
    {
        return $this->isPlatformOwner;
    }

    public function promoteToPlatformOwner(): void
    {
        $this->isPlatformOwner = true;
        $this->shopMemberships->clear();
        $this->touch();
    }

    public function belongsToShop(Uuid $shopId): bool
    {
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
