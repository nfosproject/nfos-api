<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->with(['children' => fn ($query) => $query->withCount('products')])
            ->orderBy('display_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category)
    {
        $category->loadMissing(['children', 'parent']);

        return new CategoryResource($category);
    }
}
