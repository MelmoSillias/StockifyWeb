<?php

namespace App\Impression\Domain\ValueObject;

final readonly class PrintProfile
{
    /**
     * @param list<string> $addressLines
     * @param list<string> $phones
     */
    public function __construct(
        public string $shopName,
        public array $addressLines,
        public array $phones,
        public ?string $email,
        public ?string $logoUrl,
        public bool $showLogo,
        public ?string $footerText,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'shop_name' => $this->shopName,
            'address_lines' => $this->addressLines,
            'phones' => $this->phones,
            'email' => $this->email,
            'logo_url' => $this->logoUrl,
            'show_logo' => $this->showLogo,
            'footer_text' => $this->footerText,
        ];
    }
}
