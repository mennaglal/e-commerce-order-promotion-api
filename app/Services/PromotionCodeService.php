<?php

namespace App\Services;

use App\Models\PromotionCode;
use App\Models\User;

class PromotionCodeService
{
    public function findByCode(string $code, bool $lock = false): ?PromotionCode
    {
        $query = PromotionCode::where('code', strtoupper(trim($code)));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function assertEligible(
        PromotionCode $promo,
        User $user,
        string $subtotal
    ): ?array {
        if (! $promo->is_active) {
            return $this->failure('Invalid coupon code.', 'coupon_invalid');
        }

        if ($promo->starts_at && now()->lt($promo->starts_at)) {
            return $this->failure('This coupon is not active yet.', 'coupon_not_started');
        }

        if ($promo->ends_at && now()->gt($promo->ends_at)) {
            return $this->failure('This coupon has expired.', 'coupon_expired');
        }

        if (
            $promo->min_cart_amount !== null &&
            bccomp($subtotal, $promo->min_cart_amount, 2) < 0
        ) {
            return $this->failure(
                "A minimum cart amount of {$promo->min_cart_amount} is required.",
                'coupon_min_amount'
            );
        }

        if (
            $promo->usage_limit !== null &&
            $promo->usages()->count() >= $promo->usage_limit
        ) {
            return $this->failure(
                'This coupon has reached its usage limit.',
                'coupon_usage_limit_reached'
            );
        }

        if (
            $promo->per_customer_limit !== null &&
            $promo->usages()->where('user_id', $user->id)->count() >= $promo->per_customer_limit
        ) {
            return $this->failure(
                'You have already used this coupon the maximum number of times.',
                'coupon_per_customer_limit_reached'
            );
        }

        return null;
    }

    public function calculateDiscount(PromotionCode $promo, string $subtotal): string
    {
        $discount = $promo->type === PromotionCode::TYPE_PERCENTAGE
            ? bcdiv(bcmul($subtotal, $promo->value, 4), '100', 2)
            : $promo->value;

        if (
            $promo->max_discount_amount !== null &&
            bccomp($discount, $promo->max_discount_amount, 2) > 0
        ) {
            $discount = $promo->max_discount_amount;
        }

        if (bccomp($discount, $subtotal, 2) > 0) {
            $discount = $subtotal;
        }

        return $discount;
    }

    private function failure(string $message, string $code): array
    {
        return [
            'message' => $message,
            'code' => $code,
        ];
    }
}
