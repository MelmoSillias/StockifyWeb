<?php

namespace App\Impression\Application\Factory;

use App\Impression\Domain\Entity\PrintSettings;
use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\Enum\OutputFormat;
use App\Impression\Domain\Enum\PageFormat;
use App\Impression\Domain\Enum\TableType;
use App\Impression\Domain\ValueObject\DocumentRequest;

final class DocumentRequestFactory
{
    public function createDocument(
        string $type,
        string $id,
        ?string $format,
        ?string $page,
        bool $inline,
        PrintSettings $settings,
    ): DocumentRequest {
        $documentType = DocumentType::from($type);
        $outputFormat = null !== $format ? OutputFormat::from($format) : OutputFormat::Html;
        $pageFormat = null !== $page
            ? PageFormat::from($page)
            : $settings->defaultPageFor($documentType);

        $this->assertPageFormatAllowed($documentType, $pageFormat);

        if (null === $id || '' === trim($id)) {
            throw new \InvalidArgumentException('Document id is required.');
        }

        return new DocumentRequest(
            documentType: $documentType,
            entityId: $id,
            format: $outputFormat,
            pageFormat: $pageFormat,
            inline: $inline,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createTableExport(
        string $tableType,
        string $format,
        ?string $page,
        array $payload,
        PrintSettings $settings,
    ): DocumentRequest {
        $table = TableType::from($tableType);
        $outputFormat = OutputFormat::from($format);
        $pageFormat = null !== $page ? PageFormat::from($page) : $settings->getDefaultPageTable();

        if (!in_array($pageFormat, [PageFormat::A4, PageFormat::A5], true)) {
            throw new \InvalidArgumentException('Table exports only support A4 or A5 page formats.');
        }

        return new DocumentRequest(
            documentType: DocumentType::Table,
            entityId: null,
            format: $outputFormat,
            pageFormat: $pageFormat,
            inline: false,
            tablePayload: array_merge($payload, ['table_type' => $table->value]),
            tableType: $table,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createTablePrint(
        string $tableType,
        ?string $page,
        array $payload,
        PrintSettings $settings,
    ): DocumentRequest {
        $request = $this->createTableExport($tableType, OutputFormat::Html->value, $page, $payload, $settings);

        return new DocumentRequest(
            documentType: $request->documentType,
            entityId: null,
            format: OutputFormat::Html,
            pageFormat: $request->pageFormat,
            inline: true,
            tablePayload: $request->tablePayload,
            tableType: $request->tableType,
        );
    }

    private function assertPageFormatAllowed(DocumentType $type, PageFormat $format): void
    {
        if (!in_array($format, $type->allowedPageFormats(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'Page format "%s" is not allowed for document type "%s".',
                $format->value,
                $type->value,
            ));
        }
    }
}
