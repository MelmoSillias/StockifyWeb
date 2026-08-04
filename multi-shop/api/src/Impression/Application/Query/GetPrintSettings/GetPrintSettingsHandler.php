<?php

namespace App\Impression\Application\Query\GetPrintSettings;

use App\Impression\Application\Dto\PrintSettingsDto;
use App\Impression\Domain\Repository\PrintSettingsRepositoryInterface;

final class GetPrintSettingsHandler
{
    public function __construct(
        private readonly PrintSettingsRepositoryInterface $printSettingsRepository,
    ) {
    }

    public function handle(GetPrintSettingsQuery $query): PrintSettingsDto
    {
        $settings = $this->printSettingsRepository->getOrCreateDefault();

        return new PrintSettingsDto(
            id: (string) $settings->getId(),
            shopName: $settings->getShopName(),
            addressLines: $settings->getAddressLines(),
            phones: $settings->getPhones(),
            email: $settings->getEmail(),
            logoUrl: $settings->getLogoUrl(),
            defaultPageTable: $settings->getDefaultPageTable()->value,
            defaultPageFacture: $settings->getDefaultPageFacture()->value,
            defaultPagePaiement: $settings->getDefaultPagePaiement()->value,
            defaultPageVente: $settings->getDefaultPageVente()->value,
            defaultPageBonLivraison: $settings->getDefaultPageBonLivraison()->value,
            defaultPageTransaction: $settings->getDefaultPageTransaction()->value,
            defaultExportFormat: $settings->getDefaultExportFormat()->value,
            showLogo: $settings->isShowLogo(),
            footerText: $settings->getFooterText(),
            marginMm: $settings->getMarginMm(),
        );
    }
}
