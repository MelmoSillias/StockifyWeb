<?php

namespace App\AccessAudit\Infrastructure\Command;

use App\AccessAudit\Domain\Repository\AuditLogRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'audit:purge', description: 'Purge audit logs older than retention period')]
final class AuditPurgeCommand extends Command
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('months', 'm', InputOption::VALUE_OPTIONAL, 'Retention in months', 12);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $months = max(1, (int) $input->getOption('months'));
        $before = new \DateTimeImmutable(sprintf('-%d months', $months));

        $deleted = $this->auditLogRepository->deleteOlderThan($before);
        $output->writeln(sprintf('Purged %d audit log entries older than %s.', $deleted, $before->format('Y-m-d')));

        return Command::SUCCESS;
    }
}
