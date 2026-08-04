<?php

namespace App\SharedKernel\Infrastructure\Shop;

use App\IdentityAccess\Domain\Entity\User;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Infrastructure\Doctrine\Filter\ShopScopeFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ShopContextSubscriber implements EventSubscriberInterface
{
    /** @var list<string> */
    private const EXCLUDED_PREFIXES = [
        '/api/login',
        '/api/health',
        '/api/token/refresh',
        '/api/units-of-measure',
        '/api/shops',
        '/api/me',
        '/api/roles',
        '/api/permissions',
    ];

    public function __construct(
        private readonly ShopContextResolver $shopContextResolver,
        private readonly ShopContextHolder $shopContextHolder,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onController', 10],
            KernelEvents::REQUEST => ['onRequest', 5],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->shopContextHolder->clear();
        $filters = $this->entityManager->getFilters();
        if ($filters->isEnabled('shop_scope')) {
            $filters->disable('shop_scope');
        }
    }

    public function onController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        if (!str_starts_with($path, '/api')) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (!$user instanceof User) {
            return;
        }

        if ($user->isPlatformOwner() && !$request->headers->has('X-Shop-Id')) {
            if (str_starts_with($path, '/api/shops') || str_starts_with($path, '/api/me')) {
                return;
            }

            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Missing header X-Shop-Id.');
        }

        if (!$user->isPlatformOwner() && !$request->headers->has('X-Shop-Id')) {
            if (null !== $user->getShopId()) {
                $request->headers->set('X-Shop-Id', (string) $user->getShopId());
            }
        }

        if (!$request->headers->has('X-Shop-Id')) {
            return;
        }

        $context = $this->shopContextResolver->resolve($user, $request);
        $this->shopContextHolder->set($context);

        $filters = $this->entityManager->getFilters();
        if (!$filters->isEnabled('shop_scope')) {
            $filters->enable('shop_scope');
        }

        /** @var ShopScopeFilter $filter */
        $filter = $filters->getFilter('shop_scope');
        $filter->setParameter('shop_id', "'".$context->getShopId()->toRfc4122()."'");
    }
}
