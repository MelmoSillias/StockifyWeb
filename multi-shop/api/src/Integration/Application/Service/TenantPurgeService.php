<?php

namespace App\Integration\Application\Service;

use App\IdentityAccess\Domain\Entity\User;
use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class TenantPurgeService
{
    /** Child / line tables first, then parents, then masters. */
    private const SHOP_SCOPED_TABLES = [
        'avoir_lines',
        'facture_lines',
        'vente_lines',
        'commande_lines',
        'devis_lines',
        'bon_livraison_lines',
        'commande_fournisseur_lines',
        'stock_lot_allocations',
        'paiements',
        'paiements_fournisseur',
        'avoirs',
        'factures',
        'bons_livraison',
        'ventes',
        'commandes',
        'devis',
        'stock_movements',
        'stock_lots',
        'stock_policies',
        'dettes_fournisseur',
        'commandes_fournisseur',
        'transactions',
        'audit_logs',
        'print_settings',
        'product_variants',
        'products',
        'product_categories',
        'clients',
        'fournisseurs',
        'modes_de_paiement',
        'comptes',
    ];

    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly bool $purgeEnabled,
    ) {
    }

    public function isPurgeEnabled(): bool
    {
        return $this->purgeEnabled;
    }

    /**
     * @return array{
     *     external_account_id: string,
     *     mode: string,
     *     shops_purged: int,
     *     users_purged: int,
     *     purged_at: string
     * }
     */
    public function purge(string $externalAccountId): array
    {
        if (!$this->purgeEnabled) {
            throw new \DomainException('Tenant purge is disabled.');
        }

        $account = $this->tenantAccountRepository->findByExternalAccountId($externalAccountId);
        if (null === $account) {
            throw new \InvalidArgumentException('Tenant account not found.');
        }

        $shops = $this->shopRepository->findByTenantAccountId($account->getId());
        $shopIds = array_map(static fn (Shop $shop): Uuid => $shop->getId(), $shops);

        $filters = $this->entityManager->getFilters();
        $shopScopeWasEnabled = $filters->isEnabled('shop_scope');
        if ($shopScopeWasEnabled) {
            $filters->disable('shop_scope');
        }

        try {
            if ([] !== $shopIds) {
                $this->deleteShopScopedRows($shopIds);
            }

            $usersPurged = $this->deleteTenantUsers($account, $shopIds);

            foreach ($shops as $shop) {
                $this->entityManager->remove($shop);
            }
            $this->entityManager->flush();

            $this->tenantAccountRepository->remove($account);
        } finally {
            if ($shopScopeWasEnabled && !$filters->isEnabled('shop_scope')) {
                $filters->enable('shop_scope');
            }
        }

        return [
            'external_account_id' => $externalAccountId,
            'mode' => 'purge',
            'shops_purged' => count($shops),
            'users_purged' => $usersPurged,
            'purged_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @param list<Uuid> $shopIds */
    private function deleteShopScopedRows(array $shopIds): void
    {
        $connection = $this->entityManager->getConnection();
        $hexList = array_map(
            static fn (Uuid $id): string => bin2hex($id->toBinary()),
            $shopIds,
        );
        $placeholders = implode(', ', array_fill(0, count($hexList), 'UNHEX(?)'));

        foreach (self::SHOP_SCOPED_TABLES as $table) {
            if (!$this->tableExists($connection, $table)) {
                continue;
            }

            $connection->executeStatement(
                sprintf('DELETE FROM %s WHERE shop_id IN (%s)', $table, $placeholders),
                $hexList,
            );
        }
    }

    /**
     * @param list<Uuid> $shopIds
     */
    private function deleteTenantUsers(TenantAccount $account, array $shopIds): int
    {
        /** @var list<User> $byTenant */
        $byTenant = $this->entityManager->getRepository(User::class)->findBy([
            'tenantAccountId' => $account->getId(),
        ]);

        $unique = [];
        foreach ($byTenant as $user) {
            if ($user->isPlatformOwner()) {
                continue;
            }
            $unique[$user->getId()->toRfc4122()] = $user;
        }

        foreach ($shopIds as $shopId) {
            /** @var list<User> $byShop */
            $byShop = $this->entityManager->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u')
                ->leftJoin('u.shopMemberships', 'm')
                ->andWhere('m.shopId = :shopId')
                ->setParameter('shopId', $shopId, 'uuid')
                ->getQuery()
                ->getResult();

            foreach ($byShop as $user) {
                if ($user->isPlatformOwner()) {
                    continue;
                }
                $unique[$user->getId()->toRfc4122()] = $user;
            }
        }

        foreach ($unique as $user) {
            $this->entityManager->remove($user);
        }
        $this->entityManager->flush();

        return count($unique);
    }

    private function tableExists(Connection $connection, string $table): bool
    {
        $schemaManager = $connection->createSchemaManager();

        return $schemaManager->tablesExist([$table]);
    }
}
