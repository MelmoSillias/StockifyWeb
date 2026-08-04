<?php

namespace App\Impression\Domain\Enum;

enum PageFormat: string
{
    case A4 = 'a4';
    case A5 = 'a5';
    case Receipt80mm = 'receipt_80mm';

    public function cssPageSize(): string
    {
        return match ($this) {
            self::A4 => '210mm 297mm',
            self::A5 => '148mm 210mm',
            self::Receipt80mm => '80mm auto',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::A4 => 'A4',
            self::A5 => 'A5',
            self::Receipt80mm => '80 mm',
        };
    }
}
