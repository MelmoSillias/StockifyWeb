<?php

namespace App\Impression\Infrastructure\Exporter;

use App\Impression\Application\Dto\DocumentViewModel;
use App\Impression\Application\Exporter\DocumentExporterInterface;
use App\Impression\Domain\Enum\OutputFormat;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class PhpSpreadsheetExporter implements DocumentExporterInterface
{
    public function supports(OutputFormat $format): bool
    {
        return in_array($format, [OutputFormat::Excel, OutputFormat::Csv], true);
    }

    public function export(DocumentViewModel $viewModel, string $html, OutputFormat $format): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($viewModel->data['title'] ?? 'Export', 0, 31));

        $columns = $viewModel->data['columns'] ?? [];
        $rows = $viewModel->data['rows'] ?? [];

        $colIndex = 1;
        foreach ($columns as $column) {
            $sheet->setCellValue([$colIndex, 1], $column['label'] ?? $column['key'] ?? '');
            ++$colIndex;
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($columns as $column) {
                $key = $column['key'] ?? '';
                $sheet->setCellValue([$colIndex, $rowIndex], $row[$key] ?? '');
                ++$colIndex;
            }
            ++$rowIndex;
        }

        $temp = tempnam(sys_get_temp_dir(), 'stockify_export_');
        if (false === $temp) {
            throw new \RuntimeException('Unable to create temporary export file.');
        }

        if (OutputFormat::Csv === $format) {
            $writer = new Csv($spreadsheet);
            $writer->setDelimiter(';');
            $writer->setEnclosure('"');
            $writer->save($temp);
        } else {
            $writer = new Xlsx($spreadsheet);
            $writer->save($temp);
        }

        $content = file_get_contents($temp);
        @unlink($temp);

        if (false === $content) {
            throw new \RuntimeException('Unable to read export file.');
        }

        return $content;
    }
}
