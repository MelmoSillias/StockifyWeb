<?php

namespace App\Commerce\Presentation\Api\Controller;

use App\Commerce\Application\Service\CommandeDetailMapper;
use App\Commerce\Application\Service\CommerceService;
use App\Commerce\Application\Service\VenteDetailMapper;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class CommerceController extends AbstractController
{
    public function __construct(
        private readonly CommerceService $commerceService,
        private readonly VenteDetailMapper $venteDetailMapper,
        private readonly CommandeDetailMapper $commandeDetailMapper,
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
    ) {
    }

    #[Route('/ventes', name: 'api_ventes_list', methods: ['GET'])]
    #[IsGranted('commerce.ventes.view')]
    public function listVentes(): JsonResponse
    {
        return $this->json(array_map(
            fn ($vente) => $this->venteDetailMapper->map($vente)->toArray(),
            $this->venteRepository->findAll(),
        ));
    }

    #[Route('/ventes/{id}', name: 'api_ventes_show', methods: ['GET'])]
    #[IsGranted('commerce.ventes.view')]
    public function showVente(string $id): JsonResponse
    {
        $vente = $this->venteRepository->findById(Uuid::fromString($id));
        if (null === $vente) {
            throw $this->createNotFoundException();
        }

        return $this->json($this->venteDetailMapper->map($vente)->toArray());
    }

    #[Route('/ventes', name: 'api_ventes_create', methods: ['POST'])]
    #[IsGranted('commerce.ventes.create')]
    public function createVente(Request $request): JsonResponse
    {
        try {
            $vente = $this->commerceService->realiserVente($request->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->venteDetailMapper->map($vente)->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/ventes/{id}/cancel', name: 'api_ventes_cancel', methods: ['POST'])]
    #[IsGranted('commerce.ventes.cancel')]
    public function cancelVente(string $id): JsonResponse
    {
        $vente = $this->venteRepository->findById(Uuid::fromString($id));
        if (null === $vente) {
            throw $this->createNotFoundException();
        }

        try {
            $this->commerceService->annulerVente($vente);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->venteDetailMapper->map($vente)->toArray());
    }

    #[Route('/commandes', name: 'api_commandes_list', methods: ['GET'])]
    #[IsGranted('commerce.commandes.view')]
    public function listCommandes(): JsonResponse
    {
        return $this->json(array_map(
            fn ($commande) => $this->commandeDetailMapper->map($commande)->toArray(),
            $this->commandeRepository->findAll(),
        ));
    }

    #[Route('/commandes/{id}', name: 'api_commandes_show', methods: ['GET'])]
    #[IsGranted('commerce.commandes.view')]
    public function showCommande(string $id): JsonResponse
    {
        return $this->json($this->commandeDetailMapper->map($this->getCommande($id))->toArray());
    }

    #[Route('/commandes', name: 'api_commandes_create', methods: ['POST'])]
    #[IsGranted('commerce.commandes.create')]
    public function createCommande(Request $request): JsonResponse
    {
        try {
            $commande = $this->commerceService->initierCommande($request->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->commandeDetailMapper->map($commande)->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/commandes/{id}/confirm', name: 'api_commandes_confirm', methods: ['POST'])]
    #[IsGranted('commerce.commandes.confirm')]
    public function confirmCommande(string $id, Request $request): JsonResponse
    {
        $commande = $this->getCommande($id);
        try {
            $this->commerceService->confirmerCommande($commande, $request->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->commandeDetailMapper->map($commande)->toArray());
    }

    #[Route('/commandes/{id}/cancel', name: 'api_commandes_cancel', methods: ['POST'])]
    #[IsGranted('commerce.commandes.cancel')]
    public function cancelCommande(string $id): JsonResponse
    {
        $commande = $this->getCommande($id);
        try {
            $this->commerceService->annulerCommande($commande);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->commandeDetailMapper->map($commande)->toArray());
    }

    private function getCommande(string $id): Commande
    {
        $commande = $this->commandeRepository->findById(Uuid::fromString($id));
        if (null === $commande) {
            throw $this->createNotFoundException();
        }

        return $commande;
    }
}
