@extends('layouts.admin.app')

@section('title', translate('Cashback Statistics'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <i class="tio-chart-bar-4"></i>
                </span>
                <span class="ml-2">{{translate('Cashback Statistics')}}</span>
            </h1>
            <a href="{{route('admin.customer.cashback.index')}}" class="btn btn-outline-primary">
                <i class="tio-settings"></i> {{translate('Manage Settings')}}
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-2 mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-hover-shadow h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">{{translate('Total Cashback Given')}}</h6>
                        <div class="row align-items-center gx-2 mb-1">
                            <div class="col-12">
                                <h2 class="card-title text-inherit">
                                    {{Helpers::set_symbol($totalCashbackGiven)}}
                                </h2>
                            </div>
                        </div>
                        <span class="badge badge-soft-success">
                            <i class="tio-trending-up"></i> {{translate('All Time')}}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-hover-shadow h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">{{translate('Wallet Top-up Cashback')}}</h6>
                        <div class="row align-items-center gx-2 mb-1">
                            <div class="col-12">
                                <h2 class="card-title text-inherit">
                                    {{Helpers::set_symbol($walletTopupCashback)}}
                                </h2>
                            </div>
                        </div>
                        <span class="badge badge-soft-info">
                            <i class="tio-wallet"></i> {{translate('Top-ups')}}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-hover-shadow h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">{{translate('Order Cashback')}}</h6>
                        <div class="row align-items-center gx-2 mb-1">
                            <div class="col-12">
                                <h2 class="card-title text-inherit">
                                    {{Helpers::set_symbol($orderCashback)}}
                                </h2>
                            </div>
                        </div>
                        <span class="badge badge-soft-primary">
                            <i class="tio-shopping-cart"></i> {{translate('Orders')}}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-hover-shadow h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2">{{translate('This Month')}}</h6>
                        <div class="row align-items-center gx-2 mb-1">
                            <div class="col-12">
                                <h2 class="card-title text-inherit">
                                    {{Helpers::set_symbol($thisMonthCashback)}}
                                </h2>
                            </div>
                        </div>
                        <span class="badge badge-soft-warning">
                            <i class="tio-calendar"></i> {{date('F Y')}}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Users Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-user-big"></i>
                            {{translate('Top 10 Users by Cashback Earned')}}
                        </h5>
                    </div>
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{translate('Rank')}}</th>
                                <th>{{translate('User')}}</th>
                                <th>{{translate('Email')}}</th>
                                <th>{{translate('Phone')}}</th>
                                <th>{{translate('Total Cashback')}}</th>
                                <th class="text-center">{{translate('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($topUsers as $key => $item)
                                <tr>
                                    <td>
                                        <span class="badge badge-soft-dark">
                                            #{{$key + 1}}
                                        </span>
                                    </td>
                                    <td>
                                        <a class="media align-items-center" 
                                           href="{{route('admin.customer.view',[$item->user_id])}}">
                                            <div class="avatar avatar-circle mr-3">
                                                <img class="avatar-img" 
                                                     onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'" 
                                                     src="{{$item->user->image_full_path ?? asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                                     alt="User Image">
                                            </div>
                                            <div class="media-body">
                                                <span class="d-block h5 text-hover-primary mb-0">
                                                    {{$item->user->name ?? translate('Unknown')}}
                                                </span>
                                            </div>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="d-block">
                                            {{$item->user->email ?? 'N/A'}}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="d-block">
                                            {{$item->user->phone ?? 'N/A'}}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-success font-weight-bold">
                                            {{Helpers::set_symbol($item->total_cashback)}}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-white" 
                                           href="{{route('admin.customer.view',[$item->user_id])}}">
                                            <i class="tio-visible-outlined"></i> {{translate('View')}}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="text-center p-4">
                                            <img class="mb-3" 
                                                 src="{{asset('public/assets/admin/img/empty-box.png')}}" 
                                                 alt="{{ translate('No data found') }}" 
                                                 style="width: 100px">
                                            <p class="mb-0">{{translate('No cashback data available yet')}}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cashback Distribution Chart -->
        <div class="row mt-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-chart-pie"></i>
                            {{translate('Cashback Distribution')}}
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="cashbackDistributionChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-header-title">
                            <i class="tio-chart-line"></i>
                            {{translate('Quick Stats')}}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">{{translate('Avg per User')}}</h6>
                                    <h4>{{Helpers::set_symbol($totalCashbackGiven / max(1, $topUsers->count()))}}</h4>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="border rounded p-3">
                                    <h6 class="text-muted mb-2">{{translate('Total Users')}}</h6>
                                    <h4>{{$topUsers->count()}}</h4>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded p-3 bg-soft-success">
                                    <h6 class="text-muted mb-2">{{translate('Cashback ROI')}}</h6>
                                    <p class="mb-0 small">
                                        {{translate('Cashback helps retain customers and increase transaction frequency')}}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    "use strict";

    // Cashback Distribution Chart
    const ctx = document.getElementById('cashbackDistributionChart').getContext('2d');
    const cashbackChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                '{{translate("Wallet Top-up")}}',
                '{{translate("Orders")}}'
            ],
            datasets: [{
                data: [
                    {{$walletTopupCashback}},
                    {{$orderCashback}}
                ],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(75, 192, 192, 0.8)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '{{Helpers::currency_symbol()}}' + context.parsed.toFixed(2);
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>
@endpush