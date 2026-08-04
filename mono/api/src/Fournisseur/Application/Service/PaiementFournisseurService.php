<?php

namespace App\Fournisseur\Application\Service;

use App\Finance\Application\Service\FinanceSeedService;
use App\Finance\Application\Service\ModeDePaiementService;
use App\Fournisseur\Domain\Entity\PaiementFournisseur;
use App\Fournisseur\Domain\Event\PaiementFournisseurAnnule;
use App\Fournisseur\Domain\Event\PaiementFournisseurEnregistre;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\PaiementFournisseurRepositoryInterface;
use App\SharedKernel\Application\Event\DomainEventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class PaiementFournisseurService
{
    public function __construct(
        private readonly PaiementFournisseurRepositoryInterface $paiementFournisseurRepository,
        private readonly DetteFournisseurRepositoryInterface $detteRepository,
        private readonly DetteBalanceCalculator $balanceCalculator,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ModeDePaiementService $modeDePaiementService,
        private readonly FinanceSeedService $financeSeedService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enregistrer(array $payload): PaiementFournisseur
    {
        $this->financeSeedService->seedIfEmpty();

        if (empty($payload['dette_fournisseur_id'])) {
            throw new \InvalidArgumentException('dette_fournisseur_id is required.');
        }
        if (empty($payload['amount'])) {
            throw new \InvalidArgumentException('amount is required.');
        }

        $amount = (string) $payload['amount'];
        if (bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException('A payment amount must be positive.');
        }

        $detteId = Uuid::fromString((string) $payload['dette_fournisseur_id']);
        $dette = $this->detteRepository->findById($detteId);
        if (null === $dette) {
            throw new \InvalidArgumentException('Unknown supplier debt.');
        }

        if (null !== $dette->getCreditClosedAt()) {
            throw new \DomainException('Cannot record a payment on a closed debt.');
        }

        $balance = $this->balanceCalculator->computeBalance($detteId, $dette->getTotalAmount());
        if (bccomp($amount, $balance, 2) > 0) {
            throw new \InvalidArgumentException(sprintf(
                'Payment amount exceeds the remaining balance (%s).',
                $balance,
            ));
        }

        $mode = $this->modeDePaiementService->resolveFromPayload($payload);
        $paidAt = $this->parsePaidAt($payload['paid_at'] ?? null);
        if ($paidAt < $dette->getIssuedAt()) {
            $paidAt = $dette->getIssuedAt();
        }

        $paiement = new PaiementFournisseur(
            $detteId,
            $amount,
            $mode->getId(),
            $paidAt,
        );

        $this->paiementFournisseurRepository->save($paiement);

        $this->eventDispatcher->dispatch(new PaiementFournisseurEnregistre(
            $paiement->getId(),
            $detteId,
            $mode->getId(),
            $amount,
        ));

        return $paiement;
    }

    public function annuler(PaiementFournisseur $paiement): void
    {
        $paiement->cancel();
        $this->paiementFournisseurRepository->save($paiement);

        $this->eventDispatcher->dispatch(new PaiementFournisseurAnnule(
            $paiement->getId(),
            $paiement->getDetteFournisseurId(),
            $paiement->getAmount(),
        ));
    }

    private function parsePaidAt(mixed $value): \DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return new \DateTimeImmutable();
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid paid_at datetime.');
        }
    }
}
