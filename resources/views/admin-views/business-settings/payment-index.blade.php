@extends('layouts.admin.app')

@section('title', translate('Payment Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/third-party.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('third_party')}}
                </span>
            </h2>
        </div>

        @include('admin-views.business-settings.partials._3rdparty-inline-menu')

        @php($partial_payment=\App\CentralLogics\Helpers::get_business_settings('partial_payment'))
        @php($combine_with=\App\CentralLogics\Helpers::get_business_settings('partial_payment_combine_with'))

        <div class="g-2">
            <form action="{{route('admin.business-settings.web-app.payment-method-status')}}" method="post">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{translate('Payment Method Settings')}}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                @php($cod=\App\CentralLogics\Helpers::get_business_settings('cash_on_delivery'))
                                <div class="form-control h-100 d-flex justify-content-between align-items-center">
                                    <div class="d-flex flex-column">
                                        <label class="text-dark mb-1 font-weight-bold">{{translate('Cash On Delivery')}}</label>
                                        <small class="text-muted">{{translate('Enable cash payment on delivery')}}</small>
                                    </div>
                                    <label class="switcher">
                                        <input class="switcher_input check-offline-combination" 
                                               data-method="COD" 
                                               type="checkbox" 
                                               name="cash_on_delivery" 
                                               {{$cod == null || $cod['status'] == 0? '' : 'checked'}} 
                                               id="cash_on_delivery_btn">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                @php($dp=\App\CentralLogics\Helpers::get_business_settings('digital_payment'))
                                <div class="form-control h-100 d-flex justify-content-between align-items-center">
                                    <div class="d-flex flex-column">
                                        <label class="text-dark mb-1 font-weight-bold">{{translate('Digital Payment')}}</label>
                                        <small class="text-muted">{{translate('Enable online payment gateways')}}</small>
                                    </div>
                                    <label class="switcher">
                                        <input class="switcher_input check-offline-combination" 
                                               data-method="digital_payment" 
                                               type="checkbox" 
                                               name="digital_payment" 
                                               {{$dp == null || $dp['status'] == 0? '' : 'checked'}}
                                               id="digital_payment_btn">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                @php($op=\App\CentralLogics\Helpers::get_business_settings('offline_payment'))
                                <div class="form-control h-100 d-flex justify-content-between align-items-center">
                                    <div class="d-flex flex-column">
                                        <label class="text-dark mb-1 font-weight-bold">{{translate('Offline Payment')}}</label>
                                        <small class="text-muted">{{translate('Enable manual payment verification')}}</small>
                                    </div>
                                    <label class="switcher">
                                        <input class="switcher_input check-offline-combination" 
                                               data-method="offline_payment" 
                                               type="checkbox" 
                                               name="offline_payment" 
                                               {{$op == null || $op['status'] == 0? '' : 'checked'}} 
                                               id="offline_payment_btn">
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="reset" class="btn btn-secondary mr-2">{{translate('Reset')}}</button>
                            <button type="submit" class="btn btn-primary">{{translate('Save Changes')}}</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="digital_payment_section mt-4">
            @if($published_status == 1)
                <div class="row g-2">
                    <div class="col-12 mb-3">
                        <div class="card">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="tio-info-outined text-danger" style="font-size: 2rem;"></i>
                                    <div>
                                        <h5 class="text-danger mb-1">{{translate('Payment Gateway Addon Enabled')}}</h5>
                                        <p class="mb-0">
                                            {{ translate('Your current payment settings are disabled because you have enabled the payment gateway addon. To manage your active payment gateway settings, please visit the addon settings.') }}
                                        </p>
                                    </div>
                                </div>
                                <a href="{{!empty($payment_url) ? $payment_url : '#'}}" 
                                   class="btn btn-outline-primary text-nowrap">
                                    <i class="tio-settings mr-1"></i>{{translate('Addon Settings')}}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row digital_payment_methods g-3" id="payment-gateway-cards">
                @foreach($data_values as $payment)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <form action="{{env('APP_MODE')!='demo'?route('admin.business-settings.web-app.payment-config-update'):'javascript:'}}" 
                                  method="POST"
                                  id="{{$payment->key_name}}-form" 
                                  enctype="multipart/form-data">
                                @csrf
                                
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <span class="text-uppercase">{{str_replace('_',' ',$payment->key_name)}}</span>
                                    </h5>
                                    <label class="switcher mb-0">
                                        <input type="checkbox" 
                                               name="status" 
                                               value="1"
                                               class="switcher_input" 
                                               {{$payment->is_active==1?'checked':''}}>
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>

                                @php($additional_data = $payment->additional_data != null ? json_decode($payment->additional_data) : null)
                                @php($liveValues = is_string($payment->live_values) ? json_decode($payment->live_values, true) : (array)$payment->live_values)
                                @php($testValues = is_string($payment->test_values) ? json_decode($payment->test_values, true) : (array)$payment->test_values)
                                @php($mode = $payment->mode ?? 'live')
                                @php($currentValues = $mode == 'live' ? $liveValues : $testValues)
                                
                                <div class="card-body">
                                    <div class="payment--gateway-img mb-4 text-center">
                                        <img style="height: 80px; max-width: 100%; object-fit: contain"
                                             src="{{asset('storage/app/public/payment_modules/gateway_image')}}/{{$additional_data != null ? $additional_data->gateway_image : ''}}"
                                             onerror="this.src='{{asset('public/assets/admin/img/placeholder.png')}}'"
                                             alt="{{$payment->key_name}}">
                                    </div>

                                    <input type="hidden" name="gateway" value="{{$payment->key_name}}">

                                    <div class="form-group">
                                        <label class="form-label">{{translate('Mode')}}</label>
                                        <select class="form-control" name="mode">
                                            <option value="live" {{$mode=='live'?'selected':''}}>{{ translate('Live') }}</option>
                                            <option value="test" {{$mode=='test'?'selected':''}}>{{ translate('Test') }}</option>
                                        </select>
                                    </div>

                                    @if ($payment->key_name == 'stripe')
                                        <div class="form-group">
                                            <label class="form-label">{{translate('Supported Country')}} <span class="text-danger">*</span></label>
                                            <select name="supported_country" class="form-control" required>
                                                <option value="">{{translate('Select Country')}}</option>
                                                <option value="egypt" {{isset($currentValues['supported_country']) && $currentValues['supported_country'] == 'egypt' ? 'selected' : ''}}>Egypt</option>
                                                <option value="KSA" {{isset($currentValues['supported_country']) && $currentValues['supported_country'] == 'KSA' ? 'selected' : ''}}>Saudi Arabia</option>
                                                <option value="UAE" {{isset($currentValues['supported_country']) && $currentValues['supported_country'] == 'UAE' ? 'selected' : ''}}>UAE</option>
                                                <option value="oman" {{isset($currentValues['supported_country']) && $currentValues['supported_country'] == 'oman' ? 'selected' : ''}}>Oman</option>
                                                <option value="PAK" {{isset($currentValues['supported_country']) && $currentValues['supported_country'] == 'PAK' ? 'selected' : ''}}>Pakistan</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">{{translate('API Key')}} <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control"
                                                   name="api_key"
                                                   placeholder="{{translate('Enter API Key')}}"
                                                   value="{{env('APP_MODE')=='demo'?'':($currentValues['api_key'] ?? '')}}"
                                                   >
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">{{translate('Published Key')}} <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control"
                                                   name="published_key"
                                                   placeholder="{{translate('Enter Published Key')}}"
                                                   value="{{env('APP_MODE')=='demo'?'':($currentValues['published_key'] ?? '')}}"
                                                   >
                                        </div>
                                    @endif

                                    @if ($payment->key_name == 'qib')
                                        <div class="form-group">
                                            <label class="form-label">{{translate('Merchant ID')}} <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control"
                                                   name="merchant_id"
                                                   placeholder="{{translate('Enter Merchant ID')}}"
                                                   value="{{env('APP_MODE')=='demo'?'':($currentValues['merchant_id'] ?? '')}}"
                                                   required>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">{{translate('Secure Hash')}} <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   class="form-control"
                                                   name="secure_hash"
                                                   placeholder="{{translate('Enter Secure Hash')}}"
                                                   value="{{env('APP_MODE')=='demo'?'':($currentValues['secure_hash'] ?? '')}}"
                                                   required>
                                        </div>
                                    @endif

                                    <div class="form-group">
                                        <label class="form-label">{{translate('Payment Gateway Title')}}</label>
                                        <input type="text" 
                                               class="form-control"
                                               name="gateway_title"
                                               placeholder="{{translate('e.g., Pay with Stripe')}}"
                                               value="{{$additional_data != null ? $additional_data->gateway_title : ''}}">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">{{translate('Gateway Logo')}}</label>
                                        <div class="custom-file">
                                            <input type="file" 
                                                   class="custom-file-input" 
                                                   name="gateway_image" 
                                                   accept=".jpg, .png, .jpeg|image/*"
                                                   id="gateway_image_{{$payment->key_name}}">
                                            <label class="custom-file-label" for="gateway_image_{{$payment->key_name}}">
                                                {{translate('Choose file')}}
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">
                                            {{translate('Recommended size: 120x50 pixels')}}
                                        </small>
                                    </div>

                                    <div class="d-flex justify-content-end gap-3 mt-4">
                                        <button type="reset" class="btn btn-secondary">{{translate('Reset')}}</button>
                                        <button type="{{env('APP_MODE')!='demo'?'submit':'button'}}"
                                                class="btn btn-primary {{env('APP_MODE')=='demo'?'call-demo':''}}">
                                            {{translate('Save Configuration')}}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Offline Payment Warning Modal -->
    <div class="modal fade" id="offlinePaymentWarningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center py-5">
                    <div class="mb-4">
                        <img src="{{ asset('public/assets/admin/svg/components/info.svg') }}" 
                             alt="Warning" 
                             style="width: 80px;">
                    </div>
                    <h4 class="mb-3">{{ translate('Offline Payment Warning') }}</h4>
                    <p class="text-muted px-4"> 
                        {{ translate('Since offline payment is combined with this payment method, you should change that configuration before disabling it.') }}
                    </p>
                    <p class="mb-4">
                        <a href="{{ route('admin.business-settings.restaurant.restaurant-setup') }}" 
                           target="_blank" 
                           class="text-primary font-weight-bold">
                            {{ translate('Go to Business Setup') }} <i class="tio-open-in-new"></i>
                        </a>
                    </p>
                    <button type="button" class="btn btn-primary px-5" data-dismiss="modal">
                        {{translate('Got it')}}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        'use strict';

        // Check offline payment combination
        $(document).on('change', '.check-offline-combination', function () {
            let method = $(this).data('method');
            let status = $(this).prop('checked');
            let partialPaymentStatus = "{{ $partial_payment }}";
            let partialCombineWith = "{{ $combine_with }}";
            let $checkbox = $(this);

            if (partialPaymentStatus == '1') {
                if ((partialCombineWith === method || partialCombineWith === 'all') && status === false) {
                    $('#offlinePaymentWarningModal').modal('show');
                    setTimeout(() => {
                        $checkbox.prop('checked', true);
                    }, 300);
                }
            }
        });

        // Preview gateway image
        $(document).on('change', 'input[name="gateway_image"]', function () {
            var $input = $(this);
            var $form = $input.closest('form');

            if (this.files && this.files[0]) {
                var reader = new FileReader();
                var $imagePreview = $form.find('.payment--gateway-img img');

                reader.onload = function (e) {
                    $imagePreview.attr('src', e.target.result);
                }

                reader.readAsDataURL(this.files[0]);
            }
        });

        // Update file input label with selected filename
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        // Disable all inputs if addon is published
        @if($published_status == 1)
            $('#payment-gateway-cards').find('input, select, button').prop('disabled', true);
            $('#payment-gateway-cards').find('.switcher_input').prop('checked', false);
        @endif
    </script>
@endpush