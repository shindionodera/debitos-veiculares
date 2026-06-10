<?php

namespace App\Domain;

class VehicleDebts
{
    private string $vehicle;
    /** @var Debt[] */
    private array $debts;

    public function __construct(string $vehicle, array $debts = [])
    {
        $this->vehicle = $vehicle;
        $this->debts = $debts;
    }

    public function getVehicle(): string
    {
        return $this->vehicle;
    }

    /** @return Debt[] */
    public function getDebts(): array
    {
        return $this->debts;
    }

    public function addDebt(Debt $debt): void
    {
        $this->debts[] = $debt;
    }

    public function totalAmount(): float
    {
        return array_reduce($this->debts, fn($carry, Debt $debt) => $carry + $debt->getAmount(), 0.0);
    }

    public function getDebtByType(string $type): ?Debt
    {
        foreach ($this->debts as $debt) {
            if ($debt->getType() === $type) {
                return $debt;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'vehicle' => $this->vehicle,
            'debts' => array_map(fn(Debt $debt) => $debt->toArray(), $this->debts),
            'total' => round($this->totalAmount(), 2),
        ];
    }
}
