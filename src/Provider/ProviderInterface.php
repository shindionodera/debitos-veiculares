<?php

namespace App\Provider;

interface ProviderInterface
{
    public function getName(): string;

    public function fetch(string $licensePlate): string;
}
