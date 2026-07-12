<?php

namespace App\Repository;

use App\Entity\PaiementCreditClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaiementCreditClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementCreditClient::class);
    }

    // Add custom query methods here
}
