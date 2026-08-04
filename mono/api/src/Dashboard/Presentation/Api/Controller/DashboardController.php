<?php

namespace App\Dashboard\Presentation\Api\Controller;

use App\Dashboard\Application\Query\GetDashboardFeed\GetDashboardFeedHandler;
use App\Dashboard\Application\Query\GetDashboardFeed\GetDashboardFeedQuery;
use App\Dashboard\Application\Query\GetDashboardFinanceSummary\GetDashboardFinanceSummaryHandler;
use App\Dashboard\Application\Query\GetDashboardFinanceSummary\GetDashboardFinanceSummaryQuery;
use App\Dashboard\Application\Query\GetDashboardPendingDeliveries\GetDashboardPendingDeliveriesHandler;
use App\Dashboard\Application\Query\GetDashboardPendingDeliveries\GetDashboardPendingDeliveriesQuery;
use App\Dashboard\Application\Query\GetDashboardPendingSupplierOrders\GetDashboardPendingSupplierOrdersHandler;
use App\Dashboard\Application\Query\GetDashboardPendingSupplierOrders\GetDashboardPendingSupplierOrdersQuery;
use App\Dashboard\Application\Query\GetDashboardRecentAudit\GetDashboardRecentAuditHandler;
use App\Dashboard\Application\Query\GetDashboardRecentAudit\GetDashboardRecentAuditQuery;
use App\Dashboard\Application\Query\GetDashboardSalesTrend\GetDashboardSalesTrendHandler;
use App\Dashboard\Application\Query\GetDashboardSalesTrend\GetDashboardSalesTrendQuery;
use App\Dashboard\Application\Query\GetDashboardSummary\GetDashboardSummaryHandler;
use App\Dashboard\Application\Query\GetDashboardSummary\GetDashboardSummaryQuery;
use App\Dashboard\Application\Service\DashboardPermissionFilter;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly GetDashboardSummaryHandler $summaryHandler,
        private readonly GetDashboardFeedHandler $feedHandler,
        private readonly GetDashboardSalesTrendHandler $salesTrendHandler,
        private readonly GetDashboardPendingDeliveriesHandler $pendingDeliveriesHandler,
        private readonly GetDashboardPendingSupplierOrdersHandler $pendingSupplierOrdersHandler,
        private readonly GetDashboardFinanceSummaryHandler $financeSummaryHandler,
        private readonly GetDashboardRecentAuditHandler $recentAuditHandler,
        private readonly DashboardPermissionFilter $permissionFilter,
    ) {
    }

    #[Route('/summary', name: 'api_dashboard_summary', methods: ['GET'])]
    #[IsGranted('dashboard.view')]
    public function summary(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->parsePeriod($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->summaryHandler->handle(new GetDashboardSummaryQuery($from, $to));

        return $this->json($this->permissionFilter->filterSummary($result->toArray(), $this->requireUser()));
    }

    #[Route('/feed', name: 'api_dashboard_feed', methods: ['GET'])]
    #[IsGranted('dashboard.view')]
    public function feed(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->parsePeriod($request);
            $limit = max(1, min(20, (int) $request->query->get('limit', 5)));
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->feedHandler->handle(new GetDashboardFeedQuery($from, $to, $limit));

        return $this->json($this->permissionFilter->filterFeed($result->toArray(), $this->requireUser()));
    }

    #[Route('/sales-trend', name: 'api_dashboard_sales_trend', methods: ['GET'])]
    #[IsGranted('dashboard.view')]
    public function salesTrend(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewSalesTrend($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        try {
            [$from, $to] = $this->parsePeriod($request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->salesTrendHandler->handle(new GetDashboardSalesTrendQuery($from, $to));

        return $this->json($result->toArray());
    }

    #[Route('/pending-deliveries', name: 'api_dashboard_pending_deliveries', methods: ['GET'])]
    #[IsGranted('dashboard.view')]
    public function pendingDeliveries(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewPendingDeliveries($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $limit = max(1, min(50, (int) $request->query->get('limit', 10)));
        $items = $this->pendingDeliveriesHandler->handle(new GetDashboardPendingDeliveriesQuery($limit));

        return $this->json(['items' => $items]);
    }

    #[Route('/pending-supplier-orders', name: 'api_dashboard_pending_supplier_orders', methods: ['GET'])]
    #[IsGranted('dashboard.view')]
    public function pendingSupplierOrders(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewPendingSupplierOrders($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $limit = max(1, min(50, (int) $request->query->get('limit', 10)));
        $items = $this->pendingSupplierOrdersHandler->handle(new GetDashboardPendingSupplierOrdersQuery($limit));

        return $this->json(['items' => $items]);
    }

    #[Route('/finance-summary', name: 'api_dashboard_finance_summary', methods: ['GET'])]
    #[IsGranted('dashboard.view')]
    public function financeSummary(): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewFinanceSummary($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $result = $this->financeSummaryHandler->handle(new GetDashboardFinanceSummaryQuery(), $user);

        return $this->json($this->permissionFilter->filterFinanceSummary($result->toArray(), $user));
    }

    #[Route('/recent-audit', name: 'api_dashboard_recent_audit', methods: ['GET'])]
    #[IsGranted('dashboard.view')]
    public function recentAudit(Request $request): JsonResponse
    {
        $user = $this->requireUser();
        if (!$this->permissionFilter->canViewRecentAudit($user)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $limit = max(1, min(20, (int) $request->query->get('limit', 5)));
        $items = $this->recentAuditHandler->handle(new GetDashboardRecentAuditQuery($limit));

        return $this->json(['items' => $items]);
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
