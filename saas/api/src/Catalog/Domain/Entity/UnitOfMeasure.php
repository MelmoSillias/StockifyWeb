<?php

namespace App\Catalog\Domain\Entity;

use App\Catalog\Infrastructure\Persistence\Doctrine\DoctrineUnitOfMeasureRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineUnitOfMeasureRepository::class)]
#[ORM\Table(name: 'units_of_measure')]
#[ORM\UniqueConstraint(name: 'uniq_unit_code', fields: ['code'])]
class UnitOfMeasure
{
    use UuidEntityTrait;

    #[ORM\Column(length: 20)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column]
    private int $decimalPlaces = 0;

    #[ORM\Column]
    private bool $isSystem = true;

    public function __construct(string $code, string $label, int $decimalPlaces = 0, bool $isSystem = true)
    {
        $this->initializeUuid();
        $this->code = $code;
        $this->label = $label;
        $this->decimalPlaces = $decimalPlaces;
        $this->isSystem = $isSystem;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDecimalPlaces(): int
    {
        return $this->decimalPlaces;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }
}
