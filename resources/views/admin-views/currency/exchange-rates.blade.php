@extends('layouts.admin.app')

@section('title', translate('Exchange Rates'))

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-no-gutter">
                        <li class="breadcrumb-item">
                            <a class="breadcrumb-link" href="{{route('admin.currency.index')}}">
                                {{translate('Currency Management')}}
                            </a>
                        </li>
                        <li class="breadcrumb-item active">{{translate('Exchange Rates')}}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title">{{translate('Update Exchange Rates')}}</h1>
            </div>
        </div>
    </div>

    <form action="#" method="post">
        @csrf
        <div class="card">
            <div class="card-header">
                <div class="row justify-content-between align-items-center">
                    <div class="col">
                        <h4 class="card-title">{{translate('Current Exchange Rates')}}</h4>
                        <p class="card-text">{{translate('Update exchange rates against primary currency')}}</p>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center">
                            <span class="text-muted mr-2">{{translate('Last Updated')}}:</span>
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
                        {{translate('Primary Currency')}}: <strong>{{$primaryCurrency->name}} ({{$primaryCurrency->code}})</strong>
                        - {{translate('Exchange rate is fixed at 1.0000')}}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-borderless table-thead-bordered table-align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>{{translate('Currency')}}</th>
                                <th>{{translate('Code')}}</th>
                                <th>{{translate('Symbol')}}</th>
                                <th>{{translate('Current Rate')}}</th>
                                <th>{{translate('Example')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($currencies as $currency)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($currency->is_primary)
                                                <span class="badge badge-primary mr-2">{{translate('Primary')}}</span>
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
                            {{translate('To update rates, edit individual currencies from the main currency list')}}
                        </small>
                    </div>
                    <div class="col-auto">
                        <a href="{{route('admin.currency.index')}}" class="btn btn-primary">
                            {{translate('Back to Currency List')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection