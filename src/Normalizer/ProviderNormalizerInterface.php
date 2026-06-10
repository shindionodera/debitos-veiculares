<?php

namespace App\Normalizer;

use App\Domain\VehicleDebts;

interface ProviderNormalizerInterface
{
    public function supports(string $providerName): bool;

    public function normalize(string $providerResponse): VehicleDebts;
}
