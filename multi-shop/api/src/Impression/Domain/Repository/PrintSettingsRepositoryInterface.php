<?php

namespace App\Impression\Domain\Repository;

use App\Impression\Domain\Entity\PrintSettings;

interface PrintSettingsRepositoryInterface
{
    public function findSingleton(): ?PrintSettings;

    public function getOrCreateDefault(string $shopName = 'Stockify'): PrintSettings;

    public function save(PrintSettings $settings): void;
}
