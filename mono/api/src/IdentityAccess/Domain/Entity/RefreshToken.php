<?php

namespace App\IdentityAccess\Domain\Entity;

use App\IdentityAccess\Infrastructure\Persistence\Doctrine\DoctrineRefreshTokenRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineRefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 64)]
    private string $tokenHash;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    public function __construct(User $user, string $tokenHash, \DateTimeImmutable $expiresAt)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->user = $user;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isValid(): bool
    {
        return null === $this->revokedAt && $this->expiresAt > new \DateTimeImmutable();
    }

    public function revoke(): void
    {
        $this->revokedAt = new \DateTimeImmutable();
        $this->touch();
    }
}
