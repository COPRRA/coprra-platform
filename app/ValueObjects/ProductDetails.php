<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\ValueObjects\Traits\ProductDetailsComparison;
use App\ValueObjects\Traits\ProductDetailsValidation;

final readonly class ProductDetails implements \JsonSerializable
{
    use ProductDetailsComparison;
    use ProductDetailsValidation;

    public function __construct(
        private string $sku,
        private array $dimensions,
        private float $weight,
        private string $weightUnit,
        private array $specifications,
        private array $features,
        private ?string $color = null,
        private ?string $size = null,
        private ?string $material = null,
        private ?string $brand = null
    ) {
        $this->validateSku($sku);
        $this->validateDimensions($dimensions);
        $this->validateWeight($weight);
        $this->validateWeightUnit($weightUnit);
        $this->validateSpecifications($specifications);
        $this->validateFeatures($features);
    }

    public function __toString(): string
    {
        $parts = ["SKU: {$this->sku}"];

        if ($this->brand) {
            $parts[] = "العلامة التجارية: {$this->brand}";
        }

        if ($this->color) {
            $parts[] = "اللون: {$this->color}";
        }

        if ($this->size) {
            $parts[] = "المقاس: {$this->size}";
        }

        $parts[] = "الأبعاد: {$this->getFormattedDimensions()}";
        $parts[] = "الوزن: {$this->getFormattedWeight()}";

        return implode(' | ', $parts);
    }

    public static function create(
        string $sku,
        array $dimensions,
        float $weight,
        string $weightUnit = 'kg',
        array $specifications = [],
        array $features = [],
        ?string $color = null,
        ?string $size = null,
        ?string $material = null,
        ?string $brand = null
    ): self {
        return new self(
            $sku,
            $dimensions,
            $weight,
            $weightUnit,
            $specifications,
            $features,
            $color,
            $size,
            $material,
            $brand
        );
    }

    public function sku(): string
    {
        return $this->sku;
    }

    public function dimensions(): array
    {
        return $this->dimensions;
    }

    public function weight(): float
    {
        return $this->weight;
    }

    public function weightUnit(): string
    {
        return $this->weightUnit;
    }

    public function specifications(): array
    {
        return $this->specifications;
    }

    public function features(): array
    {
        return $this->features;
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function size(): ?string
    {
        return $this->size;
    }

    public function material(): ?string
    {
        return $this->material;
    }

    public function brand(): ?string
    {
        return $this->brand;
    }

    public function getFormattedDimensions(): string
    {
        if (empty($this->dimensions)) {
            return 'غير محدد';
        }

        $length = $this->dimensions['length'] ?? 0;
        $width = $this->dimensions['width'] ?? 0;
        $height = $this->dimensions['height'] ?? 0;
        $unit = $this->dimensions['unit'] ?? 'cm';

        return "{$length} × {$width} × {$height} {$unit}";
    }

    public function getFormattedWeight(): string
    {
        return "{$this->weight} {$this->weightUnit}";
    }

    public function getVolume(): float
    {
        if (empty($this->dimensions)) {
            return 0.0;
        }

        $length = $this->dimensions['length'] ?? 0;
        $width = $this->dimensions['width'] ?? 0;
        $height = $this->dimensions['height'] ?? 0;

        return $length * $width * $height;
    }

    public function hasSpecification(string $key): bool
    {
        return \array_key_exists($key, $this->specifications);
    }

    public function getSpecification(string $key): mixed
    {
        return $this->specifications[$key] ?? null;
    }

    public function hasFeature(string $feature): bool
    {
        return \in_array($feature, $this->features, true);
    }

    public function withSku(string $sku): self
    {
        return new self(
            $sku,
            $this->dimensions,
            $this->weight,
            $this->weightUnit,
            $this->specifications,
            $this->features,
            $this->color,
            $this->size,
            $this->material,
            $this->brand
        );
    }

    public function withDimensions(array $dimensions): self
    {
        return new self(
            $this->sku,
            $dimensions,
            $this->weight,
            $this->weightUnit,
            $this->specifications,
            $this->features,
            $this->color,
            $this->size,
            $this->material,
            $this->brand
        );
    }

    public function withWeight(float $weight, ?string $unit = null): self
    {
        return new self(
            $this->sku,
            $this->dimensions,
            $weight,
            $unit ?? $this->weightUnit,
            $this->specifications,
            $this->features,
            $this->color,
            $this->size,
            $this->material,
            $this->brand
        );
    }

    public function withSpecifications(array $specifications): self
    {
        return new self(
            $this->sku,
            $this->dimensions,
            $this->weight,
            $this->weightUnit,
            $specifications,
            $this->features,
            $this->color,
            $this->size,
            $this->material,
            $this->brand
        );
    }

    public function withFeatures(array $features): self
    {
        return new self(
            $this->sku,
            $this->dimensions,
            $this->weight,
            $this->weightUnit,
            $this->specifications,
            $features,
            $this->color,
            $this->size,
            $this->material,
            $this->brand
        );
    }

    public function withColor(?string $color): self
    {
        return new self(
            $this->sku,
            $this->dimensions,
            $this->weight,
            $this->weightUnit,
            $this->specifications,
            $this->features,
            $color,
            $this->size,
            $this->material,
            $this->brand
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'sku' => $this->sku,
            'dimensions' => $this->dimensions,
            'formatted_dimensions' => $this->getFormattedDimensions(),
            'weight' => $this->weight,
            'weight_unit' => $this->weightUnit,
            'formatted_weight' => $this->getFormattedWeight(),
            'volume' => $this->getVolume(),
            'specifications' => $this->specifications,
            'features' => $this->features,
            'color' => $this->color,
            'size' => $this->size,
            'material' => $this->material,
            'brand' => $this->brand,
        ];
    }
}
