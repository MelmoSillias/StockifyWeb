<?php

namespace App\Integration\Infrastructure\Command;

use App\Integration\Application\Query\GetAccountUsage\GetAccountUsageHandler;
use App\Integration\Application\Query\GetAccountUsage\GetAccountUsageQuery;
use App\Integration\Application\Service\UsageWebhookDispatcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'integration:dispatch-usage', description: 'Dispatch usage.updated webhook to Control Plane')]
final class DispatchUsageCommand extends Command
{
    public function __construct(
        private readonly GetAccountUsageHandler $getAccountUsageHandler,
        private readonly UsageWebhookDispatcher $usageWebhookDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('external-account-id', null, InputOption::VALUE_REQUIRED, 'External account id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $externalAccountId = (string) $input->getOption('external-account-id');
        if ('' === $externalAccountId) {
            $output->writeln('<error>--external-account-id is required</error>');

            return Command::FAILURE;
        }

        $usage = $this->getAccountUsageHandler->handle(new GetAccountUsageQuery($externalAccountId));
        $this->usageWebhookDispatcher->dispatchUsageUpdated($externalAccountId, [
            'shops_count' => (int) ($usage['shops_count'] ?? 0),
            'users_count' => (int) ($usage['users_count'] ?? 0),
        ]);

        $output->writeln('<info>Usage webhook dispatched.</info>');

        return Command::SUCCESS;
    }
}
