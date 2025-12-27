@extends('layouts.admin.app')

@section('title', translate('Customer Wallet Statement'))

@section('content')
<div class="content container-fluid">
    {{-- Page Header --}}
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
        <div>
            <a href="{{route('admin.customer.wallet.balance-summary')}}" class="btn btn-sm btn-secondary mb-2">
                <i class="tio-back-ui"></i> {{translate('Back to Summary')}}
            </a>
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img src="{{asset('/public/assets/admin/img/wallet.png')}}" alt="" class="width-24">
                <span>{{translate('Wallet Statement')}}</span>
            </h2>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" onclick="printStatement()">
                <i class="tio-print"></i> {{translate('Print Statement')}}
            </button>
            <a href="{{route('admin.customer.wallet.statement.export', ['customer_id' => $customer->id] + request()->all())}}" 
               class="btn btn-success">
                <i class="tio-download"></i> {{translate('Export Excel')}}
            </a>
        </div>
    </div>

    {{-- Customer Info Card --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <img class="avatar avatar-xl rounded-circle" 
                             src="{{$customer->imageFullPath}}" 
                             alt="{{$customer->name}}">
                        <div>
                            <h4 class="mb-1">{{$customer->name}}</h4>
                            <div class="text-muted">
                                <i class="tio-call-talking-quiet"></i> {{$customer->phone}}
                            </div>
                            @if($customer->email)
                            <div class="text-muted">
                                <i class="tio-email"></i> {{$customer->email}}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center p-3 bg-soft-info rounded">
                                <h6 class="text-muted mb-1">{{translate('Current Balance')}}</h6>
                                <h3 class="mb-0 
                                    {{$customer->wallet_balance > 0 ? 'text-success' : 
                                      ($customer->wallet_balance < 0 ? 'text-danger' : 'text-muted')}}">
                                    {{Helpers::set_symbol($customer->wallet_balance)}}
                                </h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-soft-primary rounded">
                                <h6 class="text-muted mb-1">{{translate('Total Orders')}}</h6>
                                <h3 class="mb-0">{{$customer->orders_count ?? 0}}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Period Statistics --}}
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{translate('Opening Balance')}}</h6>
                            <h4 class="mb-0">{{Helpers::set_symbol($periodStats['opening_balance'])}}</h4>
                        </div>
                        <div class="avatar avatar-lg avatar-soft-secondary rounded-circle">
                            <i class="tio-briefcase"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{translate('Total Credit')}}</h6>
                            <h4 class="mb-0 text-success">{{Helpers::set_symbol($periodStats['total_credit'])}}</h4>
                        </div>
                        <div class="avatar avatar-lg avatar-soft-success rounded-circle">
                            <i class="tio-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{translate('Total Debit')}}</h6>
                            <h4 class="mb-0 text-danger">{{Helpers::set_symbol($periodStats['total_debit'])}}</h4>
                        </div>
                        <div class="avatar avatar-lg avatar-soft-danger rounded-circle">
                            <i class="tio-trending-down"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">{{translate('Transactions')}}</h6>
                            <h4 class="mb-0">{{$periodStats['transaction_count']}}</h4>
                        </div>
                        <div class="avatar avatar-lg avatar-soft-info rounded-circle">
                            <i class="tio-layers"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction Breakdown --}}
    @if($transactionBreakdown->count() > 0)
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title">
                <i class="tio-chart-pie"></i>
                {{translate('Transaction Breakdown by Type')}}
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($transactionBreakdown as $breakdown)
                <div class="col-md-3 mb-3">
                    <div class="border rounded p-3">
                        <h6 class="mb-2">{{translate($breakdown->transaction_type)}}</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">{{translate('Credit')}}:</small>
                            <strong class="text-success">{{Helpers::set_symbol($breakdown->total_credit)}}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">{{translate('Debit')}}:</small>
                            <strong class="text-danger">{{Helpers::set_symbol($breakdown->total_debit)}}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">{{translate('Count')}}:</small>
                            <strong>{{$breakdown->count}}</strong>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Filter Card --}}
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title">
                <i class="tio-filter-outlined"></i>
                {{translate('Filter Transactions')}}
            </h5>
        </div>
        <div class="card-body">
            <form action="{{route('admin.customer.wallet.statement', $customer->id)}}" method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{translate('From Date')}}</label>
                            <input type="date" name="from" class="form-control" 
                                   value="{{request('from')}}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{translate('To Date')}}</label>
                            <input type="date" name="to" class="form-control" 
                                   value="{{request('to')}}">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{translate('Transaction Type')}}</label>
                            <select name="transaction_type" class="form-control">
                                <option value="">{{translate('All Types')}}</option>
                                <option value="add_fund_by_admin" {{request('transaction_type') == 'add_fund_by_admin' ? 'selected' : ''}}>
                                    {{translate('Add Fund by Admin')}}
                                </option>
                                <option value="order_place" {{request('transaction_type') == 'order_place' ? 'selected' : ''}}>
                                    {{translate('Order Place')}}
                                </option>
                                <option value="loyalty_point_to_wallet" {{request('transaction_type') == 'loyalty_point_to_wallet' ? 'selected' : ''}}>
                                    {{translate('Loyalty Point to Wallet')}}
                                </option>
                                <option value="referral_order_place" {{request('transaction_type') == 'referral_order_place' ? 'selected' : ''}}>
                                    {{translate('Referral Order Place')}}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{route('admin.customer.wallet.statement', $customer->id)}}" class="btn btn-secondary">
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

    {{-- Transactions Table --}}
    <div class="card">
        <div class="card-header border-0">
            <h5 class="card-title">
                <i class="tio-money"></i>
                {{translate('Transaction History')}}
            </h5>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{translate('SL')}}</th>
                            <th>{{translate('Transaction ID')}}</th>
                            <th>{{translate('Date & Time')}}</th>
                            <th>{{translate('Type')}}</th>
                            <th>{{translate('Reference')}}</th>
                            <th class="text-right">{{translate('Credit')}}</th>
                            <th class="text-right">{{translate('Debit')}}</th>
                            <th class="text-right">{{translate('Balance')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $key => $transaction)
                        <tr>
                            <td>{{$transactions->firstItem() + $key}}</td>
                            <td>
                                <span class="font-weight-bold">{{$transaction->transaction_id}}</span>
                            </td>
                            <td>
                                <div>{{date('d M Y', strtotime($transaction->created_at))}}</div>
                                <small class="text-muted">{{date('h:i A', strtotime($transaction->created_at))}}</small>
                            </td>
                            <td>
                                <span class="badge badge-soft-{{
                                    $transaction->transaction_type == 'add_fund_by_admin' ? 'success' :
                                    ($transaction->transaction_type == 'order_place' ? 'info' :
                                    ($transaction->transaction_type == 'loyalty_point_to_wallet' ? 'warning' : 'primary'))
                                }}">
                                    {{translate($transaction->transaction_type)}}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{$transaction->reference ?? 'N/A'}}</small>
                            </td>
                            <td class="text-right">
                                @if($transaction->credit > 0)
                                <span class="text-success font-weight-bold">
                                    +{{Helpers::set_symbol($transaction->credit)}}
                                </span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($transaction->debit > 0)
                                <span class="text-danger font-weight-bold">
                                    -{{Helpers::set_symbol($transaction->debit)}}
                                </span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="font-weight-bold">
                                    {{Helpers::set_symbol($transaction->balance)}}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <img src="{{asset('/public/assets/admin/img/empty.png')}}" alt="" class="mb-3" width="100">
                                <p class="text-muted">{{translate('No transactions found')}}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($transactions->hasPages())
            <div class="page-area px-4 pb-3">
                <div class="d-flex align-items-center justify-content-end">
                    {!! $transactions->appends(request()->query())->links() !!}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script>
function printStatement() {
    const params = new URLSearchParams(window.location.search);
    const printUrl = "{{route('admin.customer.wallet.statement.print', $customer->id)}}" + '?' + params.toString();
    window.open(printUrl, '_blank');
}
</script>
@endpush