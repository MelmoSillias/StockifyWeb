<?php

namespace App\AccessAudit\Infrastructure\Command;

use App\AccessAudit\Domain\Entity\Permission;
use App\AccessAudit\Domain\PermissionCatalog;
use App\AccessAudit\Domain\Repository\PermissionRepositoryInterface;
use App\AccessAudit\Domain\Repository\RoleRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name: 'access:sync-devis-permissions',
    description: 'Ajoute les permissions commerce.devis.* manquantes et les lie aux rôles',
)]
final class SyncDevisPermissionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $devisDefs = array_values(array_filter(
            PermissionCatalog::all(),
            static fn (array $def): bool => str_starts_with($def['code'], 'commerce.devis.'),
        ));

        $permissionsByCode = [];
        $created = 0;

        foreach ($devisDefs as $def) {
            $existing = $this->permissionRepository->findByCode($def['code']);

            if (null === $existing) {
                $existing = new Permission(
                    $def['code'],
                    $def['label'],
                    $def['module'],
                    $def['action'],
                    $def['is_critical'],
                );
                $this->entityManager->persist($existing);
                ++$created;
                $io->writeln(sprintf('  + Permission créée : %s', $def['code']));
            } else {
                $io->writeln(sprintf('  = Permission existante : %s', $def['code']));
            }

            $permissionsByCode[$def['code']] = $existing;
        }

        $this->entityManager->flush();

        $linked = 0;
        foreach (PermissionCatalog::rolePermissions() as $roleCode => $permissionCodes) {
            $role = $this->roleRepository->findByCode($roleCode);
            if (null === $role) {
                $io->warning(sprintf('Rôle introuvable : %s', $roleCode));
                continue;
            }

            foreach ($permissionCodes as $code) {
                if (!str_starts_with($code, 'commerce.devis.') || !isset($permissionsByCode[$code])) {
                    continue;
                }

                $permission = $permissionsByCode[$code];
                if (!$role->getPermissions()->contains($permission)) {
                    $role->addPermission($permission);
                    ++$linked;
                    $io->writeln(sprintf('  → %s ← %s', $roleCode, $code));
                }
            }
        }

        $this->entityManager->flush();

        $this->cache->clear();

        $io->success(sprintf(
            'Terminé : %d permission(s) créée(s), %d lien(s) rôle-permission ajouté(s).',
            $created,
            $linked,
        ));

        return Command::SUCCESS;
    }
}
