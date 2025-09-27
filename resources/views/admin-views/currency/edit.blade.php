@extends('layouts.admin.app')

@section('title', translate('Edit Currency'))

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
                        <li class="breadcrumb-item active">{{translate('Edit Currency')}}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title">{{translate('Edit Currency')}}</h1>
            </div>
        </div>
    </div>

    <form action="{{route('admin.currency.update', $currency->id)}}" method="post">
        @csrf
        @method('put')
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{translate('Currency Information')}}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Currency Name')}}</label>
                                    <input type="text" class="form-control" value="{{$currency->name}}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Currency Code')}}</label>
                                    <input type="text" class="form-control" value="{{$currency->code}}" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Currency Symbol')}}</label>
                                    <input type="text" class="form-control" value="{{$currency->symbol}}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Exchange Rate')}} *</label>
                                    <input type="number" step="0.0001" name="exchange_rate" class="form-control" 
                                           value="{{old('exchange_rate', $currency->exchange_rate)}}" 
                                           {{$currency->is_primary ? 'readonly' : ''}} required>
                                    @if($currency->is_primary)
                                        <small class="text-muted">{{translate('Primary currency rate is fixed at 1.0000')}}</small>
                                    @else
                                        <small class="text-muted">{{translate('Rate against primary currency')}}</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Decimal Places')}}</label>
                                    <select name="decimal_places" class="form-control">
                                        <option value="0" {{old('decimal_places', $currency->decimal_places) == '0' ? 'selected' : ''}}>0</option>
                                        <option value="1" {{old('decimal_places', $currency->decimal_places) == '1' ? 'selected' : ''}}>1</option>
                                        <option value="2" {{old('decimal_places', $currency->decimal_places) == '2' ? 'selected' : ''}}>2</option>
                                        <option value="3" {{old('decimal_places', $currency->decimal_places) == '3' ? 'selected' : ''}}>3</option>
                                        <option value="4" {{old('decimal_places', $currency->decimal_places) == '4' ? 'selected' : ''}}>4</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Symbol Position')}}</label>
                                    <select name="position" class="form-control">
                                        <option value="before" {{old('position', $currency->position) == 'before' ? 'selected' : ''}}>
                                            {{translate('Before Amount')}} ({{translate('Ex: $ 100.00')}})
                                        </option>
                                        <option value="after" {{old('position', $currency->position) == 'after' ? 'selected' : ''}}>
                                            {{translate('After Amount')}} ({{translate('Ex: 100.00 $')}})
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" 
                                               id="is_primary" name="is_primary" value="1"
                                               {{old('is_primary', $currency->is_primary) ? 'checked' : ''}}>
                                        <label class="custom-control-label" for="is_primary">
                                            {{translate('Set as Primary Currency')}}
                                        </label>
                                    </div>
                                    <small class="text-muted">{{translate('This will replace current primary currency')}}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" 
                                               id="is_active" name="is_active" value="1" 
                                               {{old('is_active', $currency->is_active) ? 'checked' : ''}}>
                                        <label class="custom-control-label" for="is_active">
                                            {{translate('Active')}}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row justify-content-end">
                            <div class="col-sm-auto">
                                <a href="{{route('admin.currency.index')}}" class="btn btn-secondary mr-2">
                                    {{translate('Cancel')}}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    {{translate('Update Currency')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('script')
<script>
$(document).ready(function() {
    $('#is_primary').on('change', function() {
        if ($(this).is(':checked')) {
            $('input[name="exchange_rate"]').val('1.0000').prop('readonly', true);
            $('#is_active').prop('checked', true);
            alert('{{translate("Primary currency exchange rate will be set to 1.0000")}}');
        } else {
            $('input[name="exchange_rate"]').prop('readonly', false);
        }
    });
});
</script>
@endpush
@endsection