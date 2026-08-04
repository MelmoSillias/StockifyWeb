<?php

namespace App\SharedKernel\Application\Event;

use App\SharedKernel\Domain\Event\DomainEventInterface;

interface DomainEventDispatcherInterface
{
    public function dispatch(DomainEventInterface $event): void;
}
