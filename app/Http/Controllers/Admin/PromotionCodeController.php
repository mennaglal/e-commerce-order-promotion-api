<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionCode\StorePromotionCodeRequest;
use App\Http\Requests\Admin\PromotionCode\UpdatePromotionCodeRequest;
use App\Models\PromotionCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PromotionCodeController extends Controller
{
    public function index(Request $request): View
    {
        $promotions = PromotionCode::query()
            ->withCount([
                'usages' => fn ($query) => $query->whereNull('cancelled_at'),
                'orders',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(
                    'code',
                    'like',
                    '%' . $request->search . '%'
                );
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pages.promotion-codes.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('admin.pages.promotion-codes.create');
    }

    public function store(
        StorePromotionCodeRequest $request
    ): RedirectResponse {
        PromotionCode::create($request->validated());

        return redirect()
            ->route('admin.promotion-codes.index')
            ->with('success', 'Promotion code created successfully.');
    }
    public function edit(PromotionCode $promotionCode): View
    {
        return view('admin.pages.promotion-codes.edit', compact('promotionCode'));
    }


    public function update(
        UpdatePromotionCodeRequest $request,
        PromotionCode $promotionCode
    ): RedirectResponse {
        $promotionCode->update($request->validated());

        return redirect()
            ->route('admin.promotion-codes.index')
            ->with('success', 'Promotion code updated successfully.');
    }

    public function destroy(
        PromotionCode $promotionCode
    ): RedirectResponse {
        if (
            $promotionCode->usages()->exists() ||
            $promotionCode->orders()->exists()
        ) {
            return redirect()
                ->route('admin.promotion-codes.index')
                ->with(
                    'error',
                    'This promotion code has usage history or related orders. Deactivate it instead of deleting it.'
                );
        }

        $promotionCode->delete();

        return redirect()
            ->route('admin.promotion-codes.index')
            ->with('success', 'Promotion code deleted successfully.');
    }

}
