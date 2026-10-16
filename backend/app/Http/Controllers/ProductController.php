<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with('category')
            ->withSum('stocks as stock_total', 'quantity')
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('sku', 'like', "%{$s}%")
                      ->orWhere('barcode', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name');

        if ($request->boolean('all')) {
            return ProductResource::collection($query->get());
        }

        return ProductResource::collection(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function store(ProductRequest $request)
    {
        $data = $this->normalize($request->validated(), true);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = DB::transaction(function () use ($data) {
            $enteredSku = $data['sku'] ?? null;

            // create with a temporary unique sku, then replace it with the
            // sequential one derived from the row's own id.
            $product = Product::create([
                ...$data,
                'sku' => $enteredSku ?: Str::upper(Str::random(24)),
            ]);

            if (! $enteredSku) {
                $product->update([
                    'sku' => 'PRD-'.str_pad((string) $product->id, 5, '0', STR_PAD_LEFT),
                ]);
            }

            return $product;
        });

        return new ProductResource($product->load('category'));
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load('category'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $this->normalize($request->validated(), false);

        // keep the existing sku if the field was cleared on update
        if (array_key_exists('sku', $data) && blank($data['sku'])) {
            unset($data['sku']);
        }

        $oldImage = null;
        if ($request->hasFile('image')) {
            $oldImage = $product->image;
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        if ($oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return new ProductResource($product->load('category'));
    }

    public function destroy(Product $product)
    {
        if ($product->stocks()->where('quantity', '!=', 0)->exists()) {
            return response()->json([
                'message' => 'Cannot delete a product that still has stock.',
            ], 422);
        }

        // soft delete; the image file is kept so the product can be restored.
        $product->delete();

        return response()->noContent();
    }

    private function normalize(array $data, bool $creating): array
    {
        if ($creating) {
            // sane defaults when the field is omitted on create
            $data['cost_price'] ??= 0;
            $data['sell_price'] ??= 0;
            $data['reorder_level'] ??= 0;
            $data['unit'] ??= 'pcs';
            $data['is_active'] ??= true;

            return $data;
        }

        // on update we must not wipe fields the client didn't send;
        // only coerce fields that were present but empty.
        foreach (['cost_price', 'sell_price', 'reorder_level'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = 0;
            }
        }

        if (array_key_exists('unit', $data) && $data['unit'] === null) {
            $data['unit'] = 'pcs';
        }

        return $data;
    }
}
