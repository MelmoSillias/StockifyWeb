<?php

namespace App\Impression\Infrastructure\Exporter;

use App\Impression\Application\Dto\DocumentViewModel;
use App\Impression\Application\Exporter\DocumentExporterInterface;
use App\Impression\Domain\Enum\OutputFormat;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

final class PhpWordExporter implements DocumentExporterInterface
{
    public function supports(OutputFormat $format): bool
    {
        return OutputFormat::Word === $format;
    }

    public function export(DocumentViewModel $viewModel, string $html, OutputFormat $format): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        Html::addHtml($section, $html, false, false);

        $temp = tempnam(sys_get_temp_dir(), 'stockify_word_');
        if (false === $temp) {
            throw new \RuntimeException('Unable to create temporary Word file.');
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($temp);

        $content = file_get_contents($temp);
        @unlink($temp);

        if (false === $content) {
            throw new \RuntimeException('Unable to read Word export file.');
        }

        return $content;
    }
}
