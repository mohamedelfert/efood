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
                        <form action="{{route('admin.customer.cashback.store')}}" method="post" id="cashback-form">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ translate('Cashback Title') }}</label>
                                        <input type="text" name="title" class="form-control" 
                                               value="{{old('title')}}"
                                               placeholder="{{ translate('Ex: Wallet Top-up Cashback') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ translate('Description') }}</label>
                                        <input type="text" name="description" class="form-control" 
                                               value="{{old('description')}}"
                                               placeholder="{{ translate('Ex: Get 5% cashback') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Branch')}}</label>
                                        <select name="branch_id" class="form-control">
                                            <option value="">{{translate('All Branches (Global)')}}</option>
                                            @foreach(\App\Model\Branch::active()->get() as $branch)
                                                <option value="{{$branch->id}}" {{old('branch_id') == $branch->id ? 'selected' : ''}}>
                                                    {{$branch->name}}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">{{translate('Leave empty for global cashback')}}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Cashback For')}}</label>
                                        <select name="type" class="form-control" required>
                                            <option value="wallet_topup" {{old('type') == 'wallet_topup' ? 'selected' : ''}}>
                                                {{translate('Wallet Top-up')}}
                                            </option>
                                            <option value="add_fund" {{old('type') == 'add_fund' ? 'selected' : ''}}>
                                                {{translate('Add Fund (Dashboard)')}}
                                            </option>
                                            <option value="order" {{old('type') == 'order' ? 'selected' : ''}}>
                                                {{translate('Orders')}}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Cashback Type')}}</label>
                                        <select name="cashback_type" class="form-control" id="cashback_type" required>
                                            <option value="percentage" {{old('cashback_type') == 'percentage' ? 'selected' : ''}}>
                                                {{translate('Percentage')}} (%)
                                            </option>
                                            <option value="fixed" {{old('cashback_type') == 'fixed' ? 'selected' : ''}}>
                                                {{translate('Fixed Amount')}}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Cashback Value')}}
                                            <span id="cashback_unit">(%)</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="cashback_value" 
                                               value="{{old('cashback_value')}}"
                                               class="form-control" 
                                               placeholder="{{ translate('Ex: 5') }}" required>
                                        <small class="text-muted">{{translate('No maximum limit - full value will be given')}}</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Minimum Amount')}} ({{Helpers::currency_symbol()}})</label>
                                        <input type="number" step="0.01" min="0" name="min_amount" 
                                               value="{{old('min_amount')}}"
                                               class="form-control" 
                                               placeholder="{{ translate('Ex: 100') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Start Date')}}</label>
                                        <input type="date" name="start_date" id="start_date" 
                                               value="{{old('start_date', date('Y-m-d'))}}"
                                               min="{{date('Y-m-d')}}"
                                               class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('End Date')}}</label>
                                        <input type="date" name="end_date" id="end_date" 
                                               value="{{old('end_date', date('Y-m-d', strtotime('+1 year')))}}"
                                               min="{{date('Y-m-d')}}"
                                               class="form-control" required>
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
                                    <th>{{translate('Branch')}}</th>
                                    <th>{{translate('Type')}}</th>
                                    <th>{{translate('Cashback')}}</th>
                                    <th>{{translate('Min Amount')}}</th>
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
                                            @if($setting->branch_id)
                                                <span class="badge badge-soft-primary">
                                                    {{$setting->branch->name ?? 'N/A'}}
                                                </span>
                                            @else
                                                <span class="badge badge-soft-secondary">
                                                    <i class="tio-world"></i> {{translate('All Branches')}}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-{{$setting->type == 'wallet_topup' ? 'info' : 'success'}}">
                                                {{translate(str_replace('_', ' ', $setting->type))}}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>
                                                {{$setting->cashback_type == 'percentage' 
                                                    ? $setting->cashback_value . '%' 
                                                    : Helpers::set_symbol($setting->cashback_value)}}
                                            </strong>
                                            <br>
                                            <small class="text-muted">{{translate('No max limit')}}</small>
                                        </td>
                                        <td>{{Helpers::set_symbol($setting->min_amount)}}</td>
                                        <td>
                                            <small>
                                                {{$setting->start_date->format('d M Y')}} - {{$setting->end_date->format('d M Y')}}
                                            </small>
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
    
    // Update unit display when cashback type changes
    $('#cashback_type').on('change', function() {
        if ($(this).val() === 'percentage') {
            $('#cashback_unit').text('(%)');
        } else {
            $('#cashback_unit').text('({{Helpers::currency_symbol()}})');
        }
    });

    // Date validation: End date must be after start date
    $('#start_date').on('change', function() {
        var startDate = $(this).val();
        $('#end_date').attr('min', startDate);
        
        // If end date is before start date, update it
        var endDate = $('#end_date').val();
        if (endDate && endDate < startDate) {
            $('#end_date').val(startDate);
        }
    });

    // Validate on form submit
    $('#cashback-form').on('submit', function(e) {
        var startDate = new Date($('#start_date').val());
        var endDate = new Date($('#end_date').val());
        
        if (endDate < startDate) {
            e.preventDefault();
            toastr.error('{{translate("End date must be after or equal to start date")}}');
            return false;
        }
    });

    // Set initial unit based on selected type
    $(document).ready(function() {
        if ($('#cashback_type').val() === 'fixed') {
            $('#cashback_unit').text('({{Helpers::currency_symbol()}})');
        }
    });
</script>
@endpush