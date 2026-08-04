<?php

namespace App\Impression\Domain\Enum;

enum DocumentType: string
{
    case Facture = 'facture';
    case Avoir = 'avoir';
    case Paiement = 'paiement';
    case VenteTicket = 'vente_ticket';
    case BonLivraison = 'bon_livraison';
    case Transaction = 'transaction';
    case Table = 'table';

    /** @return list<PageFormat> */
    public function allowedPageFormats(): array
    {
        return match ($this) {
            self::Table => [PageFormat::A4, PageFormat::A5],
            self::Facture, self::Avoir, self::BonLivraison => [PageFormat::A4, PageFormat::A5],
            self::Paiement, self::VenteTicket, self::Transaction => [PageFormat::A4, PageFormat::A5, PageFormat::Receipt80mm],
        };
    }

    public function templateName(): string
    {
        return match ($this) {
            self::VenteTicket => 'vente_ticket',
            self::BonLivraison => 'bon_livraison',
            default => $this->value,
        };
    }
}
