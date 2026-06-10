<?php

namespace App\Domain;

class Debt
{
    private string $type;
    private float $amount;
    private string $dueDate;

    public function __construct(string $type, float $amount, string $dueDate)
    {
        $this->type = $type;
        $this->amount = $amount;
        $this->dueDate = $dueDate;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function withAmount(float $amount): self
    {
        return new self($this->type, $amount, $this->dueDate);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'amount' => round($this->amount, 2),
            'due_date' => $this->dueDate,
        ];
    }
}
