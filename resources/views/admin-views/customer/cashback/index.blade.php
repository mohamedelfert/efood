@extends('layouts.admin.app')

@section('title', translate('Cashback Settings'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/cashback.png')}}" class="width-24" alt="">
                </span>
                <span class="ml-2">{{translate('Cashback Settings')}}</span>
            </h1>
            <a href="{{route('admin.customer.cashback.statistics')}}" class="btn btn-outline-primary">
                <i class="tio-chart-bar-4"></i> {{translate('View Statistics')}}
            </a>
        </div>

        <div class="row g-2">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.customer.cashback.store')}}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ translate('Cashback Title') }}</label>
                                        <input type="text" name="title" class="form-control" placeholder="{{ translate('Ex: Wallet Top-up Cashback') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ translate('Description') }}</label>
                                        <input type="text" name="description" class="form-control" placeholder="{{ translate('Ex: Get 5% cashback') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Cashback For')}}</label>
                                        <select name="type" class="form-control" required>
                                            <option value="wallet_topup">{{translate('Wallet Top-up')}}</option>
                                            <option value="order">{{translate('Orders')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Cashback Type')}}</label>
                                        <select name="cashback_type" class="form-control" id="cashback_type" required>
                                            <option value="percentage">{{translate('Percentage')}} (%)</option>
                                            <option value="fixed">{{translate('Fixed Amount')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Cashback Value')}}
                                            <span id="cashback_unit">(%)</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="cashback_value" class="form-control" placeholder="{{ translate('Ex: 5') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Minimum Amount')}} ({{Helpers::currency_symbol()}})</label>
                                        <input type="number" step="0.01" min="0" name="min_amount" class="form-control" placeholder="{{ translate('Ex: 100') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Maximum Cashback')}} ({{Helpers::currency_symbol()}})</label>
                                        <input type="number" step="0.01" min="0" name="max_cashback" class="form-control" placeholder="{{ translate('Ex: 50') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Start Date')}}</label>
                                        <input type="date" name="start_date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('End Date')}}</label>
                                        <input type="date" name="end_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end">
                                <button type="reset" class="btn btn-secondary">{{translate('Reset')}}</button>
                                <button type="submit" class="btn btn-primary">{{translate('Submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-top px-card pt-4">
                        <div class="row justify-content-between align-items-center gy-2">
                            <div class="col-sm-4 col-md-6 col-lg-8">
                                <h5 class="d-flex align-items-center gap-2 mb-0">
                                    {{translate('Cashback Settings')}}
                                    <span class="badge badge-soft-dark rounded-50 fz-12">{{ $settings->total() }}</span>
                                </h5>
                            </div>
                            <div class="col-sm-8 col-md-6 col-lg-4">
                                <form action="{{url()->current()}}" method="GET">
                                    <div class="input-group">
                                        <input type="search" name="search" class="form-control" 
                                               placeholder="{{translate('Search')}}" value="{{$search}}">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">{{translate('Search')}}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="py-4">
                        <div class="table-responsive">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                <tr>
                                    <th>{{translate('SL')}}</th>
                                    <th>{{translate('Title')}}</th>
                                    <th>{{translate('Type')}}</th>
                                    <th>{{translate('Cashback')}}</th>
                                    <th>{{translate('Min Amount')}}</th>
                                    <th>{{translate('Max Cashback')}}</th>
                                    <th>{{translate('Valid Period')}}</th>
                                    <th>{{translate('Status')}}</th>
                                    <th class="text-center">{{translate('Action')}}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($settings as $key => $setting)
                                    <tr>
                                        <td>{{$key + $settings->firstItem()}}</td>
                                        <td>{{$setting->title}}</td>
                                        <td>
                                            <span class="badge badge-soft-{{$setting->type == 'wallet_topup' ? 'info' : 'success'}}">
                                                {{translate(str_replace('_', ' ', $setting->type))}}
                                            </span>
                                        </td>
                                        <td>
                                            {{$setting->cashback_type == 'percentage' 
                                                ? $setting->cashback_value . '%' 
                                                : Helpers::set_symbol($setting->cashback_value)}}
                                        </td>
                                        <td>{{Helpers::set_symbol($setting->min_amount)}}</td>
                                        <td>{{Helpers::set_symbol($setting->max_cashback)}}</td>
                                        <td>
                                            {{$setting->start_date->format('d M Y')}} - {{$setting->end_date->format('d M Y')}}
                                        </td>
                                        <td>
                                            <label class="switcher">
                                                <input type="checkbox" class="switcher_input status-change" 
                                                       {{$setting->status ? 'checked' : ''}}
                                                       data-url="{{route('admin.customer.cashback.status', $setting->id)}}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a class="btn btn-outline-info btn-sm square-btn" 
                                                   href="{{route('admin.customer.cashback.edit', $setting->id)}}">
                                                    <i class="tio-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm square-btn form-alert"
                                                        data-id="cashback-{{$setting->id}}" 
                                                        data-message="{{translate('Want to delete this cashback setting?')}}">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </div>
                                            <form action="{{route('admin.customer.cashback.delete', $setting->id)}}" 
                                                  method="post" id="cashback-{{$setting->id}}">
                                                @csrf @method('delete')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="table-responsive mt-4 px-3">
                            <div class="d-flex justify-content-lg-end">
                                {!! $settings->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    "use strict";
    
    $('#cashback_type').on('change', function() {
        if ($(this).val() === 'percentage') {
            $('#cashback_unit').text('(%)');
        } else {
            $('#cashback_unit').text('({{Helpers::currency_symbol()}})');
        }
    });
</script>
@endpush