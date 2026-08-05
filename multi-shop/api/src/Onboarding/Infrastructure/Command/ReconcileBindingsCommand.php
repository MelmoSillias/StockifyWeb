<?php

namespace App\Onboarding\Infrastructure\Command;

use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Onboarding\Application\Service\PublicSignupService;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'onboarding:reconcile-bindings',
    description: 'Replay signup/complete against Control Plane for local TenantAccounts (idempotent)',
)]
final class ReconcileBindingsCommand extends Command
{
    public function __construct(
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly PublicSignupService $publicSignupService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('external-account-id', null, InputOption::VALUE_REQUIRED, 'Reconcile a single external account id')
            ->addOption('application', null, InputOption::VALUE_REQUIRED, 'Application slug', 'stockify')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List candidates without calling Control Plane');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $applicationSlug = (string) $input->getOption('application');
        $dryRun = (bool) $input->getOption('dry-run');
        $externalAccountId = $input->getOption('external-account-id');

        if (null !== $externalAccountId && '' !== trim((string) $externalAccountId)) {
            $accounts = [];
            $account = $this->tenantAccountRepository->findByExternalAccountId((string) $externalAccountId);
            if (null !== $account) {
                $accounts[] = $account;
            }
        } else {
            $accounts = $this->tenantAccountRepository->findAllOrdered();
        }

        if ([] === $accounts) {
            $io->success('No tenant accounts to reconcile.');

            return Command::SUCCESS;
        }

        $ok = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($accounts as $account) {
            $externalId = $account->getExternalAccountId();
            $shops = $this->shopRepository->findByTenantAccountId($account->getId());
            $shopIds = array_map(static fn ($shop) => (string) $shop->getId(), $shops);

            if ([] === $shopIds) {
                $io->writeln(sprintf(' - %s skipped (no shops)', $externalId));
                ++$skipped;
                continue;
            }

            if ($dryRun) {
                $io->writeln(sprintf(' - [dry-run] %s shops=%d', $externalId, count($shopIds)));
                continue;
            }

            try {
                $result = $this->publicSignupService->reconcileBinding($externalId, $shopIds, $applicationSlug);
                if (!empty($result['bindingPending'])) {
                    $io->writeln(sprintf(' - %s still pending', $externalId));
                    ++$failed;
                } else {
                    $io->writeln(sprintf(' - %s bound', $externalId));
                    ++$ok;
                }
            } catch (\Throwable $e) {
                $io->writeln(sprintf(' - %s FAILED: %s', $externalId, $e->getMessage()));
                ++$failed;
            }
        }

        if ($dryRun) {
            $io->success('Dry-run complete.');

            return Command::SUCCESS;
        }

        $io->writeln(sprintf('Reconciled=%d pending_or_failed=%d skipped=%d', $ok, $failed, $skipped));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
