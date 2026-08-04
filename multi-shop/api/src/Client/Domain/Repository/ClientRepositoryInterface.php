<?php

namespace App\Client\Domain\Repository;

use App\Client\Domain\Entity\Client;
use Symfony\Component\Uid\Uuid;

interface ClientRepositoryInterface
{
    public function save(Client $client, bool $flush = true): void;

    public function remove(Client $client, bool $flush = true): void;

    public function findById(Uuid $id): ?Client;

    /** @return list<Client> */
    public function findAll(): array;
}
