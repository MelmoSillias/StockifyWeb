<?php

namespace App\IdentityAccess\Domain\Entity;

use App\IdentityAccess\Domain\Enum\UserStatus;
use App\IdentityAccess\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: DoctrineUserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'uniq_user_username', fields: ['username'])]
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
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
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

    public function getLastName(): string
    {
        return $this->lastName;
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
}
