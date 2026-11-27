
@extends('admin.layouts.app')

@section('title', 'Create Promotion Code')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Create Promotion Code</h4>

            <a href="{{ route('admin.promotion-codes.index') }}"
               class="btn btn-outline-secondary">
                Back
            </a>
        </div>

        @include('includes.error')

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST"
                      action="{{ route('admin.promotion-codes.store') }}">
                    @csrf

                    @include('admin.pages.promotion-codes._form', [
                        'promotionCode' => null
                    ])

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            Create Promotion
                        </button>

                        <a href="{{ route('admin.promotion-codes.index') }}"
                           class="btn btn-light">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
