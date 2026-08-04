<?php

namespace App\Paiement\Application\Service;

use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Enum\CommandeStatus;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Commerce\Domain\Repository\VenteRepositoryInterface;
use App\Facturation\Application\Service\FactureBalanceCalculator;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Finance\Application\Service\FinanceSeedService;
use App\Finance\Application\Service\ModeDePaiementService;
use App\Paiement\Domain\Entity\Paiement;
use App\Paiement\Domain\Event\PaiementAnnule;
use App\Paiement\Domain\Event\PaiementEnregistre;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use App\SharedKernel\Application\Event\DomainEventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class PaiementService
{
    public function __construct(
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly VenteRepositoryInterface $venteRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly FactureBalanceCalculator $balanceCalculator,
        private readonly ModeDePaiementService $modeDePaiementService,
        private readonly FinanceSeedService $financeSeedService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enregistrer(array $payload): Paiement
    {
        $this->financeSeedService->seedIfEmpty();

        if (empty($payload['amount'])) {
            throw new \InvalidArgumentException('amount is required.');
        }

        $amount = (string) $payload['amount'];
        if (bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException('A payment amount must be positive.');
        }

        $mode = $this->modeDePaiementService->resolveFromPayload($payload);

        $factureId = !empty($payload['facture_id']) ? Uuid::fromString((string) $payload['facture_id']) : null;
        $commandeId = !empty($payload['commande_id']) ? Uuid::fromString((string) $payload['commande_id']) : null;

        if (null !== $factureId) {
            $this->assertFacturePaymentAllowed($factureId, $amount);
        } elseif (null !== $commandeId) {
            $this->assertCommandeDepositAllowed($commandeId, $amount);
        } else {
            throw new \InvalidArgumentException('facture_id or commande_id is required.');
        }

        $operationDate = $this->resolveOperationDate($factureId, $commandeId);
        $paidAt = $this->parsePaidAt($payload['paid_at'] ?? null);
        if ($paidAt < $operationDate) {
            $paidAt = $operationDate;
        }

        $paiement = new Paiement(
            $amount,
            $mode->getId(),
            $factureId,
            $commandeId,
            $paidAt,
        );

        $this->paiementRepository->save($paiement);

        $this->eventDispatcher->dispatch(new PaiementEnregistre(
            $paiement->getId(),
            $paiement->getModeDePaiementId(),
            $paiement->getFactureId(),
            $paiement->getCommandeId(),
            $paiement->getAmount(),
        ));

        return $paiement;
    }

    public function annuler(Paiement $paiement): void
    {
        $paiement->cancel();
        $this->paiementRepository->save($paiement);

        $this->eventDispatcher->dispatch(new PaiementAnnule(
            $paiement->getId(),
            $paiement->getFactureId(),
            $paiement->getCommandeId(),
            $paiement->getAmount(),
        ));
    }

    private function assertFacturePaymentAllowed(Uuid $factureId, string $amount): void
    {
        $facture = $this->factureRepository->findById($factureId);
        if (null === $facture) {
            throw new \InvalidArgumentException('Unknown invoice.');
        }

        $venteId = $facture->getVenteId();
        if (null !== $venteId) {
            $vente = $this->venteRepository->findById($venteId);
            if (null === $vente) {
                throw new \InvalidArgumentException('Unknown sale linked to invoice.');
            }

            if ($vente->isCancelled()) {
                throw new \DomainException('Cannot record a payment on a cancelled sale.');
            }

            $balance = $this->balanceCalculator->computeBalance($factureId, $facture->getTotalAmount());
            if (bccomp($amount, $balance, 2) > 0) {
                throw new \InvalidArgumentException(sprintf(
                    'Payment amount exceeds the remaining balance (%s).',
                    $balance,
                ));
            }

            return;
        }

        $commandeId = $facture->getCommandeId();
        if (null === $commandeId) {
            return;
        }

        $commande = $this->commandeRepository->findById($commandeId);
        if (null === $commande) {
            throw new \InvalidArgumentException('Unknown order linked to invoice.');
        }

        if (CommandeStatus::Annulee === $commande->getStatus()) {
            throw new \DomainException('Cannot record a payment on a cancelled order.');
        }

        $balance = $this->computeOrderBalance($commande, $facture);
        if (bccomp($amount, $balance, 2) > 0) {
            throw new \InvalidArgumentException(sprintf(
                'Payment amount exceeds the remaining balance (%s).',
                $balance,
            ));
        }
    }

    private function assertCommandeDepositAllowed(Uuid $commandeId, string $amount): void
    {
        $commande = $this->commandeRepository->findById($commandeId);
        if (null === $commande) {
            throw new \InvalidArgumentException('Unknown order.');
        }

        if (CommandeStatus::Annulee === $commande->getStatus()) {
            throw new \DomainException('Cannot record a payment on a cancelled order.');
        }

        if (CommandeStatus::Initiee !== $commande->getStatus()) {
            throw new \DomainException('After confirmation, payments must be recorded against the invoice.');
        }

        $balance = bcsub($commande->getTotalAmount(), $commande->getDepositReceived(), 2);
        if (bccomp($amount, $balance, 2) > 0) {
            throw new \InvalidArgumentException(sprintf(
                'Payment amount exceeds the remaining balance (%s).',
                $balance,
            ));
        }
    }

    private function computeOrderBalance(Commande $commande, \App\Facturation\Domain\Entity\Facture $facture): string
    {
        $paid = '0.00';

        foreach ($this->paiementRepository->findByCommandeId($commande->getId()) as $paiement) {
            if (!$paiement->isCancelled()) {
                $paid = bcadd($paid, $paiement->getAmount(), 2);
            }
        }

        foreach ($this->paiementRepository->findByFactureId($facture->getId()) as $paiement) {
            if (!$paiement->isCancelled()) {
                $paid = bcadd($paid, $paiement->getAmount(), 2);
            }
        }

        $balance = bcsub($commande->getTotalAmount(), $paid, 2);

        return bccomp($balance, '0', 2) < 0 ? '0.00' : $balance;
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

    private function resolveOperationDate(?Uuid $factureId, ?Uuid $commandeId): \DateTimeImmutable
    {
        if (null !== $factureId) {
            $facture = $this->factureRepository->findById($factureId);
            if (null === $facture) {
                throw new \InvalidArgumentException('Unknown invoice.');
            }

            $venteId = $facture->getVenteId();
            if (null !== $venteId) {
                $vente = $this->venteRepository->findById($venteId);
                if (null === $vente) {
                    throw new \InvalidArgumentException('Unknown sale linked to invoice.');
                }

                return $vente->getCreatedAt();
            }

            $linkedCommandeId = $facture->getCommandeId();
            if (null !== $linkedCommandeId) {
                return $this->commandeCreatedAt($linkedCommandeId);
            }
        }

        if (null !== $commandeId) {
            return $this->commandeCreatedAt($commandeId);
        }

        throw new \InvalidArgumentException('A payment must reference either an invoice or an order.');
    }

    private function commandeCreatedAt(Uuid $commandeId): \DateTimeImmutable
    {
        $commande = $this->commandeRepository->findById($commandeId);
        if (null === $commande) {
            throw new \InvalidArgumentException('Unknown order.');
        }

        return $commande->getCreatedAt();
    }
}
