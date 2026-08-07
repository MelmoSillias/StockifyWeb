<?php

namespace App\Integration\Presentation\Api\Controller;

use App\Integration\Application\Command\ActivateAccount\ActivateAccountCommand;
use App\Integration\Application\Command\ActivateAccount\ActivateAccountHandler;
use App\Integration\Application\Command\CreateTenantShop\CreateTenantShopCommand;
use App\Integration\Application\Command\CreateTenantShop\CreateTenantShopHandler;
use App\Integration\Application\Command\InviteTenantUser\InviteTenantUserCommand;
use App\Integration\Application\Command\InviteTenantUser\InviteTenantUserHandler;
use App\Integration\Application\Command\DeleteAccount\DeleteAccountCommand;
use App\Integration\Application\Command\DeleteAccount\DeleteAccountHandler;
use App\Integration\Application\Command\ProvisionAccount\ProvisionAccountCommand;
use App\Integration\Application\Command\ProvisionAccount\ProvisionAccountHandler;
use App\Integration\Application\Command\SuspendAccount\SuspendAccountCommand;
use App\Integration\Application\Command\SuspendAccount\SuspendAccountHandler;
use App\Integration\Application\Command\SyncIdentityState\SyncIdentityStateCommand;
use App\Integration\Application\Command\SyncIdentityState\SyncIdentityStateHandler;
use App\Integration\Application\Command\UpdateEntitlements\UpdateEntitlementsCommand;
use App\Integration\Application\Command\UpdateEntitlements\UpdateEntitlementsHandler;
use App\Integration\Application\Query\GetAccountUsage\GetAccountUsageHandler;
use App\Integration\Application\Query\GetAccountUsage\GetAccountUsageQuery;
use App\Integration\Application\Service\IntegrationCapabilitiesProvider;
use App\Integration\Application\Service\IntegrationRequestLogger;
use App\Integration\Application\Service\UsageWebhookDispatcher;
use App\Integration\Domain\Entity\TenantAccount;
use App\Integration\Domain\Repository\TenantAccountRepositoryInterface;
use App\Integration\Security\IntegrationJwtValidator;
use App\Integration\Security\IntegrationTokenClaims;
use App\Shop\Domain\Entity\Shop;
use App\Shop\Domain\Repository\ShopRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/integration/v1')]
final class IntegrationV1Controller extends AbstractController
{
    public function __construct(
        private readonly IntegrationJwtValidator $jwtValidator,
        private readonly IntegrationCapabilitiesProvider $capabilitiesProvider,
        private readonly TenantAccountRepositoryInterface $tenantAccountRepository,
        private readonly ProvisionAccountHandler $provisionAccountHandler,
        private readonly UpdateEntitlementsHandler $updateEntitlementsHandler,
        private readonly SuspendAccountHandler $suspendAccountHandler,
        private readonly ActivateAccountHandler $activateAccountHandler,
        private readonly DeleteAccountHandler $deleteAccountHandler,
        private readonly GetAccountUsageHandler $getAccountUsageHandler,
        private readonly CreateTenantShopHandler $createTenantShopHandler,
        private readonly InviteTenantUserHandler $inviteTenantUserHandler,
        private readonly SyncIdentityStateHandler $syncIdentityStateHandler,
        private readonly UsageWebhookDispatcher $usageWebhookDispatcher,
        private readonly IntegrationRequestLogger $requestLogger,
        private readonly ShopRepositoryInterface $shopRepository,
    ) {
    }

