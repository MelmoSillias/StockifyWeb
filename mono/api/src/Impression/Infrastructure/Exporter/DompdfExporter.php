<?php

namespace App\Impression\Infrastructure\Exporter;

use App\Impression\Application\Dto\DocumentViewModel;
use App\Impression\Application\Exporter\DocumentExporterInterface;
use App\Impression\Domain\Enum\OutputFormat;
use App\Impression\Domain\Enum\PageFormat;
use Dompdf\Dompdf;
use Dompdf\Options;

final class DompdfExporter implements DocumentExporterInterface
{
    public function supports(OutputFormat $format): bool
    {
        return OutputFormat::Pdf === $format;
    }

    public function export(DocumentViewModel $viewModel, string $html, OutputFormat $format): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($this->resolvePaper($viewModel));
        $dompdf->render();

        return $dompdf->output();
    }

    /** @return array{0: string, 1: string} */
    private function resolvePaper(DocumentViewModel $viewModel): array
    {
        $format = PageFormat::from($viewModel->page['format'] ?? PageFormat::A4->value);

        return match ($format) {
            PageFormat::A4 => ['A4', 'portrait'],
            PageFormat::A5 => ['A5', 'portrait'],
            PageFormat::Receipt80mm => [array(0, 0, 226.77, 841.89), 'portrait'],
        };
    }
}
