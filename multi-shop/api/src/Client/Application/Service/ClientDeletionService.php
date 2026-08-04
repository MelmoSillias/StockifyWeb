<?php

namespace App\Client\Application\Service;

use App\Client\Domain\Entity\Client;
use App\Client\Domain\Repository\ClientRepositoryInterface;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class ClientDeletionService
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly FactureRepositoryInterface $factureRepository,
    ) {
    }

    /** @return 'hard'|'soft' */
    public function delete(Client $client): string
    {
        $clientId = $client->getId();

        if ($this->hasLinkedRecords($clientId)) {
            $client->markDeleted();
            $this->clientRepository->save($client);

            return 'soft';
        }

        $this->clientRepository->remove($client);

        return 'hard';
    }

    private function hasLinkedRecords(Uuid $clientId): bool
    {
        return $this->venteRepository->existsByClientId($clientId)
            || $this->commandeRepository->existsByClientId($clientId)
            || $this->factureRepository->existsByClientId($clientId);
    }
}
