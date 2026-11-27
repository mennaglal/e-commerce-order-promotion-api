
@extends('admin.layouts.app')

@section('title', 'Add Product')

@section('content')
    <div class="container-fluid py-4">

        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Add New Product</h5>

                <a href="{{ route('admin.products.index') }}"
                   class="btn btn-secondary btn-sm">
                    Back
                </a>
            </div>

            <div class="card-body">

                @include('includes.error')
                @include('includes.success')

                <form action="{{ route('admin.products.store') }}" method="POST">
                    @csrf

                    @include('admin.pages.products._form', ['product' => null])

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Save Product
                        </button>

                        <a href="{{ route('admin.products.index') }}"
                           class="btn btn-light">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>

    </div>
@endsection
