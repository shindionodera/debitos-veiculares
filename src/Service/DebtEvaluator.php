<?php

namespace App\Service;

use App\Domain\Debt;
use App\Domain\VehicleDebts;

class DebtEvaluator
{
    private const DATE_NOW = '2024-05-10T00:00:00Z';

    private \DateTimeImmutable $now;
    private DebtTypeRules $rules;

    public function __construct(DebtTypeRules $rules = null, ?\DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new \DateTimeImmutable(self::DATE_NOW, new \DateTimeZone('UTC'));
        $this->rules = $rules ?? new DebtTypeRules();
    }

    public function evaluate(VehicleDebts $vehicleDebts): array
    {
        $debts = [];
        $totalOriginal = 0.0;
        $totalUpdated = 0.0;

        foreach ($vehicleDebts->getDebts() as $debt) {
            $evaluation = $this->evaluateDebt($debt);
            $debts[] = $evaluation;
            $totalOriginal += (float) $evaluation['valor_original'];
            $totalUpdated += (float) $evaluation['valor_atualizado'];
        }

        return [
            'vehicle' => $vehicleDebts->getVehicle(),
            'debts' => $debts,
            'total_original' => $this->formatMoney($totalOriginal),
            'total_updated' => $this->formatMoney($totalUpdated),
        ];
    }

    private function evaluateDebt(Debt $debt): array
    {
        $original = $debt->getAmount();
        $daysLate = $this->calculateDaysLate($debt->getDueDate());
        $updated = $this->rules->calculateUpdatedAmount($debt, $daysLate);

        return [
            'tipo' => $debt->getType(),
            'valor_original' => $this->formatMoney($original),
            'valor_atualizado' => $this->formatMoney($updated),
            'vencimento' => $debt->getDueDate(),
            'dias_atraso' => $daysLate,
        ];
    }

    private function calculateDaysLate(string $dueDate): int
    {
        $due = new \DateTimeImmutable($dueDate, new \DateTimeZone('UTC'));

        if ($due >= $this->now) {
            return 0;
        }

        $interval = $due->diff($this->now);

        return (int) $interval->days;
    }

    private function formatMoney(float $value): string
    {
        return number_format($this->roundHalfUp($value), 2, '.', '');
    }

    private function roundHalfUp(float $value): float
    {
        return round($value, 2, PHP_ROUND_HALF_UP);
    }
}
