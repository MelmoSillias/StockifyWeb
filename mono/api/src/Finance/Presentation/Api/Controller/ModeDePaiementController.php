<?php

namespace App\Finance\Presentation\Api\Controller;

use App\Finance\Application\Service\ModeDePaiementService;
use App\Finance\Domain\Entity\ModeDePaiement;
use App\Finance\Domain\Repository\ModeDePaiementRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class ModeDePaiementController extends AbstractController
{
    public function __construct(
        private readonly ModeDePaiementRepositoryInterface $modeDePaiementRepository,
        private readonly ModeDePaiementService $modeDePaiementService,
    ) {
    }

    #[Route('/modes-de-paiement', name: 'api_modes_de_paiement_list', methods: ['GET'])]
    #[IsGranted('finance.view')]
    public function list(Request $request): JsonResponse
    {
        $activeOnly = filter_var($request->query->get('active_only', false), FILTER_VALIDATE_BOOL);
        $modes = $activeOnly
            ? $this->modeDePaiementRepository->findActive()
            : $this->modeDePaiementRepository->findAll();

        return $this->json(array_map([$this, 'serialize'], $modes));
    }

    #[Route('/modes-de-paiement/{id}', name: 'api_modes_de_paiement_show', methods: ['GET'])]
    #[IsGranted('finance.view')]
    public function show(string $id): JsonResponse
    {
        return $this->json($this->serialize($this->getMode($id)));
    }

    #[Route('/modes-de-paiement', name: 'api_modes_de_paiement_create', methods: ['POST'])]
    #[IsGranted('finance.modes.manage')]
    public function create(Request $request): JsonResponse
    {
        try {
            $mode = $this->modeDePaiementService->create($request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serialize($mode), Response::HTTP_CREATED);
    }

    #[Route('/modes-de-paiement/{id}', name: 'api_modes_de_paiement_update', methods: ['PUT'])]
    #[IsGranted('finance.modes.manage')]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $mode = $this->modeDePaiementService->update($this->getMode($id), $request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serialize($mode));
    }

    #[Route('/modes-de-paiement/{id}', name: 'api_modes_de_paiement_delete', methods: ['DELETE'])]
    #[IsGranted('finance.modes.manage')]
    public function delete(string $id): JsonResponse
    {
        $mode = $this->getMode($id);

        try {
            $this->modeDePaiementService->delete($mode);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function getMode(string $id): ModeDePaiement
    {
        $mode = $this->modeDePaiementRepository->findById(Uuid::fromString($id));
        if (null === $mode) {
            throw $this->createNotFoundException();
        }

        return $mode;
    }

    /** @return array<string, mixed> */
    private function serialize(ModeDePaiement $mode): array
    {
        return [
            'id' => (string) $mode->getId(),
            'code' => $mode->getCode(),
            'label' => $mode->getLabel(),
            'compte_id' => (string) $mode->getCompteId(),
            'is_active' => $mode->isActive(),
            'generates_transaction' => $mode->generatesTransaction(),
            'created_at' => $mode->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $mode->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
