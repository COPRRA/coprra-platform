<?php

declare(strict_types=1);

namespace App\ValueObjects\Traits;

use App\ValueObjects\ProductDetails;

trait ProductDetailsComparison
{
    public function equals(ProductDetails $other): bool
    {
        return $this->compareBasicProperties($other)
            && $this->compareArrayProperties($other)
            && $this->compareOptionalProperties($other);
    }

    private function compareBasicProperties(ProductDetails $other): bool
    {
        return $this->sku === $other->sku
            && $this->weight === $other->weight
            && $this->weightUnit === $other->weightUnit;
    }

    private function compareArrayProperties(ProductDetails $other): bool
    {
        return $this->dimensions === $other->dimensions
            && $this->specifications === $other->specifications
            && $this->features === $other->features;
    }

    private function compareOptionalProperties(ProductDetails $other): bool
    {
        return $this->color === $other->color
            && $this->size === $other->size
            && $this->material === $other->material
            && $this->brand === $other->brand;
    }
}
