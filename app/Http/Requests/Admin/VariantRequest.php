<?php

namespace App\Http\Requests\Admin;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('products.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $variant = $this->route('variant');

        return [
            'sku' => ['required', 'string', 'max:80', Rule::unique('product_variants', 'sku')->ignore($variant?->id)],
            'size' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'color_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'stock' => [$variant ? 'nullable' : 'required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'sku' => __('fields.sku'),
            'price' => __('fields.price'),
            'stock' => __('fields.stock'),
            'size' => __('fields.size'),
            'color' => __('fields.color'),
        ];
    }

    /** @return array<string, mixed> */
    public function variantData(): array
    {
        return [
            'sku' => trim($this->string('sku')->toString()),
            'size' => $this->input('size') ?: null,
            'color' => $this->input('color') ?: null,
            'color_hex' => $this->input('color_hex') ?: null,
            'price_cents' => Money::toMinor($this->input('price')),
            'compare_at_cents' => $this->filled('compare_at_price') ? Money::toMinor($this->input('compare_at_price')) : null,
            'low_stock_threshold' => (int) ($this->input('low_stock_threshold') ?? config('matjar.inventory.low_stock_threshold')),
            'is_active' => $this->boolean('is_active'),
            'position' => (int) ($this->input('position') ?? 0),
        ];
    }
}
