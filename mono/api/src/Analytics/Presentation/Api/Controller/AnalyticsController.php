<?php

namespace App\Analytics\Presentation\Api\Controller;

use App\Analytics\Application\Query\GetAnalyticsClients\GetAnalyticsClientsHandler;
use App\Analytics\Application\Query\GetAnalyticsClients\GetAnalyticsClientsQuery;
use App\Analytics\Application\Query\GetAnalyticsFinance\GetAnalyticsFinanceHandler;
use App\Analytics\Application\Query\GetAnalyticsFinance\GetAnalyticsFinanceQuery;
use App\Analytics\Application\Query\GetAnalyticsInventory\GetAnalyticsInventoryHandler;
use App\Analytics\Application\Query\GetAnalyticsInventory\GetAnalyticsInventoryQuery;
use App\Analytics\Application\Query\GetAnalyticsOverview\GetAnalyticsOverviewHandler;
use App\Analytics\Application\Query\GetAnalyticsOverview\GetAnalyticsOverviewQuery;
use App\Analytics\Application\Query\GetAnalyticsPayments\GetAnalyticsPaymentsHandler;
use App\Analytics\Application\Query\GetAnalyticsPayments\GetAnalyticsPaymentsQuery;
use App\Analytics\Application\Query\GetAnalyticsPurchases\GetAnalyticsPurchasesHandler;
use App\Analytics\Application\Query\GetAnalyticsPurchases\GetAnalyticsPurchasesQuery;
use App\Analytics\Application\Query\GetAnalyticsSales\GetAnalyticsSalesHandler;
use App\Analytics\Application\Query\GetAnalyticsSales\GetAnalyticsSalesQuery;
use App\Analytics\Application\Service\AnalyticsPermissionFilter;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/analytics')]
final class AnalyticsController extends AbstractController
{
    public function __construct(
        private readonly GetAnalyticsOverviewHandler $overviewHandler,
        private readonly GetAnalyticsSalesHandler $salesHandler,
        private readonly GetAnalyticsPaymentsHandler $paymentsHandler,
        private readonly GetAnalyticsInventoryHandler $inventoryHandler,
        private readonly GetAnalyticsPurchasesHandler $purchasesHandler,
        private readonly GetAnalyticsFinanceHandler $financeHandler,
        private readonly GetAnalyticsClientsHandler $clientsHandler,
        private readonly AnalyticsPermissionFilter $permissionFilter,
    ) {
    }

    #[Route('/overview', name: 'api_analytics_overview', methods: ['GET'])]
    #[IsGranted('analytics.view')]
    public function overview(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->parsePeriod($request);
            $compare = filter_var($request->query->get('compare', 'true'), FILTER_VALIDATE_BOOLEAN);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->overviewHandler->handle(new GetAnalyticsOverviewQuery($from, $to, $compare));

        return $this->json($this->permissionFilter->filterOverview($result, $this->requireUser()));
    }

    #[Route('/sales', name: 'api_analytics_sales', methods: ['GET'])]
    #[IsGranted('analytics.view')]
    public function sales(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewSales($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        try {
            [$from, $to] = $this->parsePeriod($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->salesHandler->handle(new GetAnalyticsSalesQuery($from, $to)));
    }

    #[Route('/payments', name: 'api_analytics_payments', methods: ['GET'])]
    #[IsGranted('analytics.view')]
    public function payments(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewFinance($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        try {
            [$from, $to] = $this->parsePeriod($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->paymentsHandler->handle(new GetAnalyticsPaymentsQuery($from, $to)));
    }

    #[Route('/inventory', name: 'api_analytics_inventory', methods: ['GET'])]
    #[IsGranted('analytics.view')]
    public function inventory(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewInventory($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        try {
            [$from, $to] = $this->parsePeriod($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->inventoryHandler->handle(new GetAnalyticsInventoryQuery($from, $to)));
    }

    #[Route('/purchases', name: 'api_analytics_purchases', methods: ['GET'])]
    #[IsGranted('analytics.view')]
    public function purchases(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewSuppliers($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        try {
            [$from, $to] = $this->parsePeriod($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->purchasesHandler->handle(new GetAnalyticsPurchasesQuery($from, $to)));
    }

    #[Route('/finance', name: 'api_analytics_finance', methods: ['GET'])]
    #[IsGranted('analytics.view')]
    public function finance(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewFinance($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        try {
            [$from, $to] = $this->parsePeriod($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->financeHandler->handle(new GetAnalyticsFinanceQuery($from, $to)));
    }

    #[Route('/clients', name: 'api_analytics_clients', methods: ['GET'])]
    #[IsGranted('analytics.view')]
    public function clients(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewClients($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        try {
            [$from, $to] = $this->parsePeriod($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->clientsHandler->handle(new GetAnalyticsClientsQuery($from, $to)));
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function parsePeriod(Request $request): array
    {
        $fromRaw = $request->query->get('from');
        $toRaw = $request->query->get('to');

        if (null === $fromRaw || '' === $fromRaw || null === $toRaw || '' === $toRaw) {
            throw new \InvalidArgumentException('Both from and to query parameters are required.');
        }

        try {
            $from = new \DateTimeImmutable((string) $fromRaw);
            $to = new \DateTimeImmutable((string) $toRaw);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid date format for from or to.');
        }

        $from = $from->setTime(0, 0, 0);
        $to = $to->setTime(23, 59, 59);

        if ($from > $to) {
            throw new \InvalidArgumentException('from must be before or equal to to.');
        }

        return [$from, $to];
    }

    private function requireUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
