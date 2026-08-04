<?php

namespace App\Fournisseur\Presentation\Api\Controller;

use App\Fournisseur\Application\Service\PaiementFournisseurSerializer;
use App\Fournisseur\Application\Service\PaiementFournisseurService;
use App\Fournisseur\Domain\Repository\PaiementFournisseurRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class PaiementFournisseurController extends AbstractController
{
    public function __construct(
        private readonly PaiementFournisseurService $paiementFournisseurService,
        private readonly PaiementFournisseurRepositoryInterface $paiementFournisseurRepository,
        private readonly PaiementFournisseurSerializer $paiementFournisseurSerializer,
    ) {
    }

    #[Route('/paiements-fournisseur', name: 'api_paiements_fournisseur_list', methods: ['GET'])]
    #[IsGranted('fournisseur.dettes.view')]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            [$this->paiementFournisseurSerializer, 'serialize'],
            $this->paiementFournisseurRepository->findAll(),
        ));
    }

    #[Route('/paiements-fournisseur', name: 'api_paiements_fournisseur_create', methods: ['POST'])]
    #[IsGranted('fournisseur.paiements.create')]
    public function create(Request $request): JsonResponse
    {
        try {
            $paiement = $this->paiementFournisseurService->enregistrer($request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->paiementFournisseurSerializer->serialize($paiement), Response::HTTP_CREATED);
    }

    #[Route('/paiements-fournisseur/{id}/cancel', name: 'api_paiements_fournisseur_cancel', methods: ['POST'])]
    #[IsGranted('fournisseur.paiements.cancel')]
    public function cancel(string $id): JsonResponse
    {
        $paiement = $this->paiementFournisseurRepository->findById(Uuid::fromString($id));
        if (null === $paiement) {
            throw $this->createNotFoundException();
        }

        try {
            $this->paiementFournisseurService->annuler($paiement);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->paiementFournisseurSerializer->serialize($paiement));
    }
}
