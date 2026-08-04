<?php

namespace App\Impression\Application\Resolver;

use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\ValueObject\DocumentRequest;

final class DocumentDataResolverRegistry
{
    /** @param iterable<DocumentDataResolverInterface> $resolvers */
    public function __construct(
        private readonly iterable $resolvers,
    ) {
    }

    /** @return array<string, mixed> */
    public function resolve(DocumentRequest $request): array
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($request->documentType)) {
                return $resolver->resolve($request);
            }
        }

        throw new \InvalidArgumentException(sprintf('No resolver for document type "%s".', $request->documentType->value));
    }
}
