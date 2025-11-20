<?php

declare(strict_types=1);

namespace App\ValueObjects\Traits;

use App\ValueObjects\Money;

trait MoneyArithmetic
{
    public function add(Money $other): Money
    {
        $this->ensureSameCurrency($other);

        return new Money(
            $this->amount + $other->amount,
            $this->currency
        );
    }

    public function subtract(Money $other): Money
    {
        $this->ensureSameCurrency($other);

        return new Money(
            $this->amount - $other->amount,
            $this->currency
        );
    }

    public function multiply(float $multiplier): Money
    {
        return new Money(
            (int) round($this->amount * $multiplier),
            $this->currency
        );
    }

    public function divide(float $divisor): Money
    {
        if (0.0 === $divisor) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }

        return new Money(
            (int) round($this->amount / $divisor),
            $this->currency
        );
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException(
                "Cannot perform operation on different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }
}
