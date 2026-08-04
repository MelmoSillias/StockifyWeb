<?php

namespace App\Impression\Application\Dto;

final readonly class PrintSettingsDto
{
    /**
     * @param list<string> $addressLines
     * @param list<string> $phones
     */
    public function __construct(
        public string $id,
        public string $shopName,
        public array $addressLines,
        public array $phones,
        public ?string $email,
        public ?string $logoUrl,
        public string $defaultPageTable,
        public string $defaultPageFacture,
        public string $defaultPagePaiement,
        public string $defaultPageVente,
        public string $defaultPageBonLivraison,
        public string $defaultPageTransaction,
        public string $defaultExportFormat,
        public bool $showLogo,
        public ?string $footerText,
        public int $marginMm,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'shop_name' => $this->shopName,
            'address_lines' => $this->addressLines,
            'phones' => $this->phones,
            'email' => $this->email,
            'logo_url' => $this->logoUrl,
            'default_page_table' => $this->defaultPageTable,
            'default_page_facture' => $this->defaultPageFacture,
            'default_page_paiement' => $this->defaultPagePaiement,
            'default_page_vente' => $this->defaultPageVente,
            'default_page_bon_livraison' => $this->defaultPageBonLivraison,
            'default_page_transaction' => $this->defaultPageTransaction,
            'default_export_format' => $this->defaultExportFormat,
            'show_logo' => $this->showLogo,
            'footer_text' => $this->footerText,
            'margin_mm' => $this->marginMm,
        ];
    }
}
