<?php

namespace App\Service;

class UnknownDebtTypeException extends \InvalidArgumentException
{
    private string $type;

    public function __construct(string $type)
    {
        parent::__construct('Unknown debt type: ' . $type);
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
