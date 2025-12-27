@extends('layouts.admin.app')

@section('title', translate('Wallet Balance Summary'))

@section('content')
<div class="content container-fluid">
    {{-- Page Header --}}
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
        <h2 class="h1 mb-0 d-flex align-items-center gap-2">
            <img src="{{asset('/public/assets/admin/img/wallet.png')}}" alt="" class="width-24">
            <span>{{translate('Wallet Balance Summary')}}</span>
        </h2>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" onclick="printReport()">
                <i class="tio-print"></i> {{translate('Print Report')}}
            </button>
            <a href="{{route('admin.customer.wallet.balance-summary.export', request()->all())}}" class="btn btn-success">
                <i class="tio-download"></i> {{translate('Export Excel')}}
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{translate('Total Customers')}}</h6>
                            <h3 class="mb-0">{{$statistics['total_customers']}}</h3>
                        </div>
                        <div class="avatar avatar-lg avatar-soft-primary rounded-circle">
                            <i class="tio-group"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{translate('Total Balance')}}</h6>
                            <h3 class="mb-0 text-success">{{Helpers::set_symbol($statistics['total_balance'])}}</h3>
                        </div>
                        <div class="avatar avatar-lg avatar-soft-success rounded-circle">
                            <i class="tio-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{translate('Positive Balances')}}</h6>
                            <h3 class="mb-0">{{$statistics['positive_balance_count']}}</h3>
                            <small class="text-success">{{Helpers::set_symbol($statistics['positive_balance_sum'])}}</small>
                        </div>
                        <div class="avatar avatar-lg avatar-soft-success rounded-circle">
                            <i class="tio-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{translate('Average Balance')}}</h6>
                            <h3 class="mb-0">{{Helpers::set_symbol($statistics['average_balance'])}}</h3>
                        </div>
                        <div class="avatar avatar-lg avatar-soft-info rounded-circle">
                            <i class="tio-chart-bar-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title">
                <i class="tio-filter-outlined"></i>
                {{translate('Filter Options')}}
            </h5>
        </div>
        <div class="card-body">
            <form action="{{route('admin.customer.wallet.balance-summary')}}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{translate('Search Customer')}}</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="{{translate('Name, Phone, Email')}}"
                                   value="{{request('search')}}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{translate('Balance Filter')}}</label>
                            <select name="balance_filter" class="form-control">
                                <option value="">{{translate('All Balances')}}</option>
                                <option value="positive" {{request('balance_filter') == 'positive' ? 'selected' : ''}}>
                                    {{translate('Positive Balance')}}
                                </option>
                                <option value="zero" {{request('balance_filter') == 'zero' ? 'selected' : ''}}>
                                    {{translate('Zero Balance')}}
                                </option>
                                <option value="negative" {{request('balance_filter') == 'negative' ? 'selected' : ''}}>
                                    {{translate('Negative Balance')}}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{translate('Min Balance')}}</label>
                            <input type="number" name="min_balance" class="form-control" 
                                   step="0.01"
                                   value="{{request('min_balance')}}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{translate('Max Balance')}}</label>
                            <input type="number" name="max_balance" class="form-control" 
                                   step="0.01"
                                   value="{{request('max_balance')}}">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{translate('Sort By')}}</label>
                            <select name="sort_by" class="form-control">
                                <option value="name" {{request('sort_by') == 'name' ? 'selected' : ''}}>
                                    {{translate('Name')}}
                                </option>
                                <option value="balance" {{request('sort_by') == 'balance' ? 'selected' : ''}}>
                                    {{translate('Balance')}}
                                </option>
                                <option value="created_at" {{request('sort_by') == 'created_at' ? 'selected' : ''}}>
                                    {{translate('Join Date')}}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{route('admin.customer.wallet.balance-summary')}}" class="btn btn-secondary">
                                {{translate('Reset')}}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="tio-filter-list"></i> {{translate('Filter')}}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Customer Balance Table --}}
    <div class="card">
        <div class="card-header border-0">
            <h5 class="card-title">
                <i class="tio-table"></i>
                {{translate('Customer Balance List')}}
            </h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{translate('SL')}}</th>
                            <th>{{translate('Customer')}}</th>
                            <th>{{translate('Contact')}}</th>
                            <th class="text-right">{{translate('Wallet Balance')}}</th>
                            <th class="text-center">{{translate('Status')}}</th>
                            <th class="text-center">{{translate('Orders')}}</th>
                            <th>{{translate('Last Transaction')}}</th>
                            <th class="text-center">{{translate('Actions')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $key => $customer)
                        <tr>
                            <td>{{$customers->firstItem() + $key}}</td>
                            <td>
                                <a href="{{route('admin.customer.view', ['user_id' => $customer->id])}}" 
                                   class="media align-items-center">
                                    <img class="avatar rounded-circle mr-3" 
                                         src="{{$customer->image ? asset('storage/app/public/profile/'.$customer->image) : asset('public/assets/admin/img/160x160/img1.jpg')}}" 
                                         alt="{{$customer->name}}"
                                         onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'">
                                    <div class="media-body">
                                        <h5 class="mb-0">{{$customer->name}}</h5>
                                        <small class="text-muted">ID: #{{$customer->id}}</small>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <div>
                                    <i class="tio-call-talking-quiet"></i> {{$customer->phone}}
                                </div>
                                @if($customer->email)
                                <div class="text-muted small">
                                    <i class="tio-email"></i> {{$customer->email}}
                                </div>
                                @endif
                            </td>
                            <td class="text-right">
                                <h5 class="mb-0 
                                    {{$customer->wallet_balance > 0 ? 'text-success' : 
                                      ($customer->wallet_balance < 0 ? 'text-danger' : 'text-muted')}}">
                                    {{Helpers::set_symbol($customer->wallet_balance)}}
                                </h5>
                            </td>
                            <td class="text-center">
                                @if($customer->wallet_balance > 0)
                                    <span class="badge badge-soft-success">{{translate('Credit')}}</span>
                                @elseif($customer->wallet_balance < 0)
                                    <span class="badge badge-soft-danger">{{translate('Debit')}}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{translate('Zero')}}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-soft-primary">{{$customer->orders_count ?? 0}}</span>
                            </td>
                            <td>
                                @php
                                    $lastTransaction = $customer->walletTransactions->first();
                                @endphp
                                @if($lastTransaction && $lastTransaction->created_at)
                                    <div>{{date('d M Y', strtotime($lastTransaction->created_at))}}</div>
                                    <small class="text-muted">{{date('h:i A', strtotime($lastTransaction->created_at))}}</small>
                                @else
                                    <span class="text-muted">{{translate('No transactions')}}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{route('admin.customer.wallet.statement', $customer->id)}}" 
                                       class="btn btn-sm btn-white"
                                       title="{{translate('View Statement')}}">
                                        <i class="tio-visible"></i>
                                    </a>
                                    <a href="{{route('admin.customer.view', ['user_id' => $customer->id])}}" 
                                       class="btn btn-sm btn-white"
                                       title="{{translate('View Profile')}}">
                                        <i class="tio-user"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <img src="{{asset('/public/assets/admin/img/empty.png')}}" alt="" class="mb-3" width="100">
                                <p class="text-muted">{{translate('No customers found')}}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($customers->hasPages())
            <div class="page-area px-4 pb-3">
                <div class="d-flex align-items-center justify-content-end">
                    {!! $customers->appends(request()->query())->links() !!}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function printReport() {
    const params = new URLSearchParams(window.location.search);
    const printUrl = "{{route('admin.customer.wallet.balance-summary.print')}}" + '?' + params.toString();
    window.open(printUrl, '_blank');
}
</script>
@endpush