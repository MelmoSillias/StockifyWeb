<?php

namespace App\Impression\Application\Resolver;

use App\Impression\Application\Resolver\Table\TableDataProviderInterface;
use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\Enum\TableType;
use App\Impression\Domain\ValueObject\DocumentRequest;

final class TableDocumentDataResolver implements DocumentDataResolverInterface
{
    /** @param iterable<TableDataProviderInterface> $providers */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function supports(DocumentType $type): bool
    {
        return DocumentType::Table === $type;
    }

    public function resolve(DocumentRequest $request): array
    {
        $tableTypeValue = $request->tablePayload['table_type'] ?? $request->tableType?->value;
        if (null === $tableTypeValue) {
            throw new \InvalidArgumentException('Table type is required.');
        }

        $tableType = TableType::from((string) $tableTypeValue);

        foreach ($this->providers as $provider) {
            if ($provider->supports($tableType)) {
                return $provider->provide($tableType, $request->tablePayload);
            }
        }

        throw new \InvalidArgumentException(sprintf('No table provider for "%s".', $tableType->value));
    }
}
