@extends('layouts.admin.app')

@section('title', translate('Branch Wallet Transactions'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/branch.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Wallet Transactions')}} - {{$branch->name}}
                </span>
            </h2>
        </div>

        <div class="card">
            <div class="card-header pb-0 px-card border-0">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">{{translate('Transaction History')}} <span
                            class="badge badge-soft-dark rounded-50 fz-12 ml-1">{{ $transactions->total() }}</span></h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">{{translate('Current Balance')}}:</span>
                        <h4 class="mb-0">{{\App\CentralLogics\Helpers::set_symbol($branch->wallet_balance)}}</h4>
                    </div>
                </div>
            </div>

            <div class="card-body px-0">
                <div class="table-responsive datatable-custom">
                    <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th>{{translate('SL')}}</th>
                                <th>{{translate('Transaction_ID')}}</th>
                                <th>{{translate('Type')}}</th>
                                <th>{{translate('Reference')}}</th>
                                <th>{{translate('Debit')}}</th>
                                <th>{{translate('Credit')}}</th>
                                <th>{{translate('Balance')}}</th>
                                <th>{{translate('Created_At')}}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($transactions as $key => $transaction)
                                <tr>
                                    <td>{{$transactions->firstItem() + $key}}</td>
                                    <td>{{$transaction->transaction_id}}</td>
                                    <td>
                                        <span class="badge badge-soft-{{ $transaction->credit > 0 ? 'success' : 'danger' }}">
                                            {{translate(str_replace('_', ' ', $transaction->transaction_type))}}
                                        </span>
                                    </td>
                                    <td>{{$transaction->reference ?? translate('N/A')}}</td>
                                    <td>{{\App\CentralLogics\Helpers::set_symbol($transaction->debit)}}</td>
                                    <td>{{\App\CentralLogics\Helpers::set_symbol($transaction->credit)}}</td>
                                    <td>{{\App\CentralLogics\Helpers::set_symbol($transaction->balance)}}</td>
                                    <td>{{date('Y-m-d H:i:s', strtotime($transaction->created_at))}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4 px-3">
                    <div class="d-flex justify-content-lg-end">
                        {!! $transactions->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection