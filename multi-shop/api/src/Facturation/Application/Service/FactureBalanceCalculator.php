<?php

namespace App\Facturation\Application\Service;

use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class FactureBalanceCalculator
{
    public function __construct(
        private readonly PaiementRepositoryInterface $paiementRepository,
    ) {
    }

    public function computePaidAmount(Uuid $factureId): string
    {
        $sum = '0.00';
        foreach ($this->paiementRepository->findByFactureId($factureId) as $paiement) {
            if (!$paiement->isCancelled()) {
                $sum = bcadd($sum, $paiement->getAmount(), 2);
            }
        }

        return $sum;
    }

    public function computeBalance(Uuid $factureId, string $totalAmount): string
    {
        $paidAmount = $this->computePaidAmount($factureId);
        $balance = bcsub($totalAmount, $paidAmount, 2);

        return bccomp($balance, '0', 2) < 0 ? '0.00' : $balance;
    }
}
