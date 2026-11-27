
@extends('admin.layouts.app')

@section('title', 'Promotion Codes')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <h4 class="mb-0">Promotion Codes</h4>

            <a href="{{ route('admin.promotion-codes.create') }}"
               class="btn btn-primary">
                <i class="fe fe-plus"></i>
                Add Promotion Code
            </a>
        </div>

        @include('includes.error')
        @include('includes.success')

        {{-- Search --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form action="{{ route('admin.promotion-codes.index') }}"
                      method="GET"
                      class="row align-items-center">

                    <div class="col-md-8 mb-2 mb-md-0">
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search by promotion code..."
                            value="{{ request('search') }}"
                        >
                    </div>

                    <div class="col-md-2 mb-2 mb-md-0">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fe fe-search"></i>
                            Search
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="{{ route('admin.promotion-codes.index') }}"
                           class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- Promotions Table --}}
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Promotion Codes List</h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Minimum Cart</th>
                        <th>Usage</th>
                        <th>Per Customer</th>
                        <th>Validity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($promotions as $promotion)
                        <tr>
                            <td>
                                {{ $promotions->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <strong>{{ $promotion->code }}</strong>
                            </td>

                            <td>
                                @if($promotion->type === \App\Models\PromotionCode::TYPE_PERCENTAGE)
                                    {{ rtrim(rtrim((string) $promotion->value, '0'), '.') }}%
                                    @if($promotion->max_discount_amount)
                                        <small class="d-block text-muted">
                                            Max:
                                            {{ number_format((float) $promotion->max_discount_amount, 2) }}
                                        </small>
                                    @endif
                                @else
                                    {{ number_format((float) $promotion->value, 2) }}
                                    <small class="text-muted">Fixed</small>
                                @endif
                            </td>

                            <td>
                                @if($promotion->min_cart_amount !== null)
                                    {{ number_format((float) $promotion->min_cart_amount, 2) }}
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                {{ $promotion->usages_count }}
                                /
                                {{ $promotion->usage_limit ?? 'Unlimited' }}
                                <small class="d-block text-muted">
                                    {{ $promotion->orders_count }} related orders
                                </small>
                            </td>

                            <td>
                                {{ $promotion->per_customer_limit ?? 'Unlimited' }}
                            </td>

                            <td>
                                <small class="d-block">
                                    From:
                                    {{ $promotion->starts_at?->format('Y-m-d H:i') ?? 'Any time' }}
                                </small>
                                <small class="d-block">
                                    To:
                                    {{ $promotion->ends_at?->format('Y-m-d H:i') ?? 'No expiry' }}
                                </small>
                            </td>

                            <td>
                                @if($promotion->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>

                            <td class="text-nowrap">
                                <a href="{{ route('admin.promotion-codes.edit', $promotion) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Edit">
                                    <i class="fe fe-edit"></i>
                                </a>

                                <form
                                    action="{{ route('admin.promotion-codes.destroy', $promotion) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this promotion code?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete">
                                        <i class="fe fe-trash-2"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fe fe-tag d-block mb-2"
                                   style="font-size: 30px;"></i>
                                No promotion codes found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center">
            {{ $promotions->appends(request()->query())->links('vendor.pagination.medium') }}
        </div>

    </div>
@endsection
