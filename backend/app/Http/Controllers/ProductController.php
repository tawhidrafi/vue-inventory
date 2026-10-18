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
            ->when($request->boolean('trashed'), fn ($q) => $q->onlyTrashed())
            ->with('category')
            ->withCount('suppliers')
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

    /**
     * Resolve a single product from a scanned barcode or SKU.
     */
    public function lookup(Request $request)
    {
        $code = trim((string) $request->query('code'));

        if ($code === '') {
            return response()->json(['message' => 'A barcode or SKU is required.'], 422);
        }

        $product = Product::query()
            ->with('category')
            ->withCount('suppliers')
            ->withSum('stocks as stock_total', 'quantity')
            ->where(fn ($q) => $q->where('barcode', $code)->orWhere('sku', $code))
            ->first();

        if (! $product) {
            return response()->json(['message' => "No product matches \"{$code}\"."], 404);
        }

        return new ProductResource($product);
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

        // soft delete, but free the unique sku/barcode so they can be reused.
        // saved quietly so the audit log records one clean "deleted" entry.
        $product->sku = $this->tombstone($product->sku, $product->id);
        if ($product->barcode) {
            $product->barcode = $this->tombstone($product->barcode, $product->id);
        }
        $product->saveQuietly();

        $product->delete();

        return response()->noContent();
    }

    public function restore($id)
    {
        $product = Product::withTrashed()->findOrFail($id);

        if (! $product->trashed()) {
            return response()->json(['message' => 'Product is not deleted.'], 422);
        }

        $sku = $this->stripTombstone($product->sku, $product->id);
        $barcode = $product->barcode ? $this->stripTombstone($product->barcode, $product->id) : null;

        if (Product::whereKeyNot($product->id)->where('sku', $sku)->exists()) {
            return response()->json([
                'message' => "Cannot restore: SKU {$sku} is now used by another product.",
            ], 422);
        }

        if ($barcode && Product::whereKeyNot($product->id)->where('barcode', $barcode)->exists()) {
            return response()->json([
                'message' => "Cannot restore: barcode {$barcode} is now used by another product.",
            ], 422);
        }

        $product->sku = $sku;
        $product->barcode = $barcode;
        $product->saveQuietly();
        $product->restore();

        return new ProductResource($product->load('category'));
    }

    public function forceDestroy($id)
    {
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->movements()->exists() || $product->purchaseOrderItems()->exists() || $product->salesOrderItems()->exists()) {
            return response()->json([
                'message' => 'Cannot permanently delete a product that has stock history, purchase orders, or sales orders.',
            ], 422);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->forceDelete();

        return response()->noContent();
    }

    private function tombstone(string $value, int $id): string
    {
        return $value.'::deleted::'.$id;
    }

    private function stripTombstone(string $value, int $id): string
    {
        $suffix = '::deleted::'.$id;

        return str_ends_with($value, $suffix) ? substr($value, 0, -strlen($suffix)) : $value;
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
