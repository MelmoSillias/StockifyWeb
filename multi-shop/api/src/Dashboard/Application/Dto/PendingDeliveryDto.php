<?php

namespace App\Dashboard\Application\Dto;

final readonly class PendingDeliveryDto
{
    public function __construct(
        public string $id,
        public string $reference,
        public ?string $clientName,
        public ?string $anonymousInfo,
        public string $status,
        public string $deliveryDate,
        public string $totalAmount,
        public bool $isOverdue,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'client_name' => $this->clientName,
            'anonymous_info' => $this->anonymousInfo,
            'status' => $this->status,
            'delivery_date' => $this->deliveryDate,
            'total_amount' => $this->totalAmount,
            'is_overdue' => $this->isOverdue,
        ];
    }
}
