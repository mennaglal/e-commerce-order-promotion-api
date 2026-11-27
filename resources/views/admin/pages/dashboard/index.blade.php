@extends('admin.layouts.app')

@section('style')
    <style>
        .dashboard-heading {
            margin: 22px 0 25px;
        }

        .dashboard-heading h3 {
            font-weight: 700;
            color: #263238;
            margin-bottom: 5px;
        }

        .dashboard-heading p {
            color: #89939d;
            font-size: 13px;
            margin: 0;
        }

        .dashboard-stat {
            border: 0;
            border-radius: 12px;
            min-height: 125px;
            box-shadow: 0 4px 18px rgba(30,45,62,.05);
            transition: .2s;
        }

        .dashboard-stat:hover {
            transform: translateY(-3px);
        }

        .dashboard-stat .card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
        }

        .stat-label {
            color: #89939d;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .stat-value {
            color: #263238;
            font-size: 25px;
            font-weight: 700;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        .stat-blue {
            background: #edf3ff;
            color: #4778d8;
        }

        .stat-green {
            background: #e8f8f1;
            color: #20a477;
        }

        .stat-gold {
            background: #fff5df;
            color: #c28a20;
        }

        .stat-red {
            background: #fff0ef;
            color: #d45b55;
        }

        .stat-purple {
            background: #f2edff;
            color: #8061c9;
        }

        .dashboard-panel {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(30,45,62,.05);
            height: 100%;
        }

        .dashboard-panel .card-header {
            background: #fff;
            border-bottom: 1px solid #f0f2f4;
            padding: 16px 20px;
        }

        .dashboard-panel .card-header h5 {
            font-size: 15px;
            font-weight: 700;
            margin: 0;
            color: #303b46;
        }

        .dashboard-panel .card-body {
            padding: 20px;
        }

        .dashboard-table {
            margin-bottom: 0;
        }

        .dashboard-table th {
            border-top: 0;
            color: #89939d;
            font-size: 12px;
            white-space: nowrap;
        }

        .dashboard-table td {
            vertical-align: middle;
            font-size: 12px;
        }

        .dashboard-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .dashboard-badge.success {
            color: #16835f;
            background: #e6f7ef;
        }

        .dashboard-badge.warning {
            color: #9a6b12;
            background: #fff4d9;
        }

        .dashboard-badge.danger {
            color: #b94a45;
            background: #ffeded;
        }

        .dashboard-badge.secondary {
            color: #68737d;
            background: #eef0f2;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            gap: 15px;
            height: 220px;
            border-bottom: 1px solid #edf0f2;
            padding-top: 15px;
        }

        .chart-bar-item {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }

        .chart-bar-value,
        .chart-bar-label {
            font-size: 11px;
            color: #89939d;
            white-space: nowrap;
        }

        .chart-bar {
            width: min(38px, 75%);
            min-height: 4px;
            background: linear-gradient(
                180deg,
                var(--light-primary-color),
                var(--primary-color)
            );
            border-radius: 6px 6px 0 0;
        }

        .revenue-value {
            font-size: 28px;
            font-weight: 700;
            color: #20a477;
        }

        .dashboard-empty {
            text-align: center;
            color: #9aa3ab;
            padding: 25px;
        }

        .dashboard-link {
            color: var(--primary-color);
            font-size: 12px;
            font-weight: 600;
        }

        @media(max-width: 767px) {
            .stat-value {
                font-size: 21px;
            }

            .chart-bars {
                gap: 5px;
            }
        }
    </style>
@endsection

@section('content')

    {{-- Page Heading --}}
    <div class="dashboard-heading">
        <h3>{{ __('dashboard.title') }}</h3>
        <p>
            <i class="fe fe-calendar mr-1"></i>
            {{ now()->translatedFormat('l, d F Y') }}
        </p>
    </div>

    {{-- Statistics --}}
    <div class="row">

        @php
            $cards = [
                [
                    'label' => 'Total Products',
                    'value' => $stats['total_products'],
                    'icon' => 'box',
                    'color' => 'blue',
                ],
                [
                    'label' => 'Active Products',
                    'value' => $stats['active_products'],
                    'icon' => 'check-circle',
                    'color' => 'green',
                ],
                [
                    'label' => 'Low Stock Products',
                    'value' => $stats['low_stock_products'],
                    'icon' => 'trending-down',
                    'color' => 'gold',
                ],
                [
                    'label' => 'Out of Stock',
                    'value' => $stats['out_of_stock_products'],
                    'icon' => 'alert-triangle',
                    'color' => 'red',
                ],
                [
                    'label' => 'Total Orders',
                    'value' => $stats['total_orders'],
                    'icon' => 'shopping-cart',
                    'color' => 'blue',
                ],
                [
                    'label' => "Today's Orders",
                    'value' => $stats['today_orders'],
                    'icon' => 'calendar',
                    'color' => 'purple',
                ],
                [
                    'label' => 'Total Customers',
                    'value' => $stats['total_customers'],
                    'icon' => 'users',
                    'color' => 'green',
                ],
                [
                    'label' => 'Total Promotions',
                    'value' => $stats['total_promotions'],
                    'icon' => 'tag',
                    'color' => 'gold',
                ],
                [
                    'label' => 'Active Promotions',
                    'value' => $stats['active_promotions'],
                    'icon' => 'gift',
                    'color' => 'purple',
                ],
            ];
        @endphp

        @foreach($cards as $card)
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card dashboard-stat">
                    <div class="card-body">
                        <div>
                            <div class="stat-label">
                                {{ $card['label'] }}
                            </div>

                            <div class="stat-value">
                                {{ number_format($card['value']) }}
                            </div>
                        </div>

                        <div class="stat-icon stat-{{ $card['color'] }}">
                            <i class="fe fe-{{ $card['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    {{-- Monthly Orders & Revenue --}}
    <div class="row mb-4">

        <div class="col-xl-8 mb-4">
            <div class="card dashboard-panel">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Monthly Orders</h5>
                    <span class="text-muted small">Last 6 Months</span>
                </div>

                <div class="card-body">

                    @php
                        $maxOrders = max($monthlyOrders['data'] ?: [0]);
                        $maxOrders = max($maxOrders, 1);
                    @endphp

                    <div class="chart-bars">

                        @foreach($monthlyOrders['labels'] as $index => $label)
                            <div class="chart-bar-item">

                            <span class="chart-bar-value">
                                {{ number_format($monthlyOrders['data'][$index]) }}
                            </span>

                                <div
                                    class="chart-bar"
                                    style="height: {{ max(4, ($monthlyOrders['data'][$index] / $maxOrders) * 100) }}%">
                                </div>

                                <span class="chart-bar-label">
                                {{ $label }}
                            </span>

                            </div>
                        @endforeach

                    </div>

                </div>
            </div>
        </div>

        {{-- Financial Overview --}}
        <div class="col-xl-4 mb-4">
            <div class="card dashboard-panel">

                <div class="card-header">
                    <h5>Financial Overview</h5>
                </div>

                <div class="card-body">

                    <div class="stat-label">Total Revenue</div>

                    <div class="revenue-value">
                        {{ number_format($stats['total_revenue'], 2) }}
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Paid Orders</span>

                        <strong>
                            {{ number_format($stats['paid_orders']) }}
                        </strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="text-muted small">Total Orders</span>

                        <strong>
                            {{ number_format($stats['total_orders']) }}
                        </strong>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{-- Latest Orders --}}
    <div class="row">

        <div class="col-12 mb-4">
            <div class="card dashboard-panel">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Latest Orders</h5>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">
                        <table class="table dashboard-table">

                            <thead>
                            <tr>
                                <th class="pl-3">Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Payment Status</th>
                                <th>Order Date</th>
                            </tr>
                            </thead>

                            <tbody>

                            @forelse($latestOrders as $order)
                                <tr>

                                    <td class="pl-3 font-weight-bold">
                                        {{ $order->order_number ?? '#' . $order->id }}
                                    </td>

                                    <td>
                                        {{ $order->user?->name ?? 'Guest' }}
                                    </td>

                                    <td class="font-weight-bold">
                                        {{ number_format((float) $order->total, 2) }}
                                    </td>

                                    <td>
                                        @php
                                            $paymentClass = match($order->payment_status) {
                                                'paid' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp

                                        <span class="dashboard-badge {{ $paymentClass }}">
                                            {{ ucfirst($order->payment_status) }}
                                        </span>
                                    </td>

                                    <td class="text-muted">
                                        {{ $order->created_at?->format('d/m/Y') }}
                                    </td>

                                </tr>

                            @empty
                                <tr>
                                    <td colspan="5" class="dashboard-empty">
                                        No orders found.
                                    </td>
                                </tr>
                            @endforelse

                            </tbody>

                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection
