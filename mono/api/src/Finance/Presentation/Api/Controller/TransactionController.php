<?php

namespace App\Finance\Presentation\Api\Controller;

use App\Finance\Application\Service\TransactionService;
use App\Finance\Domain\Entity\Transaction;
use App\Finance\Domain\Enum\TransactionSourceType;
use App\Finance\Domain\Enum\TransactionType;
use App\Finance\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class TransactionController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionService $transactionService,
    ) {
    }

    #[Route('/transactions', name: 'api_transactions_list', methods: ['GET'])]
    #[IsGranted('finance.transactions.view')]
    public function list(Request $request): JsonResponse
    {
        $compteId = $request->query->get('compte_id');
        $type = $request->query->get('type');
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $transactions = $this->transactionRepository->findAll(
            $compteId ? Uuid::fromString((string) $compteId) : null,
            $type ? TransactionType::from((string) $type) : null,
            $from ? new \DateTimeImmutable((string) $from) : null,
            $to ? new \DateTimeImmutable((string) $to) : null,
        );

        return $this->json(array_map([$this, 'serialize'], $transactions));
    }

    #[Route('/transactions', name: 'api_transactions_create', methods: ['POST'])]
    #[IsGranted('finance.transactions.create')]
    public function create(Request $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->createManual($request->toArray());
        } catch (\InvalidArgumentException | \ValueError $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->serialize($transaction), Response::HTTP_CREATED);
    }

    #[Route('/transactions/{id}/cancel', name: 'api_transactions_cancel', methods: ['POST'])]
    #[IsGranted('finance.transactions.cancel')]
    public function cancel(string $id): JsonResponse
    {
        $transaction = $this->transactionRepository->findById(Uuid::fromString($id));
        if (null === $transaction) {
            throw $this->createNotFoundException();
        }

        if (TransactionSourceType::Manuel !== $transaction->getSourceType()) {
            return $this->json(['error' => 'Only manual transactions can be cancelled from this endpoint.'], Response::HTTP_CONFLICT);
        }

        try {
            $this->transactionService->cancel($transaction);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($this->serialize($transaction));
    }

    /** @return array<string, mixed> */
    private function serialize(Transaction $transaction): array
    {
        return [
            'id' => (string) $transaction->getId(),
            'compte_id' => (string) $transaction->getCompteId(),
            'type' => $transaction->getType()->value,
            'amount' => $transaction->getAmount(),
            'label' => $transaction->getLabel(),
            'description' => $transaction->getDescription(),
            'occurred_at' => $transaction->getOccurredAt()->format(\DateTimeInterface::ATOM),
            'source_type' => $transaction->getSourceType()->value,
            'source_id' => $transaction->getSourceId() ? (string) $transaction->getSourceId() : null,
            'cancelled_at' => $transaction->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            'is_cancelled' => $transaction->isCancelled(),
        ];
    }
}
