<?php

namespace App\Fournisseur\Application\Service;

use App\Catalog\Domain\Repository\ProductVariantRepositoryInterface;
use App\Finance\Application\Service\FinanceSeedService;
use App\Finance\Application\Service\ModeDePaiementService;
use App\Fournisseur\Domain\Entity\CommandeFournisseur;
use App\Fournisseur\Domain\Entity\DetteFournisseur;
use App\Fournisseur\Domain\Event\CommandeFournisseurRecue;
use App\Fournisseur\Domain\Repository\CommandeFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\DetteFournisseurRepositoryInterface;
use App\Fournisseur\Domain\Repository\FournisseurRepositoryInterface;
use App\SharedKernel\Application\Event\DomainEventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class AchatsService
{
    public function __construct(
        private readonly CommandeFournisseurRepositoryInterface $commandeRepository,
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
        private readonly DetteFournisseurRepositoryInterface $detteRepository,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ModeDePaiementService $modeDePaiementService,
        private readonly FinanceSeedService $financeSeedService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function creer(array $payload): CommandeFournisseur
    {
        if (empty($payload['fournisseur_id'])) {
            throw new \InvalidArgumentException('fournisseur_id is required.');
        }

        $fournisseurId = Uuid::fromString((string) $payload['fournisseur_id']);
        $this->assertFournisseurExists($fournisseurId);

        $commande = new CommandeFournisseur($fournisseurId);

        foreach ($this->normalizeLines($payload['lines'] ?? []) as $line) {
            $variant = $this->variantRepository->findById(Uuid::fromString($line['variant_id']));
            if (null === $variant) {
                throw new \InvalidArgumentException('Unknown product variant.');
            }

            $label = !empty($line['label']) ? (string) $line['label'] : $variant->getSku();
            $commande->addLine(
                $variant->getId(),
                $label,
                (string) $line['quantity'],
                (string) $line['unit_cost'],
            );
        }

        if ($commande->getLines()->isEmpty()) {
            throw new \InvalidArgumentException('At least one line is required.');
        }

        $this->commandeRepository->save($commande);

        return $commande;
    }

    public function confirmer(CommandeFournisseur $commande, ?\DateTimeImmutable $expectedAt = null): void
    {
        $commande->confirm($expectedAt);
        $this->commandeRepository->save($commande);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function recevoir(CommandeFournisseur $commande, array $payload): void
    {
        $paidAmount = isset($payload['paid_amount']) ? (string) $payload['paid_amount'] : '0.00';
        if (bccomp($paidAmount, '0', 2) < 0) {
            throw new \InvalidArgumentException('paid_amount cannot be negative.');
        }
        if (bccomp($paidAmount, $commande->getTotalAmount(), 2) > 0) {
            throw new \InvalidArgumentException('paid_amount cannot exceed the order total.');
        }

        $modeDePaiementId = null;
        if (bccomp($paidAmount, '0', 2) > 0) {
            $this->financeSeedService->seedIfEmpty();
            if (empty($payload['mode_de_paiement_id']) && empty($payload['method'])) {
                throw new \InvalidArgumentException('mode_de_paiement_id or method is required when paid_amount > 0.');
            }
            $modeDePaiementId = $this->modeDePaiementService->resolveFromPayload($payload)->getId();
        }

        $paidAt = $this->parsePaidAt($payload['paid_at'] ?? null);

        $commande->receive();
        $this->commandeRepository->save($commande);

        $this->eventDispatcher->dispatch(new CommandeFournisseurRecue(
            $commande->getId(),
            $commande->getFournisseurId(),
            $commande->getTotalAmount(),
            $paidAmount,
            $modeDePaiementId,
            $paidAt,
        ));
    }

    public function annuler(CommandeFournisseur $commande): void
    {
        $commande->cancel();
        $this->commandeRepository->save($commande);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function creerDetteManuelle(array $payload): DetteFournisseur
    {
        if (empty($payload['fournisseur_id'])) {
            throw new \InvalidArgumentException('fournisseur_id is required.');
        }
        if (empty($payload['total_amount'])) {
            throw new \InvalidArgumentException('total_amount is required.');
        }

        $fournisseurId = Uuid::fromString((string) $payload['fournisseur_id']);
        $this->assertFournisseurExists($fournisseurId);

        $dette = new DetteFournisseur(
            $fournisseurId,
            (string) $payload['total_amount'],
            !empty($payload['label']) ? (string) $payload['label'] : null,
        );

        $this->detteRepository->save($dette);

        return $dette;
    }

    private function assertFournisseurExists(Uuid $fournisseurId): void
    {
        $fournisseur = $this->fournisseurRepository->findById($fournisseurId);
        if (null === $fournisseur || $fournisseur->isDeleted()) {
            throw new \InvalidArgumentException('Unknown supplier.');
        }
    }

    /**
     * @param mixed $lines
     * @return list<array{variant_id: string, quantity: string, unit_cost: string, label?: string}>
     */
    private function normalizeLines(mixed $lines): array
    {
        if (!\is_array($lines) || [] === $lines) {
            return [];
        }

        $normalized = [];
        foreach ($lines as $line) {
            if (!\is_array($line) || empty($line['variant_id']) || empty($line['quantity']) || empty($line['unit_cost'])) {
                throw new \InvalidArgumentException('Each line requires variant_id, quantity and unit_cost.');
            }
            $normalized[] = $line;
        }

        return $normalized;
    }

    private function parsePaidAt(mixed $value): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid paid_at datetime.');
        }
    }
}
