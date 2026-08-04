<?php

namespace App\Commerce\Application\Service;

use App\Client\Domain\Repository\ClientRepositoryInterface;
use App\Commerce\Domain\ValueObject\Acheteur;

final class AcheteurPresenter
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
    ) {
    }

    /** @return array{client_id: string|null, anonymous_info: string|null, client_name: string|null} */
    public function present(Acheteur $acheteur): array
    {
        $data = $acheteur->toArray();
        $data['client_name'] = null;

        $clientId = $acheteur->clientId();
        if (null === $clientId) {
            return $data;
        }

        $client = $this->clientRepository->findById($clientId);
        if (null !== $client) {
            $data['client_name'] = $client->getName();
        }

        return $data;
    }
}
