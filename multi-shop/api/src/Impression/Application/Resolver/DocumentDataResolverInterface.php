<?php

namespace App\Impression\Application\Resolver;

use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\ValueObject\DocumentRequest;

interface DocumentDataResolverInterface
{
    public function supports(DocumentType $type): bool;

    /** @return array<string, mixed> */
    public function resolve(DocumentRequest $request): array;
}
