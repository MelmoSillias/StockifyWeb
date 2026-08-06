<?php

namespace App\Fournisseur\Presentation\Api\Controller;

use App\Fournisseur\Application\Service\DetteDetailMapper;
use App\Fournisseur\Application\Service\FournisseurDeletionService;
use App\Fournisseur\Application\Service\PaiementFournisseurSerializer;
use App\Fournisseur\Domain\Entity\CommandeFournisseur;
use App\Fournisseur\Domain\Entity\Fournisseur;
use App\Fournisseur\Domain\Enum\DetteFilterStatus;
use App\Fournisseur\Domain\Enum\FournisseurStatus;
use App\Fournisseur\Domain\Repository\CommandeFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\PaiementFournisseurRepositoryInterface;
use App\Integration\Application\Service\TenantFeatureGuard;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class FournisseurController extends AbstractController
{
    public function __construct(
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
        private readonly FournisseurDeletionService $fournisseurDeletionService,
        private readonly CommandeFournisseurRepositoryInterface $commandeRepository,
        private readonly DetteDetailMapper $detteDetailMapper,
        private readonly PaiementFournisseurRepositoryInterface $paiementFournisseurRepository,
        private readonly PaiementFournisseurSerializer $paiementFournisseurSerializer,
        private readonly TenantFeatureGuard $tenantFeatureGuard,
    ) {
    }

    #[Route('/fournisseurs', name: 'api_fournisseurs_list', methods: ['GET'])]
    #[IsGranted('fournisseur.view')]
    public function list(): JsonResponse
    {
        $this->tenantFeatureGuard->assertFeatureForActiveShop('stockify.suppliers');

        return $this->json(array_map([$this, 'serialize'], $this->fournisseurRepository->findAll()));
    }

    #[Route('/fournisseurs/{id}', name: 'api_fournisseurs_show', methods: ['GET'])]
    #[IsGranted('fournisseur.view')]
    public function show(string $id): JsonResponse
    {
        $this->tenantFeatureGuard->assertFeatureForActiveShop('stockify.suppliers');

        return $this->json($this->serialize($this->getFournisseur($id)));
    }

    #[Route('/fournisseurs', name: 'api_fournisseurs_create', methods: ['POST'])]
    #[IsGranted('fournisseur.manage')]
    public function create(Request $request): JsonResponse
    {
        $this->tenantFeatureGuard->assertFeatureForActiveShop('stockify.suppliers');

        $data = $request->toArray();
        if (empty($data['name'])) {
            return $this->json(['error' => 'name is required'], Response::HTTP_BAD_REQUEST);
        }

        $fournisseur = new Fournisseur($data['name']);
        $this->applyData($fournisseur, $data);
        $this->fournisseurRepository->save($fournisseur);

        return $this->json($this->serialize($fournisseur), Response::HTTP_CREATED);
    }

    #[Route('/fournisseurs/{id}', name: 'api_fournisseurs_update', methods: ['PUT'])]
    #[IsGranted('fournisseur.manage')]
    public function update(string $id, Request $request): JsonResponse
    {
        $fournisseur = $this->getFournisseur($id);
        $data = $request->toArray();
        if (!empty($data['name'])) {
            $fournisseur->setName($data['name']);
        }
        $this->applyData($fournisseur, $data);
        $this->fournisseurRepository->save($fournisseur);

        return $this->json($this->serialize($fournisseur));
    }

    #[Route('/fournisseurs/{id}', name: 'api_fournisseurs_delete', methods: ['DELETE'])]
    #[IsGranted('fournisseur.manage')]
    public function delete(string $id): JsonResponse
    {
        $fournisseur = $this->getFournisseur($id);
        $mode = $this->fournisseurDeletionService->delete($fournisseur);

        return $this->json([
            'id' => $id,
            'mode' => $mode,
        ]);
    }

    #[Route('/fournisseurs/{id}/commandes', name: 'api_fournisseurs_commandes', methods: ['GET'])]
    #[IsGranted('fournisseur.commandes.view')]
    public function listCommandes(string $id): JsonResponse
    {
        $fournisseurId = Uuid::fromString($id);
        $this->getFournisseur($id);

        return $this->json(array_map(
            [$this, 'serializeCommande'],
            $this->commandeRepository->findByFournisseurId($fournisseurId),
        ));
    }

    #[Route('/fournisseurs/{id}/dettes', name: 'api_fournisseurs_dettes', methods: ['GET'])]
    #[IsGranted('fournisseur.dettes.view')]
    public function listDettes(string $id, Request $request): JsonResponse
    {
        $fournisseurId = Uuid::fromString($id);
        $this->getFournisseur($id);

        $status = $this->resolveStatus($request->query->get('status'));
        $items = $this->detteDetailMapper->mapAll($fournisseurId, $status);

        return $this->json(array_map(
            static fn ($item) => $item->toArray(),
            $items,
        ));
    }

    #[Route('/fournisseurs/{id}/paiements', name: 'api_fournisseurs_paiements', methods: ['GET'])]
    #[IsGranted('fournisseur.dettes.view')]
    public function listPaiements(string $id): JsonResponse
    {
        $fournisseurId = Uuid::fromString($id);
        $this->getFournisseur($id);

        return $this->json(array_map(
            [$this->paiementFournisseurSerializer, 'serialize'],
            $this->paiementFournisseurRepository->findByFournisseurId($fournisseurId),
        ));
    }

    private function getFournisseur(string $id): Fournisseur
    {
        $this->tenantFeatureGuard->assertFeatureForActiveShop('stockify.suppliers');

        $fournisseur = $this->fournisseurRepository->findById(Uuid::fromString($id));
        if (null === $fournisseur) {
            throw $this->createNotFoundException();
        }

        return $fournisseur;
    }

    /** @param array<string, mixed> $data */
    private function applyData(Fournisseur $fournisseur, array $data): void
    {
        if (array_key_exists('phone', $data)) {
            $fournisseur->setPhone('' !== ($data['phone'] ?? '') ? $data['phone'] : null);
        }
        if (array_key_exists('email', $data)) {
            $fournisseur->setEmail('' !== ($data['email'] ?? '') ? $data['email'] : null);
        }
        if (!empty($data['status'])) {
            $fournisseur->setStatus(FournisseurStatus::from($data['status']));
        }
    }

    /** @return array<string, mixed> */
    private function serialize(Fournisseur $fournisseur): array
    {
        return [
            'id' => (string) $fournisseur->getId(),
            'name' => $fournisseur->getName(),
            'phone' => $fournisseur->getPhone(),
            'email' => $fournisseur->getEmail(),
            'status' => $fournisseur->getStatus()->value,
            'created_at' => $fournisseur->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $fournisseur->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'is_deleted' => $fournisseur->isDeleted(),
            'deleted_at' => $fournisseur->getDeletedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeCommande(CommandeFournisseur $commande): array
    {
        return [
            'id' => (string) $commande->getId(),
            'reference' => $commande->getReference(),
            'fournisseur_id' => (string) $commande->getFournisseurId(),
            'status' => $commande->getStatus()->value,
            'total_amount' => $commande->getTotalAmount(),
            'deposit_paid' => $commande->getDepositPaid(),
            'confirmed_at' => $commande->getConfirmedAt()?->format(\DateTimeInterface::ATOM),
            'cancelled_at' => $commande->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            'expected_at' => $commande->getExpectedAt()?->format('Y-m-d'),
            'received_at' => $commande->getReceivedAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $commande->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lines' => array_map(static fn ($line) => [
                'variant_id' => (string) $line->getVariantId(),
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'unit_cost' => $line->getUnitCost(),
                'line_total' => $line->getLineTotal(),
            ], $commande->getLines()->toArray()),
        ];
    }

    private function resolveStatus(mixed $value): DetteFilterStatus
    {
        if (null === $value || '' === $value) {
            return DetteFilterStatus::Open;
        }

        return DetteFilterStatus::from((string) $value);
    }
}
