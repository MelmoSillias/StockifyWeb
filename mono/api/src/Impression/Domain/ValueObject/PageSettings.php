<?php

namespace App\Impression\Domain\ValueObject;

use App\Impression\Domain\Enum\PageFormat;

final readonly class PageSettings
{
    public function __construct(
        public PageFormat $format,
        public int $marginMm = 10,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'format' => $this->format->value,
            'margin_mm' => $this->marginMm,
            'css_page_size' => $this->format->cssPageSize(),
        ];
    }
}
