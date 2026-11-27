
@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
    <div class="container-fluid py-4">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Product Management</h4>

            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                <i class="fe fe-plus"></i>
                Add Product
            </a>
        </div>

        @include('includes.error')
        @include('includes.success')

        {{-- Search --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form action="{{ route('admin.products.index') }}"
                      method="GET"
                      class="row align-items-center">

                    <div class="col-md-8 mb-2 mb-md-0">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="Search by product name or SKU..."
                        >
                    </div>

                    <div class="col-md-2 mb-2 mb-md-0">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fe fe-search"></i>
                            Search
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="{{ route('admin.products.index') }}"
                           class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>

                </form>
            </div>
        </div>

        {{-- Products Table --}}
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Products List</h5>
            </div>

            <div class="table-responsive">
                <table class="table table-hover mb-0">

                    <thead>
                    <tr>
                        <th>#</th>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Stock Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                {{ $products->firstItem() + $loop->index }}
                            </td>

                            <td>
                                {{ $product->sku }}
                            </td>

                            <td>
                                <strong>{{ $product->name }}</strong>

                                @if($product->description)
                                    <small class="d-block text-muted">
                                        {{ \Illuminate\Support\Str::limit($product->description, 60) }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                {{ number_format((float) $product->price, 2) }}
                            </td>

                            <td>
                                {{ number_format($product->stock_quantity) }}

                                @if($product->stock_quantity === 0)
                                    <span class="badge badge-danger ml-1">
                                            Out of Stock
                                        </span>
                                @elseif($product->stock_quantity <= 5)
                                    <span class="badge badge-warning ml-1">
                                            Low Stock
                                        </span>
                                @endif
                            </td>

                            <td>
                                @if($product->status)
                                    <span class="badge badge-success">
                                            Active
                                        </span>
                                @else
                                    <span class="badge badge-secondary">
                                            Inactive
                                        </span>
                                @endif
                            </td>

                            <td class="text-nowrap">
                                <a href="{{ route('admin.products.edit', $product) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Edit">
                                    <i class="fe fe-edit"></i>
                                </a>

                                <form
                                    action="{{ route('admin.products.destroy', $product) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this product?')"
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
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fe fe-package d-block mb-2"
                                   style="font-size: 30px;"></i>

                                No products found.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center">
            {{ $products->appends(request()->query())->links('vendor.pagination.medium') }}
        </div>

    </div>
@endsection
