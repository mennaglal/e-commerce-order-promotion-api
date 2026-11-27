
<div class="row">

    {{-- Product Name --}}
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">
            Product Name <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            name="name"
            id="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $product?->name) }}"
            required
        >

        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- SKU --}}
    <div class="col-md-6 mb-3">
        <label for="sku" class="form-label">
            SKU <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            name="sku"
            id="sku"
            class="form-control @error('sku') is-invalid @enderror"
            value="{{ old('sku', $product?->sku) }}"
            required
        >

        @error('sku')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Description --}}
    <div class="col-12 mb-3">
        <label for="description" class="form-label">
            Description
        </label>

        <textarea
            name="description"
            id="description"
            rows="4"
            class="form-control @error('description') is-invalid @enderror"
        >{{ old('description', $product?->description) }}</textarea>

        @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Price --}}
    <div class="col-md-6 mb-3">
        <label for="price" class="form-label">
            Price <span class="text-danger">*</span>
        </label>

        <input
            type="number"
            name="price"
            id="price"
            step="0.01"
            min="0"
            class="form-control @error('price') is-invalid @enderror"
            value="{{ old('price', $product?->price) }}"
            required
        >

        @error('price')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Stock Quantity --}}
    <div class="col-md-6 mb-3">
        <label for="stock_quantity" class="form-label">
            Stock Quantity <span class="text-danger">*</span>
        </label>

        <input
            type="number"
            name="stock_quantity"
            id="stock_quantity"
            min="0"
            class="form-control @error('stock_quantity') is-invalid @enderror"
            value="{{ old('stock_quantity', $product?->stock_quantity ?? 0) }}"
            required
        >

        @error('stock_quantity')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Status --}}
    <div class="col-md-6 mb-3">
        <label for="status" class="form-label">
            Status <span class="text-danger">*</span>
        </label>

        @php
            $status = old('status', $product?->status ?? 1);
        @endphp

        <select
            name="status"
            id="status"
            class="form-control @error('status') is-invalid @enderror"
            required
        >
            <option value="1" @selected((int) $status === 1)>
                Active
            </option>

            <option value="0" @selected((int) $status === 0)>
                Inactive
            </option>
        </select>

        @error('status')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>
