<?php

namespace App\Http\Requests\Admin;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('products.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'min:2', 'max:160'],
            'name_en' => ['required', 'string', 'min:2', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product?->id)],
            'brand' => ['nullable', 'string', 'max:120'],
            'description_ar' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'variant' => [$product ? 'nullable' : 'required', 'array'],
            'variant.sku' => [$product ? 'nullable' : 'required', 'string', 'max:80', Rule::unique('product_variants', 'sku')],
            'variant.size' => ['nullable', 'string', 'max:50'],
            'variant.color' => ['nullable', 'string', 'max:50'],
            'variant.color_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'variant.price' => [$product ? 'nullable' : 'required', 'numeric', 'min:0', 'max:10000000'],
            'variant.compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'variant.stock' => [$product ? 'nullable' : 'required', 'integer', 'min:0', 'max:1000000'],
            'variant.low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name_ar' => __('fields.name_ar'),
            'name_en' => __('fields.name_en'),
            'category_id' => __('fields.category'),
            'slug' => __('fields.slug'),
            'variant.sku' => __('fields.sku'),
            'variant.price' => __('fields.price'),
            'variant.stock' => __('fields.stock'),
            'variant.size' => __('fields.size'),
            'variant.color' => __('fields.color'),
        ];
    }

    /** @return array<string, mixed> */
    public function productData(): array
    {
        $data = $this->safe()->only([
            'category_id', 'name_ar', 'name_en', 'brand', 'description_ar', 'description_en', 'is_active', 'is_featured',
        ]);

        $data['slug'] = $this->filled('slug') ? $this->string('slug')->toString() : null;

        return $data;
    }

    /** @return array<string, mixed>|null */
    public function firstVariantData(): ?array
    {
        $variant = $this->input('variant');

        if (! is_array($variant) || empty($variant['sku']) || ! isset($variant['price'])) {
            return null;
        }

        return [
            'sku' => trim((string) $variant['sku']),
            'size' => ($variant['size'] ?? null) ?: null,
            'color' => ($variant['color'] ?? null) ?: null,
            'color_hex' => ($variant['color_hex'] ?? null) ?: null,
            'price_cents' => Money::toMinor($variant['price']),
            'compare_at_cents' => isset($variant['compare_at_price']) && $variant['compare_at_price'] !== '' ? Money::toMinor($variant['compare_at_price']) : null,
            'stock' => (int) ($variant['stock'] ?? 0),
            'low_stock_threshold' => (int) ($variant['low_stock_threshold'] ?? config('matjar.inventory.low_stock_threshold')),
            'is_active' => true,
        ];
    }
}
