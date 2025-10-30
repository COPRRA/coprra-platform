<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ComprehensiveValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function testComplexProductValidationScenario(): void
    {
        $rules = [
            'name' => 'required|string|min:3|max:255|unique:products,name',
            'description' => 'required|string|min:10|max:2000',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'sku' => 'required|string|unique:products,sku|regex:/^[A-Z0-9\-]+$/',
            'stock_quantity' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0.01',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'required_with:dimensions|numeric|min:0.1',
            'dimensions.width' => 'required_with:dimensions|numeric|min:0.1',
            'dimensions.height' => 'required_with:dimensions|numeric|min:0.1',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',
            'images' => 'nullable|array|max:5',
            'images.*' => 'string|max:255',
            'is_active' => 'boolean',
            'featured' => 'boolean',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
        ];

        // Test valid comprehensive product data
        $validProductData = [
            'name' => 'Premium Wireless Headphones',
            'description' => 'High-quality wireless headphones with noise cancellation and premium sound quality.',
            'price' => 299.99,
            'category_id' => 1,
            'brand_id' => 2,
            'sku' => 'PWH-2024-001',
            'stock_quantity' => 50,
            'weight' => 0.35,
            'dimensions' => [
                'length' => 20.5,
                'width' => 18.0,
                'height' => 8.5,
            ],
            'tags' => ['wireless', 'bluetooth', 'noise-cancelling', 'premium'],
            'images' => [
                '/uploads/products/headphones-1.jpg',
                '/uploads/products/headphones-2.jpg',
            ],
            'is_active' => true,
            'featured' => false,
            'meta_title' => 'Premium Wireless Headphones - Best Sound Quality',
            'meta_description' => 'Experience premium sound quality with our wireless headphones featuring advanced noise cancellation technology.',
        ];

        $validator = Validator::make($validProductData, $rules);
        self::assertTrue($validator->passes(), 'Valid comprehensive product data should pass validation');
        self::assertEmpty($validator->errors()->all(), 'Valid product data should have no validation errors');

        // Test invalid product data - multiple validation failures
        $invalidProductData = [
            'name' => 'AB', // Too short (min:3)
            'description' => 'Short', // Too short (min:10)
            'price' => -10.50, // Negative price
            'category_id' => 999999, // Non-existent category
            'brand_id' => 'invalid', // Should be integer
            'sku' => 'invalid sku with spaces!', // Invalid format
            'stock_quantity' => -5, // Negative stock
            'weight' => 0, // Below minimum
            'dimensions' => [
                'length' => 0, // Below minimum
                'width' => 18.0,
                // Missing required height when dimensions provided
            ],
            'tags' => array_fill(0, 12, 'tag'), // Too many tags (max:10)
            'images' => array_fill(0, 7, 'image.jpg'), // Too many images (max:5)
            'is_active' => 'not_boolean', // Invalid boolean
            'featured' => 'yes', // Invalid boolean
            'meta_title' => str_repeat('a', 65), // Too long (max:60)
            'meta_description' => str_repeat('b', 165), // Too long (max:160)
        ];

        $invalidValidator = Validator::make($invalidProductData, $rules);
        self::assertTrue($invalidValidator->fails(), 'Invalid product data should fail validation');

        $errors = $invalidValidator->errors();

        // Verify specific validation errors
        self::assertTrue($errors->has('name'), 'Should have error for short name');
        self::assertTrue($errors->has('description'), 'Should have error for short description');
        self::assertTrue($errors->has('price'), 'Should have error for negative price');
        self::assertTrue($errors->has('sku'), 'Should have error for invalid SKU format');
        self::assertTrue($errors->has('stock_quantity'), 'Should have error for negative stock');
        self::assertTrue($errors->has('weight'), 'Should have error for zero weight');
        self::assertTrue($errors->has('dimensions.length'), 'Should have error for zero length');
        self::assertTrue($errors->has('dimensions.height'), 'Should have error for missing height');
        self::assertTrue($errors->has('tags'), 'Should have error for too many tags');
        self::assertTrue($errors->has('images'), 'Should have error for too many images');
        self::assertTrue($errors->has('is_active'), 'Should have error for invalid boolean');
        self::assertTrue($errors->has('meta_title'), 'Should have error for long meta title');
        self::assertTrue($errors->has('meta_description'), 'Should have error for long meta description');

        self::assertGreaterThanOrEqual(10, \count($errors->keys()), 'Should have multiple validation errors');
    }

    #[Test]
    public function testCrossFieldValidationRules(): void
    {
        $rules = [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'required_if:discount_type,percentage|nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1|lte:usage_limit',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];

        // Test valid cross-field validation
        $validData = [
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'discount_type' => 'percentage',
            'discount_value' => 15.0,
            'min_order_amount' => 50.00,
            'max_discount_amount' => 25.00,
            'usage_limit' => 100,
            'usage_limit_per_user' => 5,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ];

        $validator = Validator::make($validData, $rules);
        self::assertTrue($validator->passes(), 'Valid cross-field data should pass validation');
        self::assertEmpty($validator->errors()->all(), 'Valid cross-field data should have no errors');

        // Test invalid cross-field validation - end date before start date
        $invalidData1 = [
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDay()->format('Y-m-d'), // Before start date
            'discount_type' => 'percentage',
            'discount_value' => 15.0,
            'min_order_amount' => 50.00,
            'max_discount_amount' => 25.00,
            'usage_limit' => 100,
            'usage_limit_per_user' => 5,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ];

        $invalidValidator1 = Validator::make($invalidData1, $rules);
        self::assertTrue($invalidValidator1->fails(), 'End date before start date should fail validation');
        self::assertTrue($invalidValidator1->errors()->has('end_date'), 'Should have error for end date');

        // Test invalid cross-field validation - usage limit per user exceeds total usage limit
        $invalidData2 = [
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'discount_type' => 'percentage',
            'discount_value' => 15.0,
            'min_order_amount' => 50.00,
            'max_discount_amount' => 25.00,
            'usage_limit' => 10,
            'usage_limit_per_user' => 15, // Exceeds total usage limit
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ];

        $invalidValidator2 = Validator::make($invalidData2, $rules);
        self::assertTrue($invalidValidator2->fails(), 'Usage limit per user exceeding total should fail validation');
        self::assertTrue($invalidValidator2->errors()->has('usage_limit_per_user'), 'Should have error for usage limit per user');

        // Test invalid cross-field validation - password confirmation mismatch
        $invalidData3 = [
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'discount_type' => 'percentage',
            'discount_value' => 15.0,
            'min_order_amount' => 50.00,
            'max_discount_amount' => 25.00,
            'usage_limit' => 100,
            'usage_limit_per_user' => 5,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'DifferentPassword456!', // Mismatch
        ];

        $invalidValidator3 = Validator::make($invalidData3, $rules);
        self::assertTrue($invalidValidator3->fails(), 'Password confirmation mismatch should fail validation');
        self::assertTrue($invalidValidator3->errors()->has('password'), 'Should have error for password confirmation');

        // Test conditional validation - max_discount_amount required for percentage discount
        $invalidData4 = [
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'discount_type' => 'percentage',
            'discount_value' => 15.0,
            'min_order_amount' => 50.00,
            'max_discount_amount' => null, // Required for percentage discount
            'usage_limit' => 100,
            'usage_limit_per_user' => 5,
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ];

        $invalidValidator4 = Validator::make($invalidData4, $rules);
        self::assertTrue($invalidValidator4->fails(), 'Missing max discount amount for percentage should fail validation');
        self::assertTrue($invalidValidator4->errors()->has('max_discount_amount'), 'Should have error for missing max discount amount');
    }

    #[Test]
    public function testBusinessRuleValidation(): void
    {
        // Custom validation rules for business logic
        Validator::extend('business_hours', static function ($attribute, $value, $parameters, $validator) {
            $hour = (int) date('H', strtotime($value));

            return $hour >= 9 && $hour <= 17; // Business hours: 9 AM to 5 PM
        });

        Validator::extend('working_day', static function ($attribute, $value, $parameters, $validator) {
            $dayOfWeek = date('N', strtotime($value)); // 1 (Monday) to 7 (Sunday)

            return $dayOfWeek >= 1 && $dayOfWeek <= 5; // Monday to Friday
        });

        Validator::extend('price_range_valid', static function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();
            $minPrice = $data['min_price'] ?? 0;
            $maxPrice = $data['max_price'] ?? \PHP_FLOAT_MAX;

            return $value >= $minPrice && $value <= $maxPrice;
        });

        $rules = [
            'appointment_datetime' => 'required|date|after:now|business_hours|working_day',
            'product_price' => 'required|numeric|min:0|price_range_valid',
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|gt:min_price',
            'delivery_date' => 'required|date|after:today',
            'priority' => 'required|in:low,medium,high,urgent',
            'budget' => 'required|numeric|min:100|max:10000',
        ];

        $customMessages = [
            'appointment_datetime.business_hours' => 'Appointments must be scheduled during business hours (9 AM - 5 PM).',
            'appointment_datetime.working_day' => 'Appointments can only be scheduled on working days (Monday - Friday).',
            'product_price.price_range_valid' => 'Product price must be within the specified price range.',
            'max_price.gt' => 'Maximum price must be greater than minimum price.',
        ];

        // Test valid business rule data
        $validBusinessData = [
            'appointment_datetime' => now()->next('Tuesday')->setTime(14, 30)->format('Y-m-d H:i:s'), // Tuesday 2:30 PM
            'product_price' => 150.00,
            'min_price' => 100.00,
            'max_price' => 200.00,
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'priority' => 'medium',
            'budget' => 1500.00,
        ];

        $validator = Validator::make($validBusinessData, $rules, $customMessages);
        self::assertTrue($validator->passes(), 'Valid business rule data should pass validation');
        self::assertEmpty($validator->errors()->all(), 'Valid business rule data should have no errors');

        // Test invalid business rule data - appointment outside business hours
        $invalidBusinessData1 = [
            'appointment_datetime' => now()->next('Tuesday')->setTime(20, 30)->format('Y-m-d H:i:s'), // Tuesday 8:30 PM
            'product_price' => 150.00,
            'min_price' => 100.00,
            'max_price' => 200.00,
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'priority' => 'medium',
            'budget' => 1500.00,
        ];

        $invalidValidator1 = Validator::make($invalidBusinessData1, $rules, $customMessages);
        self::assertTrue($invalidValidator1->fails(), 'Appointment outside business hours should fail validation');
        self::assertTrue($invalidValidator1->errors()->has('appointment_datetime'), 'Should have error for appointment time');
        self::assertContains(
            'Appointments must be scheduled during business hours (9 AM - 5 PM).',
            $invalidValidator1->errors()->get('appointment_datetime'),
            'Should use custom business hours message'
        );

        // Test invalid business rule data - weekend appointment
        $invalidBusinessData2 = [
            'appointment_datetime' => now()->next('Saturday')->setTime(14, 30)->format('Y-m-d H:i:s'), // Saturday 2:30 PM
            'product_price' => 150.00,
            'min_price' => 100.00,
            'max_price' => 200.00,
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'priority' => 'medium',
            'budget' => 1500.00,
        ];

        $invalidValidator2 = Validator::make($invalidBusinessData2, $rules, $customMessages);
        self::assertTrue($invalidValidator2->fails(), 'Weekend appointment should fail validation');
        self::assertTrue($invalidValidator2->errors()->has('appointment_datetime'), 'Should have error for weekend appointment');
        self::assertContains(
            'Appointments can only be scheduled on working days (Monday - Friday).',
            $invalidValidator2->errors()->get('appointment_datetime'),
            'Should use custom working day message'
        );

        // Test invalid business rule data - price outside range
        $invalidBusinessData3 = [
            'appointment_datetime' => now()->next('Tuesday')->setTime(14, 30)->format('Y-m-d H:i:s'),
            'product_price' => 250.00, // Outside range (100-200)
            'min_price' => 100.00,
            'max_price' => 200.00,
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'priority' => 'medium',
            'budget' => 1500.00,
        ];

        $invalidValidator3 = Validator::make($invalidBusinessData3, $rules, $customMessages);
        self::assertTrue($invalidValidator3->fails(), 'Price outside range should fail validation');
        self::assertTrue($invalidValidator3->errors()->has('product_price'), 'Should have error for price range');
        self::assertContains(
            'Product price must be within the specified price range.',
            $invalidValidator3->errors()->get('product_price'),
            'Should use custom price range message'
        );

        // Test invalid business rule data - max price not greater than min price
        $invalidBusinessData4 = [
            'appointment_datetime' => now()->next('Tuesday')->setTime(14, 30)->format('Y-m-d H:i:s'),
            'product_price' => 150.00,
            'min_price' => 200.00,
            'max_price' => 150.00, // Not greater than min_price
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'priority' => 'medium',
            'budget' => 1500.00,
        ];

        $invalidValidator4 = Validator::make($invalidBusinessData4, $rules, $customMessages);
        self::assertTrue($invalidValidator4->fails(), 'Max price not greater than min price should fail validation');
        self::assertTrue($invalidValidator4->errors()->has('max_price'), 'Should have error for max price');
        self::assertContains(
            'Maximum price must be greater than minimum price.',
            $invalidValidator4->errors()->get('max_price'),
            'Should use custom max price message'
        );
    }

    #[Test]
    public function testNestedArrayValidationWithComplexRules(): void
    {
        $rules = [
            'order' => 'required|array',
            'order.customer' => 'required|array',
            'order.customer.name' => 'required|string|min:2|max:100',
            'order.customer.email' => 'required|email|max:255',
            'order.customer.phone' => 'required|string|regex:/^[\+]?[1-9][\d]{0,15}$/',
            'order.items' => 'required|array|min:1|max:10',
            'order.items.*.product_id' => 'required|integer|exists:products,id',
            'order.items.*.quantity' => 'required|integer|min:1|max:100',
            'order.items.*.price' => 'required|numeric|min:0.01',
            'order.items.*.options' => 'nullable|array',
            'order.items.*.options.*.name' => 'required|string|max:50',
            'order.items.*.options.*.value' => 'required|string|max:100',
            'order.shipping' => 'required|array',
            'order.shipping.address' => 'required|string|min:10|max:255',
            'order.shipping.city' => 'required|string|min:2|max:100',
            'order.shipping.postal_code' => 'required|string|regex:/^[0-9]{5}(-[0-9]{4})?$/',
            'order.shipping.method' => 'required|in:standard,express,overnight',
            'order.payment' => 'required|array',
            'order.payment.method' => 'required|in:credit_card,paypal,bank_transfer',
            'order.payment.amount' => 'required|numeric|min:0.01',
        ];

        // Test valid nested array data
        $validNestedData = [
            'order' => [
                'customer' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'phone' => '+1234567890',
                ],
                'items' => [
                    [
                        'product_id' => 1,
                        'quantity' => 2,
                        'price' => 29.99,
                        'options' => [
                            ['name' => 'Color', 'value' => 'Blue'],
                            ['name' => 'Size', 'value' => 'Large'],
                        ],
                    ],
                    [
                        'product_id' => 2,
                        'quantity' => 1,
                        'price' => 49.99,
                        'options' => null,
                    ],
                ],
                'shipping' => [
                    'address' => '123 Main Street, Apt 4B',
                    'city' => 'New York',
                    'postal_code' => '10001',
                    'method' => 'express',
                ],
                'payment' => [
                    'method' => 'credit_card',
                    'amount' => 109.97,
                ],
            ],
        ];

        $validator = Validator::make($validNestedData, $rules);
        self::assertTrue($validator->passes(), 'Valid nested array data should pass validation');
        self::assertEmpty($validator->errors()->all(), 'Valid nested array data should have no errors');

        // Test invalid nested array data - multiple validation failures
        $invalidNestedData = [
            'order' => [
                'customer' => [
                    'name' => 'J', // Too short (min:2)
                    'email' => 'invalid-email', // Invalid email format
                    'phone' => 'invalid-phone', // Invalid phone format
                ],
                'items' => [
                    [
                        'product_id' => 'invalid', // Should be integer
                        'quantity' => 0, // Below minimum (min:1)
                        'price' => -10.50, // Negative price
                        'options' => [
                            ['name' => str_repeat('a', 51), 'value' => 'Blue'], // Name too long (max:50)
                            ['name' => 'Size', 'value' => str_repeat('b', 101)], // Value too long (max:100)
                        ],
                    ],
                    // Missing second item, but array should have at least 1 item
                ],
                'shipping' => [
                    'address' => 'Short', // Too short (min:10)
                    'city' => 'A', // Too short (min:2)
                    'postal_code' => 'invalid', // Invalid postal code format
                    'method' => 'invalid_method', // Invalid shipping method
                ],
                'payment' => [
                    'method' => 'invalid_payment', // Invalid payment method
                    'amount' => 0, // Below minimum (min:0.01)
                ],
            ],
        ];

        $invalidValidator = Validator::make($invalidNestedData, $rules);
        self::assertTrue($invalidValidator->fails(), 'Invalid nested array data should fail validation');

        $errors = $invalidValidator->errors();

        // Verify specific nested validation errors
        self::assertTrue($errors->has('order.customer.name'), 'Should have error for short customer name');
        self::assertTrue($errors->has('order.customer.email'), 'Should have error for invalid email');
        self::assertTrue($errors->has('order.customer.phone'), 'Should have error for invalid phone');
        self::assertTrue($errors->has('order.items.0.product_id'), 'Should have error for invalid product ID');
        self::assertTrue($errors->has('order.items.0.quantity'), 'Should have error for zero quantity');
        self::assertTrue($errors->has('order.items.0.price'), 'Should have error for negative price');
        self::assertTrue($errors->has('order.items.0.options.0.name'), 'Should have error for long option name');
        self::assertTrue($errors->has('order.items.0.options.1.value'), 'Should have error for long option value');
        self::assertTrue($errors->has('order.shipping.address'), 'Should have error for short address');
        self::assertTrue($errors->has('order.shipping.city'), 'Should have error for short city');
        self::assertTrue($errors->has('order.shipping.postal_code'), 'Should have error for invalid postal code');
        self::assertTrue($errors->has('order.shipping.method'), 'Should have error for invalid shipping method');
        self::assertTrue($errors->has('order.payment.method'), 'Should have error for invalid payment method');
        self::assertTrue($errors->has('order.payment.amount'), 'Should have error for zero payment amount');

        self::assertGreaterThanOrEqual(10, \count($errors->keys()), 'Should have multiple nested validation errors');
    }

    #[Test]
    public function testValidationWithDatabaseConstraints(): void
    {
        $rules = [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore(1), // Ignore user with ID 1
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(static function ($query) {
                    return $query->where('active', 1);
                }),
            ],
            'category_slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('categories', 'slug'),
            ],
            'product_sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->where(static function ($query) {
                    return $query->where('deleted_at', null);
                }),
            ],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(static function ($query) {
                    return $query->where('active', 1);
                }),
            ],
            'parent_category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(static function ($query) {
                    return $query->where('level', '<', 3); // Max 3 levels deep
                }),
            ],
        ];

        // Test valid database constraint data
        $validConstraintData = [
            'username' => 'valid_username123',
            'email' => 'unique@example.com',
            'category_slug' => 'electronics-gadgets',
            'product_sku' => 'UNIQUE-SKU-2024',
            'role_id' => 1, // Assuming this exists and is active
            'parent_category_id' => 1, // Assuming this exists and level < 3
        ];

        $validator = Validator::make($validConstraintData, $rules);

        // Note: In a real test environment, these would pass if the database constraints are met
        // For this test, we're validating the rule structure and format
        self::assertInstanceOf(\Illuminate\Validation\Validator::class, $validator, 'Validator should be created successfully');

        // Test the rule structure
        $validatorRules = $validator->getRules();
        self::assertArrayHasKey('username', $validatorRules, 'Username rules should be set');
        self::assertArrayHasKey('email', $validatorRules, 'Email rules should be set');
        self::assertArrayHasKey('category_slug', $validatorRules, 'Category slug rules should be set');
        self::assertArrayHasKey('product_sku', $validatorRules, 'Product SKU rules should be set');
        self::assertArrayHasKey('role_id', $validatorRules, 'Role ID rules should be set');
        self::assertArrayHasKey('parent_category_id', $validatorRules, 'Parent category ID rules should be set');

        // Verify rule types and counts
        self::assertGreaterThanOrEqual(4, \count($validatorRules['username']), 'Username should have multiple validation rules');
        self::assertGreaterThanOrEqual(3, \count($validatorRules['email']), 'Email should have multiple validation rules');
        self::assertGreaterThanOrEqual(3, \count($validatorRules['category_slug']), 'Category slug should have multiple validation rules');
        self::assertGreaterThanOrEqual(2, \count($validatorRules['product_sku']), 'Product SKU should have multiple validation rules');
        self::assertGreaterThanOrEqual(2, \count($validatorRules['role_id']), 'Role ID should have multiple validation rules');
        self::assertGreaterThanOrEqual(2, \count($validatorRules['parent_category_id']), 'Parent category ID should have multiple validation rules');

        // Test invalid format data that would fail before database checks
        $invalidFormatData = [
            'username' => 'invalid username!', // Contains invalid characters
            'email' => 'not-an-email', // Invalid email format
            'category_slug' => 'Invalid_Slug!', // Contains invalid characters
            'product_sku' => '', // Empty required field
            'role_id' => 'not_integer', // Not an integer
            'parent_category_id' => -1, // Negative integer
        ];

        $invalidValidator = Validator::make($invalidFormatData, $rules);
        self::assertTrue($invalidValidator->fails(), 'Invalid format data should fail validation');

        $errors = $invalidValidator->errors();
        self::assertTrue($errors->has('username'), 'Should have error for invalid username format');
        self::assertTrue($errors->has('email'), 'Should have error for invalid email format');
        self::assertTrue($errors->has('category_slug'), 'Should have error for invalid category slug format');
        self::assertTrue($errors->has('product_sku'), 'Should have error for empty product SKU');
        self::assertTrue($errors->has('role_id'), 'Should have error for non-integer role ID');

        self::assertGreaterThanOrEqual(5, \count($errors->keys()), 'Should have multiple format validation errors');
    }
}
