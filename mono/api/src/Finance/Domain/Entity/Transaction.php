<?php

namespace App\Finance\Domain\Entity;

use App\Finance\Domain\Enum\TransactionSourceType;
use App\Finance\Domain\Enum\TransactionType;
use App\Finance\Infrastructure\Persistence\Doctrine\DoctrineTransactionRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineTransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
class Transaction
{
    use UuidEntityTrait;

    #[ORM\Column(type: 'uuid')]
    private Uuid $compteId;

    #[ORM\Column(enumType: TransactionType::class)]
    private TransactionType $type;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(enumType: TransactionSourceType::class)]
    private TransactionSourceType $sourceType;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $sourceId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    public function __construct(
        Uuid $compteId,
        TransactionType $type,
        string $amount,
        string $label,
        \DateTimeImmutable $occurredAt,
        TransactionSourceType $sourceType,
        ?Uuid $sourceId = null,
        ?string $description = null,
    ) {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException('A transaction amount must be positive.');
        }

        $this->initializeUuid();
        $this->compteId = $compteId;
        $this->type = $type;
        $this->amount = $amount;
        $this->label = $label;
        $this->occurredAt = $occurredAt;
        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
        $this->description = $description;
    }

    public function cancel(): void
    {
        if (null !== $this->cancelledAt) {
            throw new \DomainException('Transaction is already cancelled.');
        }

        $this->cancelledAt = new \DateTimeImmutable();
    }

    public function isCancelled(): bool
    {
        return null !== $this->cancelledAt;
    }

    public function getCompteId(): Uuid
    {
        return $this->compteId;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getSourceType(): TransactionSourceType
    {
        return $this->sourceType;
    }

    public function getSourceId(): ?Uuid
    {
        return $this->sourceId;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }
}