    #[Route('/health', name: 'integration_v1_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'integration_enabled' => $this->jwtValidator->isEnabled(),
        ]);
    }

    #[Route('/capabilities', name: 'integration_v1_capabilities', methods: ['GET'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_READ)]
    public function capabilities(): JsonResponse
    {
        return $this->json(['data' => $this->capabilitiesProvider->getCapabilities()]);
    }

    #[Route('/accounts', name: 'integration_v1_accounts_create', methods: ['POST'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_WRITE)]
    public function provisionAccount(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $payload = $this->decodePayload($request);
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        if (null !== $idempotencyKey && '' !== trim($idempotencyKey)) {
            $cached = $this->requestLogger->findIdempotentResponse(
                $idempotencyKey,
                $request->getMethod(),
                $request->getPathInfo(),
            );
            if (null !== $cached) {
                return $this->json(
                    ['data' => $cached->getResponseBody()],
                    $cached->getResponseStatus() ?? Response::HTTP_OK,
                );
            }
        }

        $log = $this->requestLogger->start(
            method: $request->getMethod(),
            path: $request->getPathInfo(),
            externalAccountId: isset($payload['external_account_id']) ? (string) $payload['external_account_id'] : null,
            idempotencyKey: $idempotencyKey,
            requestSummary: $payload,
        );

        try {
            $account = $this->provisionAccountHandler->handle(
                ProvisionAccountCommand::fromPayload($payload, $idempotencyKey),
            );
            $data = $this->serializeAccount($account);
            $status = Response::HTTP_CREATED;
            $this->requestLogger->complete($log, $status, $data, $this->durationMs($startedAt));

            return $this->json(['data' => $data], $status);
        } catch (\InvalidArgumentException $exception) {
            $this->requestLogger->complete($log, Response::HTTP_BAD_REQUEST, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/accounts/{externalAccountId}', name: 'integration_v1_accounts_get', methods: ['GET'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_READ)]
    public function getAccount(string $externalAccountId, Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $log = $this->requestLogger->start($request->getMethod(), $request->getPathInfo(), $externalAccountId);

        $account = $this->tenantAccountRepository->findByExternalAccountId($externalAccountId);
        if (null === $account) {
            $this->requestLogger->complete($log, Response::HTTP_NOT_FOUND, ['error' => 'Tenant account not found.'], $this->durationMs($startedAt));

            return $this->json(['error' => 'Tenant account not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->serializeAccount($account);
        $this->requestLogger->complete($log, Response::HTTP_OK, $data, $this->durationMs($startedAt));

        return $this->json(['data' => $data]);
    }

    #[Route('/accounts/{externalAccountId}/entitlements', name: 'integration_v1_accounts_entitlements', methods: ['PATCH'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_WRITE)]
    public function updateEntitlements(string $externalAccountId, Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $payload = $this->decodePayload($request);
        $log = $this->requestLogger->start($request->getMethod(), $request->getPathInfo(), $externalAccountId, null, $payload);

        try {
            $account = $this->updateEntitlementsHandler->handle(
                UpdateEntitlementsCommand::fromPayload($externalAccountId, $payload),
            );
            $data = $this->serializeAccount($account);
            $this->requestLogger->complete($log, Response::HTTP_OK, $data, $this->durationMs($startedAt));

            return $this->json(['data' => $data]);
        } catch (\InvalidArgumentException $exception) {
            $this->requestLogger->complete($log, Response::HTTP_BAD_REQUEST, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/accounts/{externalAccountId}/suspend', name: 'integration_v1_accounts_suspend', methods: ['POST'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_WRITE)]
    public function suspendAccount(string $externalAccountId, Request $request): JsonResponse
    {
        return $this->mutateAccount(
            $request,
            $externalAccountId,
            fn () => $this->suspendAccountHandler->handle(new SuspendAccountCommand($externalAccountId)),
        );
    }

    #[Route('/accounts/{externalAccountId}/activate', name: 'integration_v1_accounts_activate', methods: ['POST'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_WRITE)]
    public function activateAccount(string $externalAccountId, Request $request): JsonResponse
    {
        return $this->mutateAccount(
            $request,
            $externalAccountId,
            fn () => $this->activateAccountHandler->handle(new ActivateAccountCommand($externalAccountId)),
        );
    }

    #[Route('/accounts/{externalAccountId}', name: 'integration_v1_accounts_delete', methods: ['DELETE'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_WRITE)]
    public function deleteAccount(string $externalAccountId, Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $mode = (string) $request->query->get('mode', DeleteAccountCommand::MODE_GUARD);
        $log = $this->requestLogger->start($request->getMethod(), $request->getPathInfo(), $externalAccountId);

        try {
            $result = $this->deleteAccountHandler->handle(new DeleteAccountCommand($externalAccountId, $mode));
            if (Response::HTTP_ACCEPTED === $result->statusCode) {
                $payload = ['deletion_receipt' => $result->deletionReceipt];
                $this->requestLogger->complete($log, Response::HTTP_ACCEPTED, $payload, $this->durationMs($startedAt));

                return $this->json($payload, Response::HTTP_ACCEPTED);
            }

            $this->requestLogger->complete($log, Response::HTTP_NO_CONTENT, null, $this->durationMs($startedAt));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $exception) {
            $status = str_contains(strtolower($exception->getMessage()), 'not found')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_BAD_REQUEST;
            $this->requestLogger->complete($log, $status, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], $status);
        } catch (\DomainException $exception) {
            $status = str_contains(strtolower($exception->getMessage()), 'disabled')
                ? Response::HTTP_FORBIDDEN
                : Response::HTTP_CONFLICT;
            $this->requestLogger->complete($log, $status, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], $status);
        }
    }

    #[Route('/accounts/{externalAccountId}/usage', name: 'integration_v1_accounts_usage', methods: ['GET'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_READ)]
    public function getUsage(string $externalAccountId, Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $log = $this->requestLogger->start($request->getMethod(), $request->getPathInfo(), $externalAccountId);

        try {
            $data = $this->getAccountUsageHandler->handle(new GetAccountUsageQuery($externalAccountId));
            $this->requestLogger->complete($log, Response::HTTP_OK, $data, $this->durationMs($startedAt));

            return $this->json(['data' => $data]);
        } catch (\InvalidArgumentException $exception) {
            $this->requestLogger->complete($log, Response::HTTP_NOT_FOUND, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/accounts/{externalAccountId}/shops', name: 'integration_v1_accounts_shops_create', methods: ['POST'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_WRITE)]
    public function createShop(string $externalAccountId, Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $payload = $this->decodePayload($request);
        $idempotencyKey = $request->headers->get('Idempotency-Key');

        if (null !== $idempotencyKey && '' !== trim($idempotencyKey)) {
            $cached = $this->requestLogger->findIdempotentResponse(
                $idempotencyKey,
                $request->getMethod(),
                $request->getPathInfo(),
            );
            if (null !== $cached) {
                return $this->json(
                    ['data' => $cached->getResponseBody()],
                    $cached->getResponseStatus() ?? Response::HTTP_OK,
                );
            }
        }

        $log = $this->requestLogger->start(
            method: $request->getMethod(),
            path: $request->getPathInfo(),
            externalAccountId: $externalAccountId,
            idempotencyKey: $idempotencyKey,
            requestSummary: $payload,
        );

        try {
            $result = $this->createTenantShopHandler->handle(
                CreateTenantShopCommand::fromPayload($externalAccountId, $payload),
            );
            $this->shopRepository->save($result->shop);
            $data = $this->serializeShop($result->shop);
            if (null !== $result->adminEmail || null !== $result->adminUsername) {
                $data['admin'] = [
                    'email' => $result->adminEmail,
                    'username' => $result->adminUsername,
                ];
                if (null !== $result->temporaryPassword) {
                    $data['admin']['temporary_password'] = $result->temporaryPassword;
                }
            }
            $usage = $this->getAccountUsageHandler->handle(new GetAccountUsageQuery($externalAccountId));
            $this->usageWebhookDispatcher->dispatchUsageUpdated($externalAccountId, [
                'shops_count' => (int) ($usage['shops_count'] ?? 0),
                'users_count' => (int) ($usage['users_count'] ?? 0),
            ]);
            $this->requestLogger->complete($log, Response::HTTP_CREATED, $data, $this->durationMs($startedAt));

            return $this->json(['data' => $data], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            $this->requestLogger->complete($log, Response::HTTP_BAD_REQUEST, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $exception) {
            $this->requestLogger->complete($log, Response::HTTP_CONFLICT, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/accounts/{externalAccountId}/users/invite', name: 'integration_v1_accounts_users_invite', methods: ['POST'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_WRITE)]
    public function inviteUser(string $externalAccountId, Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $payload = $this->decodePayload($request);
        $log = $this->requestLogger->start($request->getMethod(), $request->getPathInfo(), $externalAccountId, null, $payload);

        try {
            $result = $this->inviteTenantUserHandler->handle(
                InviteTenantUserCommand::fromPayload($externalAccountId, $payload),
            );
            $data = [
                'user_id' => $result->userId,
                'email' => $result->email,
                'temporary_password' => $result->temporaryPassword,
            ];
            $this->requestLogger->complete($log, Response::HTTP_CREATED, $data, $this->durationMs($startedAt));

            return $this->json(['data' => $data], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            $this->requestLogger->complete($log, Response::HTTP_BAD_REQUEST, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $exception) {
            $this->requestLogger->complete($log, Response::HTTP_CONFLICT, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/identities/{identityId}', name: 'integration_v1_identities_sync', methods: ['PATCH'])]
    #[IsGranted(IntegrationTokenClaims::ROLE_WRITE)]
    public function syncIdentityState(string $identityId, Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        $payload = $this->decodePayload($request);
        $log = $this->requestLogger->start($request->getMethod(), $request->getPathInfo(), null, null, $payload);

        try {
            $this->syncIdentityStateHandler->handle(
                SyncIdentityStateCommand::fromPayload($identityId, $payload),
            );
            $data = [
                'identity_id' => $identityId,
                'email_verified_at' => $payload['email_verified_at'] ?? null,
            ];
            $this->requestLogger->complete($log, Response::HTTP_OK, $data, $this->durationMs($startedAt));

            return $this->json(['data' => $data]);
        } catch (\InvalidArgumentException $exception) {
            $status = str_contains(strtolower($exception->getMessage()), 'not found')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_BAD_REQUEST;
            $this->requestLogger->complete($log, $status, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], $status);
        }
    }

    /** @return array<string, mixed> */
    private function serializeAccount(TenantAccount $account): array
    {
        return [
            'id' => (string) $account->getId(),
            'external_account_id' => $account->getExternalAccountId(),
            'status' => $account->getStatus()->value,
            'entitlements' => $account->getEntitlementsSnapshot(),
            'provisioned_at' => $account->getProvisionedAt()?->format(\DateTimeInterface::ATOM),
            'last_synced_at' => $account->getLastSyncedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $account->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $account->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeShop(Shop $shop): array
    {
        return [
            'id' => (string) $shop->getId(),
            'tenant_account_id' => null !== $shop->getTenantAccountId() ? (string) $shop->getTenantAccountId() : null,
            'name' => $shop->getName(),
            'slug' => $shop->getSlug(),
            'status' => $shop->getStatus()->value,
            'currency' => $shop->getCurrency(),
            'address' => $shop->getAddress(),
            'phone' => $shop->getPhone(),
            'created_at' => $shop->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $shop->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function decodePayload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Invalid JSON body.');
        }

        return $payload;
    }

    private function mutateAccount(Request $request, string $externalAccountId, callable $handler): JsonResponse
    {
        $startedAt = microtime(true);
        $log = $this->requestLogger->start($request->getMethod(), $request->getPathInfo(), $externalAccountId);

        try {
            /** @var TenantAccount $account */
            $account = $handler();
            $data = $this->serializeAccount($account);
            $this->requestLogger->complete($log, Response::HTTP_OK, $data, $this->durationMs($startedAt));

            return $this->json(['data' => $data]);
        } catch (\InvalidArgumentException $exception) {
            $this->requestLogger->complete($log, Response::HTTP_NOT_FOUND, ['error' => $exception->getMessage()], $this->durationMs($startedAt));

            return $this->json(['error' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
