<?php

namespace App\Provider;

use App\Provider\ProviderUnavailableException;

class ProviderB implements ProviderInterface
{
    public function getName(): string
    {
        return 'provider-b';
    }

    public function fetch(string $licensePlate): string
    {
        if (str_starts_with($licensePlate, 'FAIL')) {
            throw new ProviderUnavailableException('Provider B is unavailable for plate ' . $licensePlate);
        }

        $hasDebts = true;

        if ($licensePlate === 'EMPTY000') {
            $hasDebts = false;
        }

        if (! $hasDebts) {
            return sprintf('<response><vehicle>%s</vehicle><debts/></response>', $licensePlate);
        }

        return sprintf(
            '<response><vehicle>%s</vehicle><debts><debt><type>IPVA</type><amount>1500.00</amount><due_date>2024-01-10</due_date></debt><debt><type>MULTA</type><amount>300.50</amount><due_date>2024-02-15</due_date></debt></debts></response>',
            $licensePlate
        );
    }
}
