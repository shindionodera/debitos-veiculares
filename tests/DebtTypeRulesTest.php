<?php

namespace Tests;

use App\Domain\Debt;
use App\Service\DebtTypeRules;
use PHPUnit\Framework\TestCase;

class DebtTypeRulesTest extends TestCase
{
    private DebtTypeRules $rules;

    protected function setUp(): void
    {
        $this->rules = new DebtTypeRules();
    }

    public function testIpvaInterestIsCappedAtTwentyPercent(): void
    {
        $debt = new Debt('IPVA', 1500.00, '2024-01-10');

        $updated = $this->rules->calculateUpdatedAmount($debt, 121);

        $this->assertSame(1800.00, $updated);
    }

    public function testMultaInterestIsCalculatedWithoutCap(): void
    {
        $debt = new Debt('MULTA', 300.50, '2024-02-15');

        $updated = $this->rules->calculateUpdatedAmount($debt, 85);

        $this->assertSame(555.925, $updated);
    }
}
