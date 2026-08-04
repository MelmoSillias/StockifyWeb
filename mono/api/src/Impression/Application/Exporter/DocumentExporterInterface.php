<?php

namespace App\Impression\Application\Exporter;

use App\Impression\Application\Dto\DocumentViewModel;
use App\Impression\Domain\Enum\OutputFormat;

interface DocumentExporterInterface
{
    public function supports(OutputFormat $format): bool;

    public function export(DocumentViewModel $viewModel, string $html, OutputFormat $format): string;
}
