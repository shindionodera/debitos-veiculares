<?php

namespace Tests;

use App\Domain\Debt;
use App\Domain\VehicleDebts;
use App\Service\DebtEvaluator;
use App\Service\PaymentSimulator;
use PHPUnit\Framework\TestCase;

class PaymentSimulatorTest extends TestCase
{
    private DebtEvaluator $evaluator;
    private PaymentSimulator $simulator;

    protected function setUp(): void
    {
        $this->evaluator = new DebtEvaluator();
        $this->simulator = new PaymentSimulator();
    }

    public function testPaymentOptionsIncludeTotalAndPartialByType(): void
    {
        $debts = [
            new Debt('IPVA', 1500.00, '2024-01-10'),
            new Debt('MULTA', 300.50, '2024-02-15'),
        ];
        $vehicleDebts = new VehicleDebts('ABC1234', $debts);
        $evaluation = $this->evaluator->evaluate($vehicleDebts);

        $result = $this->simulator->simulate($evaluation);

        $this->assertCount(3, $result['opcoes']);
        $this->assertSame('TOTAL', $result['opcoes'][0]['tipo']);
        $this->assertSame('2238.13', $result['opcoes'][0]['pix']['total_com_desconto']);
        $this->assertSame('1800.00', $result['opcoes'][1]['valor_base']);
        $this->assertSame('528.13', $result['opcoes'][2]['pix']['total_com_desconto']);
        $this->assertSame('229.67', $result['opcoes'][0]['cartao_credito']['parcelas'][2]['valor_parcela']);
    }
}
