<?php

declare(strict_types=1);

namespace App\ValueObjects\Traits;

use App\ValueObjects\Money;

trait MoneyComparison
{
    public function isGreaterThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function isLessThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amount < $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function isZero(): bool
    {
        return 0 === $this->amount;
    }
}
