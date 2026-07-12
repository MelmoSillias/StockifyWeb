<?php

namespace App\Catalog\Application\Command\RegisterProduct;

final readonly class RegisterProductCommand
{
    /**
     * @param array{
     *     sku: string,
     *     unit_of_measure_id: string,
     *     sale_mode: string,
     *     default_price?: string|null,
     *     alert_threshold?: string|null
     * }|null $variant
     * @param list<array{
     *     quantity: string,
     *     unit_cost: string,
     *     reference?: string|null,
     *     supplier_ref?: string|null,
     *     expiry_date?: string|null
     * }> $lots
     */
    public function __construct(
        public string $name,
        public ?string $reference = null,
        public ?string $description = null,
        public ?string $categoryId = null,
        public ?array $variant = null,
        public array $lots = [],
    ) {
    }
}
