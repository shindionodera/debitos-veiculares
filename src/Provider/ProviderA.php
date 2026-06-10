<?php

namespace App\Provider;

use App\Provider\ProviderUnavailableException;

class ProviderA implements ProviderInterface
{
    public function getName(): string
    {
        return 'provider-a';
    }

    public function fetch(string $licensePlate): string
    {
        if (str_starts_with($licensePlate, 'FAIL')) {
            throw new ProviderUnavailableException('Provider A is unavailable for plate ' . $licensePlate);
        }

        $result = [
            'vehicle' => $licensePlate,
            'debts' => [
                //['type' => 'IPVA', 'amount' => 1500.00, 'due_date' => '2024-01-10'],
                //['type' => 'MULTA', 'amount' => 300.50, 'due_date' => '2024-02-15'],
                ['type' => 'IPVA', 'amount' => 1800.00, 'due_date' => '2024-02-10'],
                ['type' => 'MULTA', 'amount' => 400.50, 'due_date' => '2024-03-15'],
            ],
        ];

        return json_encode($result, JSON_THROW_ON_ERROR);
    }
}
