<?php

namespace App\Impression\Application\Builder;

use App\Impression\Application\Dto\DocumentViewModel;
use App\Impression\Domain\Entity\PrintSettings;
use App\Impression\Domain\Enum\OutputFormat;
use App\Impression\Domain\ValueObject\DocumentRequest;
use App\Impression\Domain\ValueObject\PageSettings;
use App\Impression\Domain\ValueObject\PrintProfile;

final class DocumentViewModelBuilder
{
    /**
     * @param array<string, mixed> $data
     */
    public function build(
        DocumentRequest $request,
        PrintSettings $settings,
        array $data,
    ): DocumentViewModel {
        $pageSettings = new PageSettings($request->pageFormat, $settings->getMarginMm());
        $profile = new PrintProfile(
            $settings->getShopName(),
            $settings->getAddressLines(),
            $settings->getPhones(),
            $settings->getEmail(),
            $settings->getLogoUrl(),
            $settings->isShowLogo(),
            $settings->getFooterText(),
        );

        return new DocumentViewModel(
            documentType: $request->documentType->templateName(),
            page: $pageSettings->toArray(),
            profile: $profile->toArray(),
            data: $data,
            autoPrint: OutputFormat::Html === $request->format && $request->inline,
        );
    }
}
