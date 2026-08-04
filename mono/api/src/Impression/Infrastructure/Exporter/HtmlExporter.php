<?php

namespace App\Impression\Infrastructure\Exporter;

use App\Impression\Application\Dto\DocumentViewModel;
use App\Impression\Application\Exporter\DocumentExporterInterface;
use App\Impression\Domain\Enum\OutputFormat;

final class HtmlExporter implements DocumentExporterInterface
{
    public function supports(OutputFormat $format): bool
    {
        return OutputFormat::Html === $format;
    }

    public function export(DocumentViewModel $viewModel, string $html, OutputFormat $format): string
    {
        return $html;
    }
}
