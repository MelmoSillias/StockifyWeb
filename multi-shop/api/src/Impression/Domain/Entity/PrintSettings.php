<?php

namespace App\Impression\Domain\Entity;

use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\Enum\OutputFormat;
use App\Impression\Domain\Enum\PageFormat;
use App\Impression\Infrastructure\Persistence\Doctrine\DoctrinePrintSettingsRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrinePrintSettingsRepository::class)]
#[ORM\Table(name: 'print_settings')]
class PrintSettings implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 255)]
    private string $shopName = 'Stockify';

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $addressLines = [];

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $phones = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $logoUrl = null;

    #[ORM\Column(enumType: PageFormat::class)]
    private PageFormat $defaultPageTable = PageFormat::A4;

    #[ORM\Column(enumType: PageFormat::class)]
    private PageFormat $defaultPageFacture = PageFormat::A4;

    #[ORM\Column(enumType: PageFormat::class)]
    private PageFormat $defaultPagePaiement = PageFormat::Receipt80mm;

    #[ORM\Column(enumType: PageFormat::class)]
    private PageFormat $defaultPageVente = PageFormat::Receipt80mm;

    #[ORM\Column(enumType: PageFormat::class)]
    private PageFormat $defaultPageBonLivraison = PageFormat::A4;

    #[ORM\Column(enumType: PageFormat::class)]
    private PageFormat $defaultPageTransaction = PageFormat::A4;

    #[ORM\Column(enumType: OutputFormat::class)]
    private OutputFormat $defaultExportFormat = OutputFormat::Pdf;

    #[ORM\Column]
    private bool $showLogo = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $footerText = null;

    #[ORM\Column]
    private int $marginMm = 10;

    public function __construct()
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
    }

    public static function createDefault(string $shopName = 'Stockify'): self
    {
        $settings = new self();
        $settings->shopName = $shopName;

        return $settings;
    }

    public function getShopName(): string
    {
        return $this->shopName;
    }

    public function setShopName(string $shopName): void
    {
        $this->shopName = $shopName;
        $this->touch();
    }

    /** @return list<string> */
    public function getAddressLines(): array
    {
        return $this->addressLines;
    }

    /** @param list<string> $addressLines */
    public function setAddressLines(array $addressLines): void
    {
        $this->addressLines = array_values(array_filter($addressLines, static fn (string $line): bool => '' !== trim($line)));
        $this->touch();
    }

    /** @return list<string> */
    public function getPhones(): array
    {
        return $this->phones;
    }

    /** @param list<string> $phones */
    public function setPhones(array $phones): void
    {
        $this->phones = array_values(array_filter($phones, static fn (string $phone): bool => '' !== trim($phone)));
        $this->touch();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
        $this->touch();
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function setLogoUrl(?string $logoUrl): void
    {
        $this->logoUrl = $logoUrl;
        $this->touch();
    }

    public function getDefaultPageTable(): PageFormat
    {
        return $this->defaultPageTable;
    }

    public function setDefaultPageTable(PageFormat $format): void
    {
        $this->defaultPageTable = $format;
        $this->touch();
    }

    public function getDefaultPageFacture(): PageFormat
    {
        return $this->defaultPageFacture;
    }

    public function setDefaultPageFacture(PageFormat $format): void
    {
        $this->defaultPageFacture = $format;
        $this->touch();
    }

    public function getDefaultPagePaiement(): PageFormat
    {
        return $this->defaultPagePaiement;
    }

    public function setDefaultPagePaiement(PageFormat $format): void
    {
        $this->defaultPagePaiement = $format;
        $this->touch();
    }

    public function getDefaultPageVente(): PageFormat
    {
        return $this->defaultPageVente;
    }

    public function setDefaultPageVente(PageFormat $format): void
    {
        $this->defaultPageVente = $format;
        $this->touch();
    }

    public function getDefaultPageBonLivraison(): PageFormat
    {
        return $this->defaultPageBonLivraison;
    }

    public function setDefaultPageBonLivraison(PageFormat $format): void
    {
        $this->defaultPageBonLivraison = $format;
        $this->touch();
    }

    public function getDefaultPageTransaction(): PageFormat
    {
        return $this->defaultPageTransaction;
    }

    public function setDefaultPageTransaction(PageFormat $format): void
    {
        $this->defaultPageTransaction = $format;
        $this->touch();
    }

    public function getDefaultExportFormat(): OutputFormat
    {
        return $this->defaultExportFormat;
    }

    public function setDefaultExportFormat(OutputFormat $format): void
    {
        $this->defaultExportFormat = $format;
        $this->touch();
    }

    public function isShowLogo(): bool
    {
        return $this->showLogo;
    }

    public function setShowLogo(bool $showLogo): void
    {
        $this->showLogo = $showLogo;
        $this->touch();
    }

    public function getFooterText(): ?string
    {
        return $this->footerText;
    }

    public function setFooterText(?string $footerText): void
    {
        $this->footerText = $footerText;
        $this->touch();
    }

    public function getMarginMm(): int
    {
        return $this->marginMm;
    }

    public function setMarginMm(int $marginMm): void
    {
        $this->marginMm = max(0, $marginMm);
        $this->touch();
    }

    public function defaultPageFor(DocumentType $type): PageFormat
    {
        return match ($type) {
            DocumentType::Table => $this->defaultPageTable,
            DocumentType::Facture, DocumentType::Avoir => $this->defaultPageFacture,
            DocumentType::Paiement => $this->defaultPagePaiement,
            DocumentType::VenteTicket => $this->defaultPageVente,
            DocumentType::BonLivraison => $this->defaultPageBonLivraison,
            DocumentType::Transaction => $this->defaultPageTransaction,
        };
    }
}
