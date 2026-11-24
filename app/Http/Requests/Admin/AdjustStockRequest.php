<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('inventory.manage');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['in', 'out', 'adjust'])],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['nullable', 'string', 'max:190'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => __('inventory.type'),
            'quantity' => __('inventory.quantity'),
            'reason' => __('inventory.reason'),
        ];
    }
}
