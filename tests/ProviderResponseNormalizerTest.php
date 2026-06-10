<?php

namespace Tests;

use App\Normalizer\ProviderResponseNormalizer;
use App\Domain\Debt;
use App\Domain\VehicleDebts;
use PHPUnit\Framework\TestCase;

class ProviderResponseNormalizerTest extends TestCase
{
    private ProviderResponseNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProviderResponseNormalizer();
    }

    public function testNormalizesJsonProviderResponse(): void
    {
        $payload = json_encode([
            'vehicle' => 'ABC1234',
            'debts' => [
                ['type' => 'IPVA', 'amount' => 1500.00, 'due_date' => '2024-01-10'],
                ['type' => 'MULTA', 'amount' => 300.50, 'due_date' => '2024-02-15'],
            ],
        ], JSON_THROW_ON_ERROR);

        $vehicleDebts = $this->normalizer->normalize($payload);

        $this->assertSame('ABC1234', $vehicleDebts->getVehicle());
        $this->assertCount(2, $vehicleDebts->getDebts());
        $this->assertSame('IPVA', $vehicleDebts->getDebts()[0]->getType());
    }

    public function testNormalizesXmlProviderResponse(): void
    {
        $payload = '<response><vehicle>ABC1234</vehicle><debts><debt><type>IPVA</type><amount>1500.00</amount><due_date>2024-01-10</due_date></debt><debt><type>MULTA</type><amount>300.50</amount><due_date>2024-02-15</due_date></debt></debts></response>';

        $vehicleDebts = $this->normalizer->normalize($payload);

        $this->assertSame('ABC1234', $vehicleDebts->getVehicle());
        $this->assertCount(2, $vehicleDebts->getDebts());
        $this->assertSame('MULTA', $vehicleDebts->getDebts()[1]->getType());
    }
}
