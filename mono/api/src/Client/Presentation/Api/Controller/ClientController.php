<?php

namespace App\Client\Presentation\Api\Controller;

use App\Client\Application\Service\ClientDeletionService;
use App\Client\Domain\Entity\Client;
use App\Client\Domain\Enum\ClientStatus;
use App\Client\Domain\Repository\ClientRepositoryInterface;
use App\Commerce\Application\Service\VenteDetailMapper;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use App\Facturation\Application\Service\CreanceDetailMapper;
use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Enum\CreanceFilterStatus;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Paiement\Application\Service\PaiementSerializer;
use App\Paiement\Domain\Entity\Paiement;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class ClientController extends AbstractController
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly ClientDeletionService $clientDeletionService,
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly VenteDetailMapper $venteDetailMapper,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly CreanceDetailMapper $creanceDetailMapper,
        private readonly PaiementSerializer $paiementSerializer,
    ) {
    }

    #[Route('/clients', name: 'api_clients_list', methods: ['GET'])]
    #[IsGranted('client.clients.view')]
    public function list(): JsonResponse
    {
        return $this->json(array_map([$this, 'serialize'], $this->clientRepository->findAll()));
    }

    #[Route('/clients/{id}', name: 'api_clients_show', methods: ['GET'])]
    #[IsGranted('client.clients.view')]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->serialize($this->getClient($id)));
    }

    #[Route('/clients', name: 'api_clients_create', methods: ['POST'])]
    #[IsGranted('client.clients.create')]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data['name'])) {
            return $this->json(['error' => 'name is required'], Response::HTTP_BAD_REQUEST);
        }

        $client = new Client($data['name']);
        $this->applyData($client, $data);
        $this->clientRepository->save($client);

        return $this->json($this->serialize($client), Response::HTTP_CREATED);
    }

    #[Route('/clients/{id}', name: 'api_clients_update', methods: ['PUT'])]
    #[IsGranted('client.clients.update')]
    public function update(string $id, Request $request): JsonResponse
    {
        $client = $this->getClient($id);
        $data = $request->toArray();
        if (!empty($data['name'])) {
            $client->setName($data['name']);
        }
        $this->applyData($client, $data);
        $this->clientRepository->save($client);

        return $this->json($this->serialize($client));
    }

    #[Route('/clients/{id}', name: 'api_clients_delete', methods: ['DELETE'])]
    #[IsGranted('client.clients.delete')]
    public function delete(string $id): JsonResponse
    {
        $client = $this->getClient($id);
        $mode = $this->clientDeletionService->delete($client);

        return $this->json([
            'id' => $id,
            'mode' => $mode,
        ]);
    }

    #[Route('/clients/{id}/ventes', name: 'api_clients_ventes', methods: ['GET'])]
    #[IsGranted('client.journal.view')]
    public function listVentes(string $id): JsonResponse
    {
        $clientId = Uuid::fromString($id);
        $this->getClient($id);

        return $this->json(array_map(
            fn ($vente) => $this->venteDetailMapper->map($vente)->toArray(),
            $this->venteRepository->findByClientId($clientId),
        ));
    }

    #[Route('/clients/{id}/commandes', name: 'api_clients_commandes', methods: ['GET'])]
    #[IsGranted('client.journal.view')]
    public function listCommandes(string $id): JsonResponse
    {
        $clientId = Uuid::fromString($id);
        $this->getClient($id);

        return $this->json(array_map(
            [$this, 'serializeCommande'],
            $this->commandeRepository->findByClientId($clientId),
        ));
    }

    #[Route('/clients/{id}/factures', name: 'api_clients_factures', methods: ['GET'])]
    #[IsGranted('client.journal.view')]
    public function listFactures(string $id): JsonResponse
    {
        $clientId = Uuid::fromString($id);
        $this->getClient($id);

        return $this->json(array_map(
            [$this, 'serializeFacture'],
            $this->factureRepository->findByClientId($clientId),
        ));
    }

    #[Route('/clients/{id}/creances', name: 'api_clients_creances', methods: ['GET'])]
    #[IsGranted('client.creances.view')]
    public function listCreances(string $id, Request $request): JsonResponse
    {
        $clientId = Uuid::fromString($id);
        $this->getClient($id);

        $status = CreanceFilterStatus::Open;
        if ($request->query->has('status') && '' !== $request->query->get('status')) {
            $status = CreanceFilterStatus::from((string) $request->query->get('status'));
        }

        $items = $this->creanceDetailMapper->mapAll($clientId, $status);

        return $this->json(array_map(
            static fn ($item) => $item->toArray(),
            $items,
        ));
    }

    #[Route('/clients/{id}/paiements', name: 'api_clients_paiements', methods: ['GET'])]
    #[IsGranted('client.journal.view')]
    public function listPaiements(string $id): JsonResponse
    {
        $clientId = Uuid::fromString($id);
        $this->getClient($id);

        return $this->json(array_map(
            [$this, 'serializePaiement'],
            $this->paiementRepository->findByClientId($clientId),
        ));
    }

    private function getClient(string $id): Client
    {
        $client = $this->clientRepository->findById(Uuid::fromString($id));
        if (null === $client) {
            throw $this->createNotFoundException();
        }

        return $client;
    }

    /** @param array<string, mixed> $data */
    private function applyData(Client $client, array $data): void
    {
        if (array_key_exists('phone', $data)) {
            $client->setPhone($data['phone'] !== '' ? $data['phone'] : null);
        }
        if (array_key_exists('email', $data)) {
            $client->setEmail($data['email'] !== '' ? $data['email'] : null);
        }
        if (array_key_exists('credit_limit', $data)) {
            $client->setCreditLimit($data['credit_limit'] !== '' && null !== $data['credit_limit'] ? (string) $data['credit_limit'] : null);
        }
        if (!empty($data['status'])) {
            $client->setStatus(ClientStatus::from($data['status']));
        }
    }

    /** @return array<string, mixed> */
    private function serialize(Client $client): array
    {
        return [
            'id' => (string) $client->getId(),
            'name' => $client->getName(),
            'phone' => $client->getPhone(),
            'email' => $client->getEmail(),
            'status' => $client->getStatus()->value,
            'credit_limit' => $client->getCreditLimit(),
            'created_at' => $client->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $client->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'is_deleted' => $client->isDeleted(),
            'deleted_at' => $client->getDeletedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeCommande(Commande $commande): array
    {
        return [
            'id' => (string) $commande->getId(),
            'reference' => $commande->getReference(),
            'acheteur' => $commande->getAcheteur()->toArray(),
            'status' => $commande->getStatus()->value,
            'total_amount' => $commande->getTotalAmount(),
            'deposit_received' => $commande->getDepositReceived(),
            'confirmed_at' => $commande->getConfirmedAt()?->format(\DateTimeInterface::ATOM),
            'cancelled_at' => $commande->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $commande->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lines' => array_map(static fn ($line) => [
                'variant_id' => (string) $line->getVariantId(),
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getUnitPrice(),
                'line_total' => $line->getLineTotal(),
            ], $commande->getLines()->toArray()),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeFacture(Facture $facture): array
    {
        return [
            'id' => (string) $facture->getId(),
            'numero' => $facture->getNumero(),
            'vente_id' => $facture->getVenteId() ? (string) $facture->getVenteId() : null,
            'commande_id' => $facture->getCommandeId() ? (string) $facture->getCommandeId() : null,
            'source_reference' => $facture->getSourceReference(),
            'origin' => null !== $facture->getVenteId() ? 'vente' : 'commande',
            'acheteur' => [
                'client_id' => $facture->getClientId() ? (string) $facture->getClientId() : null,
                'anonymous_info' => $facture->getAnonymousInfo(),
            ],
            'total_amount' => $facture->getTotalAmount(),
            'issued_at' => $facture->getIssuedAt()->format(\DateTimeInterface::ATOM),
            'is_creance' => $facture->isCreance(),
            'credit_closed_at' => $facture->getCreditClosedAt()?->format(\DateTimeInterface::ATOM),
            'is_cancelled' => $this->isFactureSourceCancelled($facture),
            'lines' => array_map(static fn ($line) => [
                'variant_id' => (string) $line->getVariantId(),
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getUnitPrice(),
                'line_total' => $line->getLineTotal(),
            ], $facture->getLines()->toArray()),
        ];
    }

    private function isFactureSourceCancelled(Facture $facture): bool
    {
        $venteId = $facture->getVenteId();
        if (null === $venteId) {
            return false;
        }

        $vente = $this->venteRepository->findById($venteId);

        return null !== $vente && $vente->isCancelled();
    }

    /** @return array<string, mixed> */
    private function serializePaiement(Paiement $paiement): array
    {
        return $this->paiementSerializer->serialize($paiement);
    }
}
