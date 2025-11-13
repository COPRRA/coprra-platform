<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="List products",
     *     description="Get a list of products with optional search",
     *     operationId="listProducts",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Search by product name",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of products",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     summary="Update product",
     *     description="Update an existing product (Admin only)",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProductUpdateRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            $validated = $request->validated();

            $validated['slug'] = $this->updateProductSlug($validated, $id);

            $product->update($validated);

            return $this->success(
                $this->formatProductResponse($product),
                'Product updated successfully'
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Product not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('An error occurred while updating the product', $e);
        }
    }

    /**
     * @OA\Post(
     *     path="/products",
     *     summary="Create product",
     *     description="Create a new product (Admin only)",
     *     operationId="createProduct",
     *     tags={"Products"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/ProductCreateRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    private function updateProductSlug(array $validated, int $id): string
    {
        if (! isset($validated['name'])) {
            return $validated['slug'] ?? '';
        }

        $nameValue = $validated['name'];
        $nameString = \is_string($nameValue) ? $nameValue : '';
        $baseSlug = Str::slug($nameString);
        $slug = $baseSlug;
        $counter = 1;

        while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            ++$counter;
        }

        return $slug;
    }

    /**
     * Format product data for response.
     *
     * @return array<array<int|string>|bool|int|mixed|string|* @method static \App\Models\Brand create(array<string, string|bool|null>
     *
     * @psalm-return array{id: int, name: string, slug: string, description: string, price: string, created_at: mixed|null, updated_at: mixed|null, image_url: string|null, is_active: bool, category_id: int, brand_id: int, category: array{id: int, name: string}|null, brand: array{id: int, name: string}|null, stores: array<never, never>|mixed}
     */
    private function formatProductResponse(Product $product): array
    {
        $response = [
            'id' => $product->id,
            'name' => htmlspecialchars($product->name, \ENT_QUOTES, 'UTF-8'),
            'slug' => $product->slug,
            'description' => htmlspecialchars($product->description, \ENT_QUOTES, 'UTF-8'),
            'price' => $product->price,
            'created_at' => $product->created_at ? $product->created_at->toIso8601String() : null,
            'updated_at' => $product->updated_at ? $product->updated_at->toIso8601String() : null,
            'image_url' => $product->image ? asset('storage/'.$product->image) : null,
            'is_active' => $product->is_active,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'category' => null,
            'brand' => null,
            'stores' => [],
        ];

        if ($product->relationLoaded('category') && $product->category) {
            $response['category'] = [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ];
        }

        if ($product->relationLoaded('brand') && $product->brand) {
            $response['brand'] = [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
            ];
        }

        if ($product->relationLoaded('stores') && $product->stores) {
            $response['stores'] = $product->stores->map(static function ($store): array {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                ];
            })->all();
        }

        return $response;
    }

    /**
     * Autocomplete endpoint for live search.
     *
     * @return JsonResponse
     */
    public function autocomplete(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        
        if (empty($query) || strlen($query) < 2) {
            return $this->success([], 'No results');
        }

        $products = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'slug']);

        $results = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'url' => route('products.show', $product->slug),
            ];
        })->all();

        return $this->success($results, 'Autocomplete results');
    }
}
