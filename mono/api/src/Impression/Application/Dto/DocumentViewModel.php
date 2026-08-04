<?php

namespace App\Impression\Application\Dto;

/** @phpstan-type DocumentViewModel array{document_type: string, page: array<string, mixed>, profile: array<string, mixed>, data: array<string, mixed>, auto_print: bool} */
final readonly class DocumentViewModel
{
    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $documentType,
        public array $page,
        public array $profile,
        public array $data,
        public bool $autoPrint = false,
    ) {
    }

    /** @return DocumentViewModel */
    public function toArray(): array
    {
        return [
            'document_type' => $this->documentType,
            'page' => $this->page,
            'profile' => $this->profile,
            'data' => $this->data,
            'auto_print' => $this->autoPrint,
        ];
    }
}
