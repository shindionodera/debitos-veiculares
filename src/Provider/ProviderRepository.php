<?php

namespace App\Provider;

class ProviderRepository
{
    /** @var ProviderInterface[] */
    private array $providers;

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    /** @return ProviderInterface[] */
    public function all(): array
    {
        return $this->providers;
    }
}
