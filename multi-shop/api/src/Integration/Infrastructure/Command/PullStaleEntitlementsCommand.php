<?php

namespace App\Integration\Infrastructure\Command;

use App\Integration\Application\Service\EntitlementPullService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'integration:pull-stale-entitlements',
    description: 'Pull entitlements from the Control Plane for tenants with a stale snapshot',
)]
final class PullStaleEntitlementsCommand extends Command
{
    public function __construct(
        private readonly EntitlementPullService $entitlementPullService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->entitlementPullService->pullAllStale();

        $io->success(sprintf(
            'Entitlements: %d refreshed, %d skipped (fresh), %d failed (kept last snapshot).',
            $result['refreshed'],
            $result['skipped'],
            $result['failed'],
        ));

        return Command::SUCCESS;
    }
}
