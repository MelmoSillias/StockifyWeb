<?php

namespace App\Finance\Application\Service;

use App\Finance\Domain\Entity\Compte;
use App\Finance\Domain\Enum\CompteType;
use App\Finance\Domain\Repository\CompteRepositoryInterface;
use App\Finance\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class CompteService
{
    public function __construct(
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): Compte
    {
        if (empty($payload['name'])) {
            throw new \InvalidArgumentException('name is required.');
        }

        $type = CompteType::from((string) ($payload['type'] ?? CompteType::Caisse->value));
        $compte = new Compte((string) $payload['name'], $type);

        if (!empty($payload['is_active'])) {
            $compte->setActive((bool) $payload['is_active']);
        }

        $this->compteRepository->save($compte);

        return $compte;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(Compte $compte, array $payload): Compte
    {
        if (!empty($payload['name'])) {
            $compte->setName((string) $payload['name']);
        }

        if (!empty($payload['type'])) {
            $compte->setType(CompteType::from((string) $payload['type']));
        }

        if (array_key_exists('is_active', $payload)) {
            $compte->setActive((bool) $payload['is_active']);
        }

        $this->compteRepository->save($compte);

        return $compte;
    }

    public function computeBalance(Uuid $compteId): string
    {
        return $this->transactionRepository->computeBalance($compteId);
    }

    public function hasTransactions(Uuid $compteId): bool
    {
        return $this->transactionRepository->countByCompteId($compteId) > 0;
    }
}
