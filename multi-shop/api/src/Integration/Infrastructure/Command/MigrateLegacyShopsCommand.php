<?php

namespace App\Integration\Infrastructure\Command;

use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'integration:migrate-legacy-shops',
    description: 'Create TenantAccount records for shops without tenant_account_id (idempotent)',
)]
final class MigrateLegacyShopsCommand extends Command
{
    public function __construct(
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview without persisting')
            ->addOption('export', null, InputOption::VALUE_REQUIRED, 'Export mapping JSON to file for Control Plane import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $exportPath = $input->getOption('export');

        $shops = $this->shopRepository->findAllOrderedByName();
        $created = 0;
        $skipped = 0;
        $export = [];

        foreach ($shops as $shop) {
            if (null !== $shop->getTenantAccountId()) {
                ++$skipped;
                continue;
            }

            $externalAccountId = $this->resolveExternalAccountId($shop);

            $existing = $this->tenantAccountRepository->findByExternalAccountId($externalAccountId);
            if (null !== $existing) {
                if (!$dryRun) {
                    $shop->setTenantAccountId($existing->getId());
                    $this->entityManager->flush();
                }
                ++$skipped;
                continue;
            }

            $tenant = new TenantAccount($externalAccountId, [
                'features' => [],
                'quotas' => ['max_shops' => 1, 'max_users' => 3],
            ]);
            $tenant->markProvisioned();

            if (!$dryRun) {
                $this->tenantAccountRepository->save($tenant, false);
                $shop->setTenantAccountId($tenant->getId());
                $this->entityManager->flush();
            }

            $export[] = [
                'external_account_id' => $externalAccountId,
                'account_name' => $shop->getName(),
                'account_slug' => 'legacy-'.$shop->getSlug(),
                'billing_email' => sprintf('billing@%s.local', $shop->getSlug()),
                'shop_id' => $shop->getId()->toRfc4122(),
                'shop_slug' => $shop->getSlug(),
                'tenant_account_id' => $tenant->getId()->toRfc4122(),
            ];

            ++$created;
            $io->writeln(sprintf('  + Shop "%s" → TenantAccount %s', $shop->getSlug(), $externalAccountId));
        }

        if (null !== $exportPath && [] !== $export) {
            file_put_contents($exportPath, json_encode($export, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $io->success(sprintf('Exported %d mapping(s) to %s', count($export), $exportPath));
        }

        $io->success(sprintf(
            'Migration complete: %d created, %d skipped%s',
            $created,
            $skipped,
            $dryRun ? ' (dry-run)' : '',
        ));

        return Command::SUCCESS;
    }

    private function resolveExternalAccountId(Shop $shop): string
    {
        return Uuid::v5(Uuid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8'), 'legacy-shop:'.$shop->getId()->toRfc4122())->toRfc4122();
    }
}
