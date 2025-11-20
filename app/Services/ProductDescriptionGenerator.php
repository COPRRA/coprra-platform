<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class ProductDescriptionGenerator
{
    /**
     * Generate a detailed product description.
     */
    public static function generate(array $productData): string
    {
        $name = $productData['name'] ?? '';
        $brand = $productData['brand'] ?? '';
        $category = $productData['category'] ?? '';
        $specs = $productData['specs'] ?? [];
        $features = $productData['features'] ?? [];
        $baseDescription = $productData['description'] ?? '';

        // Start with base description
        $description = $baseDescription;

        // Add brand introduction if available
        if ($brand) {
            $brandIntro = self::getBrandIntroduction($brand);
            if ($brandIntro) {
                $description .= "\n\n".$brandIntro;
            }
        }

        // Add detailed product information
        $description .= "\n\n".self::getProductDetails($name, $category);

        // Add specifications summary if available
        if (! empty($specs)) {
            $description .= "\n\n📋 **Key Specifications:**\n";
            $count = 0;
            foreach ($specs as $key => $value) {
                if ($count >= 5) {
                    break;
                } // Limit to 5 specs
                $description .= '• **'.ucfirst($key).':** '.(\is_array($value) ? implode(', ', $value) : $value)."\n";
                ++$count;
            }
        }

        // Add features summary if available
        if (! empty($features)) {
            $description .= "\n\n✨ **Standout Features:**\n";
            $count = 0;
            foreach ($features as $feature) {
                if ($count >= 5) {
                    break;
                } // Limit to 5 features
                $description .= '• '.$feature."\n";
                ++$count;
            }
        }

        // Add category-specific benefits
        $description .= "\n\n".self::getCategoryBenefits($category);

        // Add call to action
        $description .= "\n\n🛒 **Why Buy This Product?**\n";
        $description .= self::getCallToAction($name, $brand, $category);

        return trim($description);
    }

    /**
     * Update existing product with enhanced description.
     */
    public static function enhanceProduct(Product $product): bool
    {
        try {
            $productData = [
                'name' => $product->name,
                'brand' => $product->brand->name ?? '',
                'category' => $product->category->name ?? '',
                'description' => $product->description ?? '',
                'specs' => json_decode($product->specifications, true) ?? [],
                'features' => json_decode($product->features, true) ?? [],
            ];

            $enhancedDescription = self::generate($productData);

            $product->update([
                'description' => $enhancedDescription,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to enhance product description: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get brand introduction.
     */
    private static function getBrandIntroduction(string $brandName): string
    {
        $introductions = [
            'Apple' => 'Apple is renowned for its innovative technology, premium design, and seamless ecosystem integration. Every Apple product is crafted with precision and attention to detail.',
            'Samsung' => 'Samsung is a global leader in technology and innovation, delivering cutting-edge products that combine performance, style, and reliability.',
            'Sony' => 'Sony brings decades of audio-visual excellence and technological innovation to every product, ensuring premium quality and performance.',
            'HP' => 'HP (Hewlett-Packard) is a trusted name in computing and printing solutions, offering reliable products for both professionals and home users.',
            'Dell' => 'Dell is known for delivering high-performance computers and technology solutions that cater to professionals, gamers, and everyday users.',
            'Lenovo' => 'Lenovo combines innovation with reliability, offering versatile computing solutions for work, entertainment, and everything in between.',
            'Microsoft' => 'Microsoft creates powerful software and hardware solutions that empower productivity and creativity worldwide.',
            'Google' => 'Google brings AI-powered innovation and seamless integration across all your devices, making technology more helpful and intuitive.',
            'LG' => 'LG is a pioneer in home appliances and electronics, delivering innovative solutions that enhance your lifestyle.',
            'Bosch' => 'Bosch represents German engineering excellence, providing durable and efficient appliances and tools.',
        ];

        return $introductions[$brandName] ?? '';
    }

    /**
     * Get detailed product information.
     */
    private static function getProductDetails(string $name, string $category): string
    {
        $details = "**About This Product:**\n\n";

        // Category-specific details
        if (false !== stripos($category, 'phone') || false !== stripos($category, 'mobile')) {
            $details .= "This {$name} is designed to keep you connected, productive, and entertained throughout your day. With advanced technology and intuitive features, it offers everything you need in a modern smartphone.";
        } elseif (false !== stripos($category, 'laptop') || false !== stripos($category, 'computer')) {
            $details .= "The {$name} combines powerful performance with portability, making it perfect for work, study, and entertainment. Whether you're multitasking, creating content, or enjoying media, this device delivers exceptional results.";
        } elseif (false !== stripos($category, 'tablet') || false !== stripos($category, 'ipad')) {
            $details .= "Experience versatility with the {$name}. Perfect for reading, browsing, streaming, and light productivity tasks. Its portable design makes it your ideal companion for on-the-go use.";
        } elseif (false !== stripos($category, 'watch')) {
            $details .= "The {$name} is your personal health and fitness companion. Track your activities, monitor your health, stay connected, and express your style with this advanced smartwatch.";
        } elseif (false !== stripos($category, 'headphone') || false !== stripos($category, 'earbuds') || false !== stripos($category, 'audio')) {
            $details .= "Immerse yourself in superior sound quality with the {$name}. Designed for music lovers and audiophiles, these deliver crystal-clear audio and comfortable wearing experience.";
        } elseif (false !== stripos($category, 'tv') || false !== stripos($category, 'television')) {
            $details .= "Transform your entertainment experience with the {$name}. Enjoy stunning picture quality, immersive sound, and smart features that bring your favorite content to life.";
        } elseif (false !== stripos($category, 'camera')) {
            $details .= "Capture life's precious moments with the {$name}. Whether you're a professional photographer or enthusiast, this camera delivers exceptional image quality and creative control.";
        } elseif (false !== stripos($category, 'printer')) {
            $details .= "The {$name} delivers reliable printing performance for your home or office. With easy setup and high-quality output, it's the perfect solution for all your printing needs.";
        } elseif (false !== stripos($category, 'appliance') || false !== stripos($category, 'home')) {
            $details .= "Make your home smarter and more efficient with the {$name}. Designed for modern living, it combines functionality, energy efficiency, and sleek design.";
        } else {
            $details .= "The {$name} is carefully designed to meet your needs with quality, reliability, and performance. Experience the perfect blend of functionality and value.";
        }

        return $details;
    }

    /**
     * Get category-specific benefits.
     */
    private static function getCategoryBenefits(string $category): string
    {
        $benefits = "**Perfect For:**\n";

        if (false !== stripos($category, 'phone') || false !== stripos($category, 'mobile')) {
            $benefits .= "• Professionals who need reliable communication\n";
            $benefits .= "• Content creators and photographers\n";
            $benefits .= "• Users who demand premium performance\n";
            $benefits .= '• Anyone seeking the latest mobile technology';
        } elseif (false !== stripos($category, 'laptop')) {
            $benefits .= "• Remote workers and digital nomads\n";
            $benefits .= "• Students and educators\n";
            $benefits .= "• Creative professionals\n";
            $benefits .= '• Business users who need reliable computing power';
        } elseif (false !== stripos($category, 'gaming')) {
            $benefits .= "• Competitive gamers\n";
            $benefits .= "• Streamers and content creators\n";
            $benefits .= "• Gaming enthusiasts\n";
            $benefits .= '• Anyone seeking immersive gaming experiences';
        } else {
            $benefits .= "• Home and office use\n";
            $benefits .= "• Daily tasks and entertainment\n";
            $benefits .= "• Users seeking quality and reliability\n";
            $benefits .= '• Anyone looking for value for money';
        }

        return $benefits;
    }

    /**
     * Get call to action.
     */
    private static function getCallToAction(string $name, string $brand, string $category): string
    {
        $cta = "Invest in quality with the {$name}. ";

        if ($brand) {
            $cta .= "Backed by {$brand}'s reputation for excellence, ";
        }

        $cta .= 'this product offers exceptional value and performance. ';
        $cta .= 'Compare prices from multiple retailers on COPRRA and make an informed purchase decision. ';
        $cta .= 'Get the best deal available and enjoy peace of mind with your purchase.';

        return $cta;
    }
}
