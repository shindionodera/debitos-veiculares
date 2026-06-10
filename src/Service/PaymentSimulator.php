<?php

namespace App\Service;

class PaymentSimulator
{
    private const PIX_DISCOUNT = 0.05;
    private const MONTHLY_RATE = 0.025;
    private const CREDIT_TERMS = [1, 6, 12];

    public function simulate(array $evaluation): array
    {
        $baseTotal = (float) $evaluation['total_updated'];
        $options = [];

        $options[] = [
            'tipo' => 'TOTAL',
            'valor_base' => $this->formatMoney($baseTotal),
            'pix' => [
                'total_com_desconto' => $this->formatMoney($baseTotal * (1 - self::PIX_DISCOUNT)),
            ],
            'cartao_credito' => [
                'parcelas' => $this->buildCreditInstallments($baseTotal),
            ],
        ];

        foreach ($this->groupDebtsByType($evaluation['debts']) as $type => $debtGroup) {
            $options[] = [
                'tipo' => 'SOMENTE_' . $type,
                'valor_base' => $this->formatMoney($debtGroup['total_updated']),
                'pix' => [
                    'total_com_desconto' => $this->formatMoney($debtGroup['total_updated'] * (1 - self::PIX_DISCOUNT)),
                ],
                'cartao_credito' => [
                    'parcelas' => $this->buildCreditInstallments($debtGroup['total_updated']),
                ],
            ];
        }

        return ['opcoes' => $options];
    }

    private function buildCreditInstallments(float $amount): array
    {
        $installments = [];

        foreach (self::CREDIT_TERMS as $months) {
            $installments[] = [
                'quantidade' => $months,
                'valor_parcela' => $this->formatMoney($this->calculateInstallment($amount, $months)),
            ];
        }

        return $installments;
    }

    private function calculateInstallment(float $amount, int $months): float
    {
        if ($months === 1) {
            return $amount;
        }

        $rate = self::MONTHLY_RATE;
        $factor = (1 + $rate) ** $months;

        return $amount * $rate * $factor / ($factor - 1);
    }

    private function groupDebtsByType(array $debts): array
    {
        $grouped = [];

        foreach ($debts as $debt) {
            $type = $debt['tipo'];
            if (! isset($grouped[$type])) {
                $grouped[$type] = ['total_updated' => 0.0];
            }

            $grouped[$type]['total_updated'] += (float) $debt['valor_atualizado'];
        }

        return $grouped;
    }

    private function formatMoney(float $value): string
    {
        return number_format(round($value, 2, PHP_ROUND_HALF_UP), 2, '.', '');
    }
}
