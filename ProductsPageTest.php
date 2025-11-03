<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductsPageTest extends TestCase
{
    public function test_products_page_loads_successfully(): void
    {
        $response = $this->get('/products');
        $response->assertStatus(200);
    }
    
    public function test_categories_page_loads_successfully(): void
    {
        $response = $this->get('/categories');
        $response->assertStatus(200);
    }
    
    public function test_brands_page_loads_successfully(): void
    {
        $response = $this->get('/brands');
        $response->assertStatus(200);
    }
}
