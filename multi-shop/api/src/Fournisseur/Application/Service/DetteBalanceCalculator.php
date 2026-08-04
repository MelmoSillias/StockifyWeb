<?php

namespace App\Fournisseur\Application\Service;

use App\Fournisseur\Domain\Repository\PaiementFournisseurRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class DetteBalanceCalculator
{
    public function __construct(
        private readonly PaiementFournisseurRepositoryInterface $paiementFournisseurRepository,
    ) {
    }

    public function computePaidAmount(Uuid $detteId): string
    {
        $sum = '0.00';
        foreach ($this->paiementFournisseurRepository->findByDetteId($detteId) as $paiement) {
            if (!$paiement->isCancelled()) {
                $sum = bcadd($sum, $paiement->getAmount(), 2);
            }
        }

        return $sum;
    }

    public function computeBalance(Uuid $detteId, string $totalAmount): string
    {
        $paidAmount = $this->computePaidAmount($detteId);
        $balance = bcsub($totalAmount, $paidAmount, 2);

        return bccomp($balance, '0', 2) < 0 ? '0.00' : $balance;
    }
}
