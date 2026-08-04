<?php

namespace App\Impression\Application\Command\UpdatePrintSettings;

use App\Impression\Application\Dto\PrintSettingsDto;
use App\Impression\Application\Query\GetPrintSettings\GetPrintSettingsHandler;
use App\Impression\Application\Query\GetPrintSettings\GetPrintSettingsQuery;
use App\Impression\Domain\Enum\OutputFormat;
use App\Impression\Domain\Enum\PageFormat;
use App\Impression\Domain\Repository\PrintSettingsRepositoryInterface;

final class UpdatePrintSettingsHandler
{
    public function __construct(
        private readonly PrintSettingsRepositoryInterface $printSettingsRepository,
        private readonly GetPrintSettingsHandler $getPrintSettingsHandler,
    ) {
    }

    public function handle(UpdatePrintSettingsCommand $command): PrintSettingsDto
    {
        $payload = $command->payload;
        $settings = $this->printSettingsRepository->getOrCreateDefault();

        if (isset($payload['shop_name']) && is_string($payload['shop_name'])) {
            $settings->setShopName(trim($payload['shop_name']));
        }

        if (isset($payload['address_lines']) && is_array($payload['address_lines'])) {
            $settings->setAddressLines(array_map(strval(...), $payload['address_lines']));
        }

        if (isset($payload['phones']) && is_array($payload['phones'])) {
            $settings->setPhones(array_map(strval(...), $payload['phones']));
        }

        if (array_key_exists('email', $payload)) {
            $settings->setEmail(is_string($payload['email']) && '' !== trim($payload['email']) ? trim($payload['email']) : null);
        }

        if (array_key_exists('logo_url', $payload)) {
            $settings->setLogoUrl(is_string($payload['logo_url']) && '' !== trim($payload['logo_url']) ? trim($payload['logo_url']) : null);
        }

        if (isset($payload['default_page_table'])) {
            $settings->setDefaultPageTable(PageFormat::from((string) $payload['default_page_table']));
        }
        if (isset($payload['default_page_facture'])) {
            $settings->setDefaultPageFacture(PageFormat::from((string) $payload['default_page_facture']));
        }
        if (isset($payload['default_page_paiement'])) {
            $settings->setDefaultPagePaiement(PageFormat::from((string) $payload['default_page_paiement']));
        }
        if (isset($payload['default_page_vente'])) {
            $settings->setDefaultPageVente(PageFormat::from((string) $payload['default_page_vente']));
        }
        if (isset($payload['default_page_bon_livraison'])) {
            $settings->setDefaultPageBonLivraison(PageFormat::from((string) $payload['default_page_bon_livraison']));
        }
        if (isset($payload['default_page_transaction'])) {
            $settings->setDefaultPageTransaction(PageFormat::from((string) $payload['default_page_transaction']));
        }
        if (isset($payload['default_export_format'])) {
            $settings->setDefaultExportFormat(OutputFormat::from((string) $payload['default_export_format']));
        }
        if (isset($payload['show_logo'])) {
            $settings->setShowLogo((bool) $payload['show_logo']);
        }
        if (array_key_exists('footer_text', $payload)) {
            $settings->setFooterText(is_string($payload['footer_text']) && '' !== trim($payload['footer_text']) ? trim($payload['footer_text']) : null);
        }
        if (isset($payload['margin_mm'])) {
            $settings->setMarginMm((int) $payload['margin_mm']);
        }

        $this->printSettingsRepository->save($settings);

        return $this->getPrintSettingsHandler->handle(new GetPrintSettingsQuery());
    }
}
