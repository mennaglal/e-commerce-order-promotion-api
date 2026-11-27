
@php
    $promotionCode = $promotionCode ?? null;

    $dateValue = fn ($field) => old(
        $field,
        $promotionCode?->{$field}?->format('Y-m-d\TH:i')
    );
@endphp

<div class="row">

    {{-- Promotion Details --}}
    <div class="col-12">
        <h5 class="mb-3">Promotion Details</h5>
    </div>

    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">
            Promotion Code <span class="text-danger">*</span>
        </label>
        <input
            type="text"
            name="code"
            id="code"
            class="form-control text-uppercase @error('code') is-invalid @enderror"
            value="{{ old('code', $promotionCode?->code) }}"
            maxlength="255"
            required
        >
        @error('code')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="type" class="form-label">
            Discount Type <span class="text-danger">*</span>
        </label>
        <select
            name="type"
            id="type"
            class="form-control @error('type') is-invalid @enderror"
            required
        >
            <option
                value="percentage"
                @selected(old('type', $promotionCode?->type ?? 'percentage') === 'percentage')
            >
                Percentage (%)
            </option>
            <option
                value="fixed"
                @selected(old('type', $promotionCode?->type) === 'fixed')
            >
                Fixed Amount
            </option>
        </select>
        @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="value" class="form-label">
            Discount Value <span class="text-danger">*</span>
        </label>
        <input
            type="number"
            name="value"
            id="value"
            step="0.01"
            min="0.01"
            class="form-control @error('value') is-invalid @enderror"
            value="{{ old('value', $promotionCode?->value) }}"
            required
        >
        @error('value')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">
            For percentage discounts, the value must not exceed 100.
        </small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="min_cart_amount" class="form-label">
            Minimum Cart Amount
        </label>
        <input
            type="number"
            name="min_cart_amount"
            id="min_cart_amount"
            step="0.01"
            min="0"
            class="form-control @error('min_cart_amount') is-invalid @enderror"
            value="{{ old('min_cart_amount', $promotionCode?->min_cart_amount) }}"
        >
        @error('min_cart_amount')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3" id="max_discount_box">
        <label for="max_discount_amount" class="form-label">
            Maximum Discount Amount
        </label>
        <input
            type="number"
            name="max_discount_amount"
            id="max_discount_amount"
            step="0.01"
            min="0.01"
            class="form-control @error('max_discount_amount') is-invalid @enderror"
            value="{{ old('max_discount_amount', $promotionCode?->max_discount_amount) }}"
        >
        @error('max_discount_amount')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">
            Optional cap for percentage discounts.
        </small>
    </div>

    {{-- Usage Limits --}}
    <div class="col-12">
        <hr>
        <h5 class="mb-3">Usage Limits</h5>
    </div>

    <div class="col-md-6 mb-3">
        <label for="usage_limit" class="form-label">
            Total Usage Limit
        </label>
        <input
            type="number"
            name="usage_limit"
            id="usage_limit"
            min="1"
            class="form-control @error('usage_limit') is-invalid @enderror"
            value="{{ old('usage_limit', $promotionCode?->usage_limit) }}"
        >
        @error('usage_limit')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">
            Leave empty for unlimited usage.
        </small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="per_customer_limit" class="form-label">
            Per Customer Limit
        </label>
        <input
            type="number"
            name="per_customer_limit"
            id="per_customer_limit"
            min="1"
            class="form-control @error('per_customer_limit') is-invalid @enderror"
            value="{{ old('per_customer_limit', $promotionCode?->per_customer_limit) }}"
        >
        @error('per_customer_limit')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">
            Leave empty if each customer has no separate limit.
        </small>
    </div>

    {{-- Validity --}}
    <div class="col-12">
        <hr>
        <h5 class="mb-3">Validity Period</h5>
    </div>

    <div class="col-md-6 mb-3">
        <label for="starts_at" class="form-label">Starts At</label>
        <input
            type="datetime-local"
            name="starts_at"
            id="starts_at"
            class="form-control @error('starts_at') is-invalid @enderror"
            value="{{ $dateValue('starts_at') }}"
        >
        @error('starts_at')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="ends_at" class="form-label">Ends At</label>
        <input
            type="datetime-local"
            name="ends_at"
            id="ends_at"
            class="form-control @error('ends_at') is-invalid @enderror"
            value="{{ $dateValue('ends_at') }}"
        >
        @error('ends_at')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Status --}}
    <div class="col-12 mb-3">
        <hr>
        <input type="hidden" name="is_active" value="0">

        <div class="form-check">
            <input
                type="checkbox"
                name="is_active"
                id="is_active"
                value="1"
                class="form-check-input @error('is_active') is-invalid @enderror"
                @checked(old('is_active', $promotionCode?->is_active ?? true))
            >
            <label for="is_active" class="form-check-label">
                Active
            </label>
        </div>
        @error('is_active')
        <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('type');
        const maxDiscountBox = document.getElementById('max_discount_box');
        const maxDiscountInput = document.getElementById('max_discount_amount');

        function toggleMaxDiscount() {
            const isPercentage = typeSelect.value === 'percentage';

            maxDiscountBox.style.display = isPercentage ? '' : 'none';

            if (!isPercentage) {
                maxDiscountInput.value = '';
            }
        }

        typeSelect.addEventListener('change', toggleMaxDiscount);

        toggleMaxDiscount();
    });
</script>
