<?php
namespace App\Provider;

use App\Provider\ProviderUnavailableException;

class ProviderA implements ProviderInterface
{
    private const PLATE_ALL_PROVIDERS_FAIL = 'ZZZ9999';
    private const PLATE_PROVIDER_A_FAILS   = 'ZZZ9998';

    /**
     * Base de dados simulada do Provider A (formato JSON).
     * Para adicionar uma nova placa, basta incluir uma nova entrada neste array.
     */
    private const MOCK_DATA = [
        'ABC1234' => [
            //['type' => 'IPVA',  'amount' => 1800.00, 'due_date' => '2024-02-10'],
            //['type' => 'MULTA', 'amount' => 400.50,  'due_date' => '2024-03-15'],
            ['type' => 'IPVA', 'amount' => 1800.00, 'due_date' => '2024-02-10'],
            ['type' => 'MULTA', 'amount' => 400.50, 'due_date' => '2024-03-15'],
        ],
    ];

    public function getName(): string
    {
        return 'provider-a';
    }

    public function fetch(string $licensePlate): string
    {
        if ($licensePlate === self::PLATE_ALL_PROVIDERS_FAIL || $licensePlate === self::PLATE_PROVIDER_A_FAILS) {
            throw new ProviderUnavailableException('Provider A is unavailable for plate ' . $licensePlate);
        }

        $debts = self::MOCK_DATA[$licensePlate] ?? [];

        return json_encode(['vehicle' => $licensePlate, 'debts' => $debts], JSON_THROW_ON_ERROR);
    }
}