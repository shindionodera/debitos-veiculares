<?php

namespace Tests;

use App\Domain\Debt;
use App\Domain\VehicleDebts;
use App\Service\DebtEvaluator;
use App\Service\UnknownDebtTypeException;
use PHPUnit\Framework\TestCase;

class DebtEvaluatorTest extends TestCase
{
    private DebtEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new DebtEvaluator();
    }

    public function testIpvaInterestIsCappedAtTwentyPercent(): void
    {
        $debt = new Debt('IPVA', 1500.00, '2024-01-10');
        $vehicleDebts = new VehicleDebts('ABC1234', [$debt]);

        $result = $this->evaluator->evaluate($vehicleDebts);

        $this->assertSame('1500.00', $result['debts'][0]['valor_original']);
        $this->assertSame('1800.00', $result['debts'][0]['valor_atualizado']);
        $this->assertSame('121', (string) $result['debts'][0]['dias_atraso']);
        $this->assertSame('1800.00', $result['total_updated']);
    }

    public function testMultaInterestIsCalculatedAndRoundedHalfUp(): void
    {
        $debt = new Debt('MULTA', 300.50, '2024-02-15');
        $vehicleDebts = new VehicleDebts('ABC1234', [$debt]);

        $result = $this->evaluator->evaluate($vehicleDebts);

        $this->assertSame('300.50', $result['debts'][0]['valor_original']);
        $this->assertSame('555.93', $result['debts'][0]['valor_atualizado']);
        $this->assertSame('85', (string) $result['debts'][0]['dias_atraso']);
    }

    public function testNotExpiredDebtHasNoInterest(): void
    {
        $debt = new Debt('IPVA', 1000.00, '2024-05-10');
        $vehicleDebts = new VehicleDebts('ABC1234', [$debt]);

        $result = $this->evaluator->evaluate($vehicleDebts);

        $this->assertSame('1000.00', $result['debts'][0]['valor_atualizado']);
    }

    public function testUnknownDebtTypeThrowsException(): void
    {
        $this->expectException(UnknownDebtTypeException::class);

        $debt = new Debt('IPTU', 500.00, '2024-01-10');
        $vehicleDebts = new VehicleDebts('ABC1234', [$debt]);

        $this->evaluator->evaluate($vehicleDebts);
    }
}
