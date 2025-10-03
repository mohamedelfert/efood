@extends('layouts.admin.app')

@section('title', translate('exchange_rates'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item">
                            <a class="breadcrumb-link" href="{{route('admin.currency.index')}}">
                                {{translate('currency_management')}}
                            </a>
                        </li>
                        <li class="breadcrumb-item active">{{translate('exchange_rates')}}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title">{{translate('update_exchange_rates')}}</h1>
            </div>
        </div>
    </div>

    <form action="#" method="post">
        @csrf
        <div class="card">
            <div class="card-header">
                <div class="row justify-content-between align-items-center">
                    <div class="col">
                        <h4 class="card-title">{{translate('current_exchange_rates')}}</h4>
                        <p class="card-text">{{translate('update_exchange_rates_against_primary_currency')}}</p>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center">
                            <span class="text-muted mr-2">{{translate('last_updated')}}:</span>
                            <span class="badge badge-soft-info">{{date('M d, Y H:i')}}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @php($primaryCurrency = $currencies->where('is_primary', true)->first())
                
                @if($primaryCurrency)
                    <div class="alert alert-info">
                        <i class="tio-info-outlined"></i>
                        {{translate('primary_currency')}}: <strong>{{$primaryCurrency->name}} ({{$primaryCurrency->code}})</strong>
                        - {{translate('exchange_rate_is_fixed_at_1.0000')}}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>{{translate('currency')}}</th>
                                <th>{{translate('code')}}</th>
                                <th>{{translate('symbol')}}</th>
                                <th>{{translate('current_rate')}}</th>
                                <th>{{translate('example')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currencies as $currency)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($currency->is_primary)
                                                <span class="badge badge-primary mr-2">{{translate('primary')}}</span>
                                            @endif
                                            {{$currency->name}}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-secondary">{{$currency->code}}</span>
                                    </td>
                                    <td>{{$currency->symbol}}</td>
                                    <td>
                                        <span class="font-weight-bold">{{number_format($currency->exchange_rate, 4)}}</span>
                                    </td>
                                    <td>
                                        @if($primaryCurrency)
                                            {{$primaryCurrency->symbol}} 1.00 = {{$currency->formatAmount($currency->exchange_rate)}}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning mt-3">
                    <h6 class="alert-heading">{{translate('Important Notes')}}</h6>
                    <ul class="mb-0">
                        <li>{{translate('Exchange rates affect all product prices and orders')}}</li>
                        <li>{{translate('Primary currency rate is always 1.0000 and cannot be changed')}}</li>
                        <li>{{translate('You can edit individual currencies from the currency list page')}}</li>
                        <li>{{translate('Consider integrating with external API for real-time rates')}}</li>
                    </ul>
                </div>
            </div>

            <div class="card-footer">
                <div class="row justify-content-between align-items-center">
                    <div class="col">
                        <small class="text-muted">
                            {{translate('to_update_rates_edit_individual_currencies_from_the_main_currency_list')}}
                        </small>
                    </div>
                    <div class="col-auto">
                        <a href="{{route('admin.currency.index')}}" class="btn btn-primary">
                            {{translate('back_to_currency_list')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection