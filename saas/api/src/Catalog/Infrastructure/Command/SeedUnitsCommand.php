<?php

namespace App\Catalog\Infrastructure\Command;

use App\Catalog\Domain\Entity\UnitOfMeasure;
use App\Catalog\Domain\Repository\UnitOfMeasureRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:seed-units', description: 'Seed system units of measure')]
final class SeedUnitsCommand extends Command
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $unitOfMeasureRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $units = [
            ['piece', 'Pièce', 0],
            ['kg', 'Kilogramme', 3],
            ['liter', 'Litre', 3],
            ['carton', 'Carton', 0],
        ];

        foreach ($units as [$code, $label, $decimals]) {
            if (null !== $this->unitOfMeasureRepository->findByCode($code)) {
                continue;
            }
            $this->unitOfMeasureRepository->save(new UnitOfMeasure($code, $label, $decimals));
            $output->writeln(sprintf('Seeded unit: %s', $code));
        }

        return Command::SUCCESS;
    }
}
