@extends('layouts.admin.app')

@section('title', translate('Edit Cashback Setting'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/cashback.png')}}" class="width-24" alt="">
                </span>
                <span class="ml-2">{{translate('Edit Cashback Setting')}}</span>
            </h1>
        </div>

        <div class="row g-2">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.customer.cashback.update', [$setting['id']])}}" method="post">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ translate('Cashback Title') }}</label>
                                        <input type="text" name="title" class="form-control" 
                                               value="{{ $setting['title'] }}" 
                                               placeholder="{{ translate('Ex: Wallet Top-up Cashback') }}" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ translate('Description') }}</label>
                                        <input type="text" name="description" class="form-control" 
                                               value="{{ $setting['description'] }}" 
                                               placeholder="{{ translate('Ex: Get 5% cashback') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Branch')}}</label>
                                        <select name="branch_id" class="form-control">
                                            <option value="" {{!$setting['branch_id'] ? 'selected' : ''}}>
                                                {{translate('All Branches (Global)')}}
                                            </option>
                                            @foreach(\App\Model\Branch::active()->get() as $branch)
                                                <option value="{{$branch->id}}" 
                                                        {{$setting['branch_id'] == $branch->id ? 'selected' : ''}}>
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
                                            <option value="wallet_topup" {{$setting['type']=='wallet_topup'?'selected':''}}>
                                                {{translate('Wallet Top-up')}}
                                            </option>
                                            <option value="order" {{$setting['type']=='order'?'selected':''}}>
                                                {{translate('Orders')}}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Cashback Type')}}</label>
                                        <select name="cashback_type" class="form-control" id="cashback_type" required>
                                            <option value="percentage" {{$setting['cashback_type']=='percentage'?'selected':''}}>
                                                {{translate('Percentage')}} (%)
                                            </option>
                                            <option value="fixed" {{$setting['cashback_type']=='fixed'?'selected':''}}>
                                                {{translate('Fixed Amount')}}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Cashback Value')}}
                                            <span id="cashback_unit">
                                                {{$setting['cashback_type']=='percentage' ? '(%)' : '('.Helpers::currency_symbol().')'}}
                                            </span>
                                        </label>
                                        <input type="number" step="0.01" min="0" name="cashback_value" 
                                               value="{{$setting['cashback_value']}}" 
                                               class="form-control" 
                                               placeholder="{{ translate('Ex: 5') }}" 
                                               required>
                                        <small class="text-muted">{{translate('No maximum limit - full value will be given')}}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Minimum Amount')}} ({{Helpers::currency_symbol()}})</label>
                                        <input type="number" step="0.01" min="0" name="min_amount" 
                                               value="{{$setting['min_amount']}}" 
                                               class="form-control" 
                                               placeholder="{{ translate('Ex: 100') }}" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Start Date')}}</label>
                                        <input type="date" name="start_date" class="form-control" 
                                               value="{{date('Y-m-d',strtotime($setting['start_date']))}}" 
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('End Date')}}</label>
                                        <input type="date" name="end_date" class="form-control" 
                                               value="{{date('Y-m-d',strtotime($setting['end_date']))}}" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end">
                                <a href="{{route('admin.customer.cashback.index')}}" class="btn btn-secondary">
                                    {{translate('Cancel')}}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    {{translate('Update')}}
                                </button>
                            </div>
                        </form>
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
</script>
@endpush