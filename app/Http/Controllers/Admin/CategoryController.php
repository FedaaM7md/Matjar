<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()
                ->with('parent')
                ->withCount('products')
                ->orderBy('position')
                ->orderBy('name_ar')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category,
            'parents' => Category::orderBy('name_ar')->get(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $source = ! empty($data['slug']) ? $data['slug'] : ($data['name_en'] ?: $data['name_ar']);
        $data['slug'] = Slug::unique(Category::class, $source, null, 'category');
        $data['position'] = $data['position'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        $category = Category::create($data);

        return redirect()->route('admin.categories.edit', $category)
            ->with('status', __('admin.categories.created'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parents' => Category::whereKeyNot($category->id)->orderBy('name_ar')->get(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['slug'])) {
            $data['slug'] = Slug::unique(Category::class, $data['slug'], $category->id, 'category');
        } else {
            unset($data['slug']);
        }

        $data['position'] = $data['position'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return redirect()->route('admin.categories.edit', $category)
            ->with('status', __('admin.categories.updated'));
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', __('admin.categories.has_products'));
        }

        if ($category->children()->exists()) {
            return back()->with('error', __('admin.categories.has_children'));
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('status', __('admin.categories.deleted'));
    }
}
