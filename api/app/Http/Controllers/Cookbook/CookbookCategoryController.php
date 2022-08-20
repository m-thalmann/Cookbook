<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Models\Cookbook;
use Illuminate\Http\Resources\Json\JsonResource;

class CookbookCategoryController extends Controller {
    public function index(Cookbook $cookbook) {
        $this->authorizeAnonymously('view', $cookbook);

        $categories = $cookbook
            ->recipes()
            ->orderBy('category', 'asc')
            ->select('category')
            ->distinct()
            ->get()
            ->pluck('category');

        return JsonResource::make($categories);
    }
}

