<?php

namespace App\Impression\Infrastructure\Persistence\Doctrine;

use App\Impression\Domain\Entity\PrintSettings;
use App\Impression\Domain\Repository\PrintSettingsRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PrintSettings> */
final class DoctrinePrintSettingsRepository extends ServiceEntityRepository implements PrintSettingsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrintSettings::class);
    }

    public function findSingleton(): ?PrintSettings
    {
        return $this->createQueryBuilder('p')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getOrCreateDefault(string $shopName = 'Stockify'): PrintSettings
    {
        $existing = $this->findSingleton();
        if (null !== $existing) {
            return $existing;
        }

        $settings = PrintSettings::createDefault($shopName);
        $this->save($settings);

        return $settings;
    }

    public function save(PrintSettings $settings): void
    {
        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();
    }
}
