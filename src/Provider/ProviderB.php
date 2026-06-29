<?php
namespace App\Provider;

use App\Provider\ProviderUnavailableException;

class ProviderB implements ProviderInterface
{
    private const PLATE_ALL_PROVIDERS_FAIL = 'ZZZ9999';

    /**
     * Base de dados simulada do Provider B (formato XML).
     * Para adicionar uma nova placa, basta incluir uma nova entrada neste array.
     */
    private const MOCK_DATA = [
        'ABC1234' => [
            ['type' => 'IPVA',  'amount' => 1500.00, 'due_date' => '2024-01-10'],
            ['type' => 'MULTA', 'amount' => 300.50,  'due_date' => '2024-02-15'],
        ],
    ];

    public function getName(): string
    {
        return 'provider-b';
    }

    public function fetch(string $licensePlate): string
    {
        if ($licensePlate === self::PLATE_ALL_PROVIDERS_FAIL) {
            throw new ProviderUnavailableException('Provider B is unavailable for plate ' . $licensePlate);
        }

        $debts = self::MOCK_DATA[$licensePlate] ?? [];

        if (empty($debts)) {
            return sprintf('<response><vehicle>%s</vehicle><debts/></response>', $licensePlate);
        }

        $debtsXml = '';
        foreach ($debts as $debt) {
            $debtsXml .= sprintf(
                '<debt><type>%s</type><amount>%.2f</amount><due_date>%s</due_date></debt>',
                $debt['type'],
                $debt['amount'],
                $debt['due_date']
            );
        }

        return sprintf('<response><vehicle>%s</vehicle><debts>%s</debts></response>', $licensePlate, $debtsXml);
    }
}