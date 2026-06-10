<?php

namespace App\Provider;

class ProviderFactory
{
    /** @return ProviderInterface[] */
    public static function createAll(): array
    {
        return [
            new ProviderA(),
            new ProviderB(),
        ];
    }
}
