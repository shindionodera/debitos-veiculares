<?php

namespace App\Service;

use App\Domain\Debt;

class DebtTypeRules
{
    private const RULES = [
        'IPVA' => [
            'daily_rate' => 0.0033,
            'max_rate' => 0.20,
        ],
        'MULTA' => [
            'daily_rate' => 0.01,
            'max_rate' => null,
        ],
    ];

    public function calculateUpdatedAmount(Debt $debt, int $daysLate): float
    {
        if ($daysLate <= 0) {
            return $debt->getAmount();
        }

        $rule = $this->getRule($debt->getType());
        $interest = $debt->getAmount() * $rule['daily_rate'] * $daysLate;

        if ($rule['max_rate'] !== null) {
            $interest = min($interest, $debt->getAmount() * $rule['max_rate']);
        }

        return $debt->getAmount() + $interest;
    }

    private function getRule(string $type): array
    {
        if (! isset(self::RULES[$type])) {
            throw new UnknownDebtTypeException($type);
        }

        return self::RULES[$type];
    }
}
