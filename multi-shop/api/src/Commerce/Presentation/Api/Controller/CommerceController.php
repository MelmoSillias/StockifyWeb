<?php

namespace App\Commerce\Presentation\Api\Controller;

use App\Commerce\Application\Service\CommandeDetailMapper;
use App\Commerce\Application\Service\CommerceService;
use App\Commerce\Application\Service\DevisDetailMapper;
use App\Commerce\Application\Service\VenteDetailMapper;
use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Entity\Devis;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Commerce\Domain\Repository\DevisRepositoryInterface;
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
        private readonly DevisDetailMapper $devisDetailMapper,
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly DevisRepositoryInterface $devisRepository,
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

    #[Route('/devis', name: 'api_devis_list', methods: ['GET'])]
    #[IsGranted('commerce.devis.view')]
    public function listDevis(): JsonResponse
    {
        return $this->json(array_map(
            fn ($devis) => $this->devisDetailMapper->map($devis)->toArray(),
            $this->devisRepository->findAll(),
        ));
    }

    #[Route('/devis/{id}', name: 'api_devis_show', methods: ['GET'])]
    #[IsGranted('commerce.devis.view')]
    public function showDevis(string $id): JsonResponse
    {
        return $this->json($this->devisDetailMapper->map($this->getDevis($id))->toArray());
    }

    #[Route('/devis', name: 'api_devis_create', methods: ['POST'])]
    #[IsGranted('commerce.devis.create')]
    public function createDevis(Request $request): JsonResponse
    {
        try {
            $devis = $this->commerceService->creerDevis($request->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->devisDetailMapper->map($devis)->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/devis/{id}/cancel', name: 'api_devis_cancel', methods: ['POST'])]
    #[IsGranted('commerce.devis.cancel')]
    public function cancelDevis(string $id): JsonResponse
    {
        $devis = $this->getDevis($id);
        try {
            $this->commerceService->annulerDevis($devis);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->devisDetailMapper->map($devis)->toArray());
    }

    #[Route('/devis/{id}/convert/vente', name: 'api_devis_convert_vente', methods: ['POST'])]
    #[IsGranted('commerce.devis.convert')]
    #[IsGranted('commerce.ventes.create')]
    public function convertDevisToVente(string $id, Request $request): JsonResponse
    {
        $devis = $this->getDevis($id);
        try {
            $vente = $this->commerceService->convertirDevisEnVente($devis, $request->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'devis' => $this->devisDetailMapper->map($devis)->toArray(),
            'vente' => $this->venteDetailMapper->map($vente)->toArray(),
        ]);
    }

    #[Route('/devis/{id}/convert/commande', name: 'api_devis_convert_commande', methods: ['POST'])]
    #[IsGranted('commerce.devis.convert')]
    #[IsGranted('commerce.commandes.create')]
    public function convertDevisToCommande(string $id, Request $request): JsonResponse
    {
        $devis = $this->getDevis($id);
        try {
            $commande = $this->commerceService->convertirDevisEnCommande($devis, $request->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'devis' => $this->devisDetailMapper->map($devis)->toArray(),
            'commande' => $this->commandeDetailMapper->map($commande)->toArray(),
        ]);
    }

    private function getCommande(string $id): Commande
    {
        $commande = $this->commandeRepository->findById(Uuid::fromString($id));
        if (null === $commande) {
            throw $this->createNotFoundException();
        }

        return $commande;
    }

    private function getDevis(string $id): Devis
    {
        $devis = $this->devisRepository->findById(Uuid::fromString($id));
        if (null === $devis) {
            throw $this->createNotFoundException();
        }

        return $devis;
    }
}
