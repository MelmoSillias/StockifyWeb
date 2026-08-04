<?php

namespace App\Impression\Domain\Enum;

enum OutputFormat: string
{
    case Html = 'html';
    case Pdf = 'pdf';
    case Excel = 'excel';
    case Csv = 'csv';
    case Word = 'word';

    public function mimeType(): string
    {
        return match ($this) {
            self::Html => 'text/html',
            self::Pdf => 'application/pdf',
            self::Excel => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Csv => 'text/csv',
            self::Word => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            self::Html => 'html',
            self::Pdf => 'pdf',
            self::Excel => 'xlsx',
            self::Csv => 'csv',
            self::Word => 'docx',
        };
    }
}
