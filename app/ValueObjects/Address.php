<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class Address implements \JsonSerializable
{
    public function __construct(
        private string $street,
        private string $city,
        private string $state,
        private string $postalCode,
        private string $country,
        private ?string $apartment = null,
        private ?string $landmark = null
    ) {
        $this->validateStreet($street);
        $this->validateCity($city);
        $this->validateState($state);
        $this->validatePostalCode($postalCode);
        $this->validateCountry($country);
    }

    public function __toString(): string
    {
        return $this->fullAddress();
    }

    public static function create(
        string $street,
        string $city,
        string $state,
        string $postalCode,
        string $country,
        ?string $apartment = null,
        ?string $landmark = null
    ): self {
        return new self($street, $city, $state, $postalCode, $country, $apartment, $landmark);
    }

    public function street(): string
    {
        return $this->street;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function postalCode(): string
    {
        return $this->postalCode;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function apartment(): ?string
    {
        return $this->apartment;
    }

    public function landmark(): ?string
    {
        return $this->landmark;
    }

    public function fullAddress(): string
    {
        $parts = [$this->street];

        if ($this->apartment) {
            $parts[] = "شقة {$this->apartment}";
        }

        if ($this->landmark) {
            $parts[] = "بالقرب من {$this->landmark}";
        }

        $parts[] = $this->city;
        $parts[] = $this->state;
        $parts[] = $this->postalCode;
        $parts[] = $this->country;

        return implode(', ', array_filter($parts));
    }

    public function withStreet(string $street): self
    {
        return new self($street, $this->city, $this->state, $this->postalCode, $this->country, $this->apartment, $this->landmark);
    }

    public function withCity(string $city): self
    {
        return new self($this->street, $city, $this->state, $this->postalCode, $this->country, $this->apartment, $this->landmark);
    }

    public function withState(string $state): self
    {
        return new self($this->street, $this->city, $state, $this->postalCode, $this->country, $this->apartment, $this->landmark);
    }

    public function withPostalCode(string $postalCode): self
    {
        return new self($this->street, $this->city, $this->state, $postalCode, $this->country, $this->apartment, $this->landmark);
    }

    public function withCountry(string $country): self
    {
        return new self($this->street, $this->city, $this->state, $this->postalCode, $country, $this->apartment, $this->landmark);
    }

    public function withApartment(?string $apartment): self
    {
        return new self($this->street, $this->city, $this->state, $this->postalCode, $this->country, $apartment, $this->landmark);
    }

    public function withLandmark(?string $landmark): self
    {
        return new self($this->street, $this->city, $this->state, $this->postalCode, $this->country, $this->apartment, $landmark);
    }

    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country
            && $this->apartment === $other->apartment
            && $this->landmark === $other->landmark;
    }

    public function isSameLocation(self $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }

    public function jsonSerialize(): array
    {
        return [
            'street' => $this->street,
            'apartment' => $this->apartment,
            'landmark' => $this->landmark,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'full_address' => $this->fullAddress(),
        ];
    }

    private function validateStreet(string $street): void
    {
        if (empty(trim($street))) {
            throw new \InvalidArgumentException('Street address cannot be empty');
        }

        if (\strlen($street) > 255) {
            throw new \InvalidArgumentException('Street address cannot exceed 255 characters');
        }
    }

    private function validateCity(string $city): void
    {
        if (empty(trim($city))) {
            throw new \InvalidArgumentException('City cannot be empty');
        }

        if (\strlen($city) > 100) {
            throw new \InvalidArgumentException('City cannot exceed 100 characters');
        }
    }

    private function validateState(string $state): void
    {
        if (empty(trim($state))) {
            throw new \InvalidArgumentException('State cannot be empty');
        }

        if (\strlen($state) > 100) {
            throw new \InvalidArgumentException('State cannot exceed 100 characters');
        }
    }

    private function validatePostalCode(string $postalCode): void
    {
        if (empty(trim($postalCode))) {
            throw new \InvalidArgumentException('Postal code cannot be empty');
        }

        // Saudi Arabia postal code validation (5 digits)
        if (! preg_match('/^\d{5}$/', $postalCode)) {
            throw new \InvalidArgumentException('Postal code must be 5 digits for Saudi Arabia');
        }
    }

    private function validateCountry(string $country): void
    {
        if (empty(trim($country))) {
            throw new \InvalidArgumentException('Country cannot be empty');
        }

        // For now, we'll support Saudi Arabia primarily
        $supportedCountries = ['Saudi Arabia', 'المملكة العربية السعودية', 'SA', 'SAU'];

        if (! \in_array($country, $supportedCountries, true)) {
            throw new \InvalidArgumentException('Currently only Saudi Arabia is supported');
        }
    }
}
