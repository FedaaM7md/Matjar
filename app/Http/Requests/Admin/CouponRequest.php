<?php

namespace App\Http\Requests\Admin;

use App\Models\Coupon;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('coupons.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Coupon::normalizeCode($this->input('code')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');

        return [
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'type' => ['required', Rule::in([Coupon::TYPE_PERCENT, Coupon::TYPE_FIXED])],
            'value' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'max_discount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (ValidationValidator $validator) {
            if ($this->input('type') === Coupon::TYPE_PERCENT && is_numeric($this->input('value')) && (float) $this->input('value') > 100) {
                $validator->errors()->add('value', __('validation.max.numeric', ['attribute' => __('fields.discount_percent'), 'max' => 100]));
            }
        });
    }

    public function attributes(): array
    {
        return [
            'code' => __('fields.coupon_code'),
            'value' => __('fields.coupon_value'),
            'min_subtotal' => __('fields.min_subtotal'),
            'max_discount' => __('fields.max_discount'),
            'usage_limit' => __('fields.usage_limit'),
            'per_user_limit' => __('fields.per_user_limit'),
            'starts_at' => __('fields.starts_at'),
            'ends_at' => __('fields.ends_at'),
        ];
    }

    /** @return array<string, mixed> */
    public function couponData(): array
    {
        $isPercent = $this->input('type') === Coupon::TYPE_PERCENT;

        return [
            'code' => Coupon::normalizeCode($this->input('code')),
            'type' => $this->input('type'),
            'value' => $isPercent
                ? (int) round(((float) $this->input('value')) * 100)
                : Money::toMinor($this->input('value')),
            'min_subtotal_cents' => Money::toMinor($this->input('min_subtotal')),
            'max_discount_cents' => $isPercent && $this->filled('max_discount')
                ? Money::toMinor($this->input('max_discount'))
                : null,
            'usage_limit' => $this->filled('usage_limit') ? (int) $this->input('usage_limit') : null,
            'per_user_limit' => $this->filled('per_user_limit') ? (int) $this->input('per_user_limit') : null,
            'starts_at' => $this->filled('starts_at') ? Carbon::parse($this->input('starts_at'))->startOfDay() : null,
            'ends_at' => $this->filled('ends_at') ? Carbon::parse($this->input('ends_at'))->endOfDay() : null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
