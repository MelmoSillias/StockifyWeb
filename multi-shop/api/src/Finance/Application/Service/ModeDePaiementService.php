<?php

namespace App\Finance\Application\Service;

use App\Finance\Domain\Entity\ModeDePaiement;
use App\Finance\Domain\Repository\CompteRepositoryInterface;
use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class ModeDePaiementService
{
    public function __construct(
        private readonly ModeDePaiementRepositoryInterface $modeDePaiementRepository,
        private readonly CompteRepositoryInterface $compteRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): ModeDePaiement
    {
        if (empty($payload['code'])) {
            throw new \InvalidArgumentException('code is required.');
        }
        if (empty($payload['label'])) {
            throw new \InvalidArgumentException('label is required.');
        }
        if (empty($payload['compte_id'])) {
            throw new \InvalidArgumentException('compte_id is required.');
        }

        $code = strtolower(trim((string) $payload['code']));
        if (null !== $this->modeDePaiementRepository->findByCode($code)) {
            throw new \InvalidArgumentException('A payment method with this code already exists.');
        }

        $compteId = Uuid::fromString((string) $payload['compte_id']);
        $this->assertCompteActive($compteId);

        $mode = new ModeDePaiement(
            $code,
            (string) $payload['label'],
            $compteId,
            !array_key_exists('generates_transaction', $payload) || (bool) $payload['generates_transaction'],
        );

        if (array_key_exists('is_active', $payload)) {
            $mode->setActive((bool) $payload['is_active']);
        }

        $this->modeDePaiementRepository->save($mode);

        return $mode;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(ModeDePaiement $mode, array $payload): ModeDePaiement
    {
        if (!empty($payload['label'])) {
            $mode->setLabel((string) $payload['label']);
        }

        if (!empty($payload['compte_id'])) {
            $compteId = Uuid::fromString((string) $payload['compte_id']);
            $this->assertCompteActive($compteId);
            $mode->setCompteId($compteId);
        }

        if (array_key_exists('is_active', $payload)) {
            $mode->setActive((bool) $payload['is_active']);
        }

        if (array_key_exists('generates_transaction', $payload)) {
            $mode->setGeneratesTransaction((bool) $payload['generates_transaction']);
        }

        $this->modeDePaiementRepository->save($mode);

        return $mode;
    }

    public function delete(ModeDePaiement $mode): void
    {
        $this->modeDePaiementRepository->remove($mode);
    }

    public function resolveFromPayload(array $payload): ModeDePaiement
    {
        if (!empty($payload['mode_de_paiement_id'])) {
            $mode = $this->modeDePaiementRepository->findById(Uuid::fromString((string) $payload['mode_de_paiement_id']));
            if (null === $mode) {
                throw new \InvalidArgumentException('Unknown payment method.');
            }

            return $this->assertModeActive($mode);
        }

        if (!empty($payload['method'])) {
            $mode = $this->modeDePaiementRepository->findByCode((string) $payload['method']);
            if (null === $mode) {
                throw new \InvalidArgumentException('Unknown payment method.');
            }

            return $this->assertModeActive($mode);
        }

        throw new \InvalidArgumentException('mode_de_paiement_id or method is required.');
    }

    private function assertCompteActive(Uuid $compteId): void
    {
        $compte = $this->compteRepository->findById($compteId);
        if (null === $compte || !$compte->isActive()) {
            throw new \InvalidArgumentException('Unknown or inactive account.');
        }
    }

    private function assertModeActive(ModeDePaiement $mode): ModeDePaiement
    {
        if (!$mode->isActive()) {
            throw new \InvalidArgumentException('Payment method is inactive.');
        }

        return $mode;
    }
}
