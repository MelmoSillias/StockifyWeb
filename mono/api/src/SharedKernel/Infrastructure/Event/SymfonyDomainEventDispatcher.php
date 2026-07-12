<?php

namespace App\SharedKernel\Infrastructure\Event;

use App\SharedKernel\Application\Event\DomainEventDispatcherInterface;
use App\SharedKernel\Domain\Event\DomainEventInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SymfonyDomainEventDispatcher implements DomainEventDispatcherInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function dispatch(DomainEventInterface $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }
}
