<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\ValueObjects\Traits\MoneyArithmetic;
use App\ValueObjects\Traits\MoneyComparison;

final readonly class Money implements \JsonSerializable
{
    use MoneyArithmetic;
    use MoneyComparison;

    public function __construct(
        private int $amount,
        private string $currency = 'SAR'
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }

        if (empty($currency) || 3 !== \strlen($currency)) {
            throw new \InvalidArgumentException('Currency must be a valid 3-letter ISO code');
        }
    }

    public function __toString(): string
    {
        return $this->toFormattedString();
    }

    public static function fromFloat(float $amount, string $currency = 'SAR'): self
    {
        return new self((int) round($amount * 100), $currency);
    }

    public static function fromString(string $amount, string $currency = 'SAR'): self
    {
        $floatAmount = (float) $amount;

        return self::fromFloat($floatAmount, $currency);
    }

    public static function zero(string $currency = 'SAR'): self
    {
        return new self(0, $currency);
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function toFloat(): float
    {
        return $this->amount / 100;
    }

    public function toString(): string
    {
        return number_format($this->toFloat(), 2);
    }

    public function toFormattedString(): string
    {
        return $this->toString().' '.$this->currency;
    }

    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->toFormattedString(),
        ];
    }
}
