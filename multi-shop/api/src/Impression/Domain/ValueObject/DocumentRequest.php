<?php

namespace App\Impression\Domain\ValueObject;

use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\Enum\OutputFormat;
use App\Impression\Domain\Enum\PageFormat;
use App\Impression\Domain\Enum\TableType;

final readonly class DocumentRequest
{
    public function __construct(
        public DocumentType $documentType,
        public ?string $entityId,
        public OutputFormat $format,
        public PageFormat $pageFormat,
        public bool $inline,
        /** @var array<string, mixed> */
        public array $tablePayload = [],
        public ?TableType $tableType = null,
    ) {
    }
}
