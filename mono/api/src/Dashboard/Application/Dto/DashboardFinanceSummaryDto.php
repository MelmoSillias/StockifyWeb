<?php

namespace App\Dashboard\Application\Dto;

final readonly class DashboardFinanceSummaryDto
{
    /**
     * @param array<string, mixed>|null $clientCreances
     * @param array<string, mixed>|null $supplierDettes
     * @param array<string, mixed>|null $treasury
     */
    public function __construct(
        public ?array $clientCreances = null,
        public ?array $supplierDettes = null,
        public ?array $treasury = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [];

        if (null !== $this->clientCreances) {
            $data['client_creances'] = $this->clientCreances;
        }

        if (null !== $this->supplierDettes) {
            $data['supplier_dettes'] = $this->supplierDettes;
        }

        if (null !== $this->treasury) {
            $data['treasury'] = $this->treasury;
        }

        return $data;
    }
}
