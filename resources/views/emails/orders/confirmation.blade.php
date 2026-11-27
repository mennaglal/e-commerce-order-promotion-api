
<h2>Thank you for your order!</h2>

<p>Hello {{ $order->customer_name }},</p>

<p>Your order has been placed successfully.</p>

<h3>Order #{{ $order->id }}</h3>

<table>
    <thead>
    <tr>
        <th>Product</th>
        <th>Quantity</th>
        <th>Price</th>
    </tr>
    </thead>

    <tbody>
    @foreach ($order->items as $item)
        <tr>
            <td>{{ $item->product_name }}</td>
            <td>{{ $item->product_quantity }}</td>
            <td>{{ $item->product_price }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<p>Subtotal: {{ $order->subtotal }} {{ $order->currency }}</p>

<p>Discount: {{ $order->discount_amount }} {{ $order->currency }}</p>

<h3>Total: {{ $order->total }} {{ $order->currency }}</h3>

<p>Thank you for shopping with us!</p>
