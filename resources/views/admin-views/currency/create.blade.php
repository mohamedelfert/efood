@extends('layouts.admin.app')

@section('title', translate('add_currency'))

@push('css_or_js')
<style>
    .currency-preview {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1rem;
        margin-top: 1rem;
    }
    .currency-info {
        display: none;
    }
</style>
@endpush

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
                        <li class="breadcrumb-item active">{{translate('add_currency')}}</li>
                    </ol>
                </nav>
                <h1 class="page-header-title">{{translate('add_new_currency')}}</h1>
            </div>
        </div>
    </div>

    <form action="{{route('admin.currency.store')}}" method="post">
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{translate('currency_information')}}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('select_currency')}} *</label>
                                    <select name="currency_code" id="currency_code" class="form-control" required>
                                        <option value="">{{translate('choose_a_currency')}}</option>
                                        @foreach($availableCurrencies as $currency)
                                            <option value="{{$currency['code']}}" 
                                                    data-name="{{$currency['name']}}"
                                                    data-symbol="{{$currency['symbol']}}"
                                                    {{old('currency_code') == $currency['code'] ? 'selected' : ''}}>
                                                {{$currency['code']}} - {{$currency['name']}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('exchange_rate')}} *</label>
                                    <input type="number" step="0.0001" name="exchange_rate" class="form-control" 
                                           placeholder="{{translate('Ex: 1.0000')}}"
                                           value="{{old('exchange_rate', '1.0000')}}" required>
                                    <small class="text-muted">{{translate('rate_against_primary_currency')}}</small>
                                </div>
                            </div>
                        </div>

                        <div class="currency-info" id="currency-info">
                            <div class="currency-preview">
                                <h6>{{translate('currency_preview')}}</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>{{translate('name')}}:</strong>
                                        <span id="preview-name"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{translate('code')}}:</strong>
                                        <span id="preview-code"></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>{{translate('symbol')}}:</strong>
                                        <span id="preview-symbol"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Decimal Places')}}</label>
                                    <select name="decimal_places" class="form-control">
                                        <option value="0" {{old('decimal_places') == '0' ? 'selected' : ''}}>0</option>
                                        <option value="1" {{old('decimal_places') == '1' ? 'selected' : ''}}>1</option>
                                        <option value="2" {{old('decimal_places', '2') == '2' ? 'selected' : ''}}>2</option>
                                        <option value="3" {{old('decimal_places') == '3' ? 'selected' : ''}}>3</option>
                                        <option value="4" {{old('decimal_places') == '4' ? 'selected' : ''}}>4</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Symbol Position')}}</label>
                                    <select name="position" class="form-control">
                                        <option value="before" {{old('position', 'before') == 'before' ? 'selected' : ''}}>
                                            {{translate('Before Amount')}} ({{translate('Ex: $ 100.00')}})
                                        </option>
                                        <option value="after" {{old('position') == 'after' ? 'selected' : ''}}>
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
                                               {{old('is_primary') ? 'checked' : ''}}>
                                        <label class="custom-control-label" for="is_primary">
                                            {{translate('set_as_primary_currency')}}
                                        </label>
                                    </div>
                                    <small class="text-muted">{{translate('this_will_replace_current_primary_currency')}}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" 
                                               id="is_active" name="is_active" value="1" 
                                               {{old('is_active', '1') ? 'checked' : ''}}>
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
                                    {{translate('Add Currency')}}
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
    $('#currency_code').on('change', function() {
        const selectedOption = $(this).find('option:selected');
        const code = selectedOption.val();
        const name = selectedOption.data('name');
        const symbol = selectedOption.data('symbol');
        
        if (code) {
            $('#currency-info').show();
            $('#preview-name').text(name);
            $('#preview-code').text(code);
            $('#preview-symbol').text(symbol);
        } else {
            $('#currency-info').hide();
        }
    });
    
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