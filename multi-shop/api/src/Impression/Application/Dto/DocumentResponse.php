<?php

namespace App\Impression\Application\Dto;

use App\Impression\Domain\Enum\OutputFormat;

final readonly class DocumentResponse
{
    public function __construct(
        public string $content,
        public OutputFormat $format,
        public string $filename,
        public bool $inline,
    ) {
    }
}
