<?php

namespace App\Impression\Application\Resolver\Table;

use App\Impression\Domain\Enum\TableType;

interface TableDataProviderInterface
{
    public function supports(TableType $type): bool;

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{title: string, filename: string, columns: list<array{key: string, label: string}>, rows: list<array<string, mixed>>, filters_summary?: string}
     */
    public function provide(TableType $type, array $payload): array;
}
