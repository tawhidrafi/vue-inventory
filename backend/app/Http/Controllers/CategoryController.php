<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()
            ->when($request->boolean('trashed'), fn ($q) => $q->onlyTrashed())
            ->with('parent')
            ->withCount('products')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name');

        // ?all=1 returns everything (used for dropdowns)
        if ($request->boolean('all')) {
            return CategoryResource::collection($query->get());
        }

        return CategoryResource::collection(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return new CategoryResource($category->load('parent'));
    }

    public function show(Category $category)
    {
        return new CategoryResource($category->load('parent')->loadCount('products'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return new CategoryResource($category->load('parent'));
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that has products or subcategories.',
            ], 422);
        }

        // soft delete, but free the unique name so it can be reused.
        // saved quietly so the audit log records one clean "deleted" entry.
        $category->name = $category->name.'::deleted::'.$category->id;
        $category->saveQuietly();

        $category->delete();

        return response()->noContent();
    }

    public function restore($id)
    {
        $category = Category::withTrashed()->findOrFail($id);

        if (! $category->trashed()) {
            return response()->json(['message' => 'Category is not deleted.'], 422);
        }

        $suffix = '::deleted::'.$category->id;
        $name = str_ends_with($category->name, $suffix)
            ? substr($category->name, 0, -strlen($suffix))
            : $category->name;

        if (Category::whereKeyNot($category->id)->where('name', $name)->exists()) {
            return response()->json([
                'message' => "Cannot restore: a category named {$name} already exists.",
            ], 422);
        }

        $category->name = $name;
        $category->saveQuietly();
        $category->restore();

        return new CategoryResource($category->load('parent'));
    }

    public function forceDestroy($id)
    {
        $category = Category::withTrashed()->findOrFail($id);

        if ($category->products()->exists() || $category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot permanently delete a category that has products or subcategories.',
            ], 422);
        }

        $category->forceDelete();

        return response()->noContent();
    }
}
