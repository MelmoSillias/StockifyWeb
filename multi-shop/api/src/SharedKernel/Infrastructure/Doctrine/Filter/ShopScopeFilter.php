<?php

namespace App\SharedKernel\Infrastructure\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class ShopScopeFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (null === $targetEntity->reflClass) {
            return '';
        }

        if (!$targetEntity->reflClass->implementsInterface(\App\SharedKernel\Domain\Contract\ShopScopedInterface::class)) {
            return '';
        }

        try {
            $shopId = $this->getParameter('shop_id');
        } catch (\InvalidArgumentException) {
            return '';
        }

        if ('' === $shopId || "''" === $shopId) {
            return '';
        }

        // shop_id is stored as BINARY(16); comparing it to a hex literal requires
        // UNHEX, otherwise the predicate silently matches nothing.
        return sprintf('%s.shop_id = UNHEX(%s)', $targetTableAlias, $shopId);
    }
}
