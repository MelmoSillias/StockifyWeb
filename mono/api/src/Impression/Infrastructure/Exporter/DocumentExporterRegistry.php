<?php

namespace App\Impression\Infrastructure\Exporter;

use App\Impression\Application\Dto\DocumentViewModel;
use App\Impression\Application\Exporter\DocumentExporterInterface;
use App\Impression\Domain\Enum\OutputFormat;

final class DocumentExporterRegistry
{
    /** @param iterable<DocumentExporterInterface> $exporters */
    public function __construct(
        private readonly iterable $exporters,
    ) {
    }

    public function export(DocumentViewModel $viewModel, string $html, OutputFormat $format): string
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports($format)) {
                return $exporter->export($viewModel, $html, $format);
            }
        }

        throw new \InvalidArgumentException(sprintf('No exporter for format "%s".', $format->value));
    }
}
