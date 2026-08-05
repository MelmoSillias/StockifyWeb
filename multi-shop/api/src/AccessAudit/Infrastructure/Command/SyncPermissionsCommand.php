<?php

namespace App\AccessAudit\Infrastructure\Command;

use App\AccessAudit\Domain\Entity\Permission;
use App\AccessAudit\Domain\Entity\Role;
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
    name: 'access:sync-permissions',
    description: 'Synchronise permissions et liaisons rôle-permission depuis PermissionCatalog',
)]
final class SyncPermissionsCommand extends Command
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

        $permissionsByCode = [];
        $createdPermissions = 0;

        foreach (PermissionCatalog::all() as $def) {
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
                ++$createdPermissions;
                $io->writeln(sprintf('  + Permission créée : %s', $def['code']));
            }

            $permissionsByCode[$def['code']] = $existing;
        }

        $this->entityManager->flush();

        $createdRoles = 0;
        foreach (PermissionCatalog::predefinedRoles() as $def) {
            $role = $this->roleRepository->findByCode($def['code']);
            if (null === $role) {
                $role = new Role($def['code'], $def['label'], $def['description'], $def['is_system']);
                $this->entityManager->persist($role);
                ++$createdRoles;
                $io->writeln(sprintf('  + Rôle créé : %s', $def['code']));
            }
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
                if (!isset($permissionsByCode[$code])) {
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
            'Terminé : %d permission(s) créée(s), %d rôle(s) créé(s), %d lien(s) rôle-permission ajouté(s).',
            $createdPermissions,
            $createdRoles,
            $linked,
        ));

        return Command::SUCCESS;
    }
}
