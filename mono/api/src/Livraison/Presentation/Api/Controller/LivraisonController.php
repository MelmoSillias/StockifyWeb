<?php

namespace App\Livraison\Presentation\Api\Controller;

use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Livraison\Application\Service\LivraisonService;
use App\Livraison\Domain\Entity\BonDeLivraison;
use App\Livraison\Domain\Repository\BonDeLivraisonRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class LivraisonController extends AbstractController
{
    public function __construct(
        private readonly LivraisonService $livraisonService,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly BonDeLivraisonRepositoryInterface $bonDeLivraisonRepository,
    ) {
    }

    #[Route('/commandes/{id}/reste-a-livrer', name: 'api_commandes_reste_a_livrer', methods: ['GET'])]
    #[IsGranted('commerce.livraisons.view')]
    public function resteALivrer(string $id): JsonResponse
    {
        $commande = $this->getCommande($id);

        return $this->json($this->livraisonService->getResteALivrer($commande));
    }

    #[Route('/commandes/{id}/bons-livraison', name: 'api_commandes_bons_livraison_list', methods: ['GET'])]
    #[IsGranted('commerce.livraisons.view')]
    public function listBonsLivraison(string $id): JsonResponse
    {
        $commande = $this->getCommande($id);

        return $this->json(array_map(
            fn (BonDeLivraison $bon) => $this->serializeBonDeLivraison($bon),
            $this->bonDeLivraisonRepository->findByCommandeId($commande->getId()),
        ));
    }

    #[Route('/commandes/{id}/bons-livraison', name: 'api_commandes_bons_livraison_create', methods: ['POST'])]
    #[IsGranted('commerce.livraisons.create')]
    public function createBonLivraison(string $id, Request $request): JsonResponse
    {
        $commande = $this->getCommande($id);

        try {
            $bon = $this->livraisonService->creerBonDeLivraison($commande, $request->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'bon' => $this->serializeBonDeLivraison($bon),
            'commande_status' => $commande->getStatus()->value,
        ], Response::HTTP_CREATED);
    }

    #[Route('/bons-livraison/{id}', name: 'api_bons_livraison_show', methods: ['GET'])]
    #[IsGranted('commerce.livraisons.view')]
    public function showBonLivraison(string $id): JsonResponse
    {
        $bon = $this->bonDeLivraisonRepository->findById(Uuid::fromString($id));
        if (null === $bon) {
            throw $this->createNotFoundException();
        }

        return $this->json($this->serializeBonDeLivraison($bon));
    }

    #[Route('/bons-livraison/{id}/delivrer', name: 'api_bons_livraison_delivrer', methods: ['POST'])]
    #[IsGranted('commerce.livraisons.deliver')]
    public function delivrerBonLivraison(string $id): JsonResponse
    {
        $bon = $this->bonDeLivraisonRepository->findById(Uuid::fromString($id));
        if (null === $bon) {
            throw $this->createNotFoundException();
        }

        try {
            $this->livraisonService->marquerDelivre($bon);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->serializeBonDeLivraison($bon));
    }

    private function getCommande(string $id): Commande
    {
        $commande = $this->commandeRepository->findById(Uuid::fromString($id));
        if (null === $commande) {
            throw $this->createNotFoundException();
        }

        return $commande;
    }

    /** @return array<string, mixed> */
    private function serializeBonDeLivraison(BonDeLivraison $bon): array
    {
        return [
            'id' => (string) $bon->getId(),
            'reference' => $bon->getReference(),
            'commande_id' => (string) $bon->getCommandeId(),
            'status' => $bon->getStatus()->value,
            'sent_at' => $bon->getSentAt()->format(\DateTimeInterface::ATOM),
            'delivered_at' => $bon->getDeliveredAt()?->format(\DateTimeInterface::ATOM),
            'created_at' => $bon->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lines' => array_map(static fn ($line) => [
                'variant_id' => (string) $line->getVariantId(),
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
            ], $bon->getLines()->toArray()),
        ];
    }
}
