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
                @foreach($paymentMethods as $payment)
                    <div class="col-md-6">
                        <div class="card h-100">
                            <form action="{{route('admin.business-settings.web-app.system-payment-method.update', $payment->id)}}" 
                                  method="POST"
                                  enctype="multipart/form-data">
                                @csrf
                                
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <span class="text-uppercase">{{$payment->method_name}}</span>
                                    </h5>
                                    <label class="switcher mb-0">
                                        <input type="checkbox" 
                                               onclick="location.href='{{route('admin.business-settings.web-app.system-payment-method.status',[$payment->id,$payment->is_active?0:1])}}'"
                                               class="switcher_input" 
                                               {{$payment->is_active?'checked':''}}>
                                        <span class="switcher_control"></span>
                                    </label>
                                </div>

                                <div class="card-body">
                                    <div class="payment--gateway-img mb-4 text-center">
                                        <img style="height: 80px; max-width: 100%; object-fit: contain"
                                             src="{{asset('storage/app/public/payment_modules/gateway_image')}}/{{$payment->image}}"
                                             onerror="this.src='{{asset('public/assets/admin/img/placeholder.png')}}'"
                                             alt="{{$payment->method_name}}">
                                    </div>

                                    <div class="d-flex justify-content-center gap-3">
                                        <a href="javascript:" class="btn btn-primary" onclick="editPaymentMethod(
                                            '{{$payment->id}}',
                                            '{{$payment->method_name}}',
                                            '{{$payment->driver_name}}',
                                            '{{$payment->mode}}',
                                            '{{asset('storage/app/public/payment_modules/gateway_image')}}/{{$payment->image}}',
                                            {{json_encode($payment->settings)}},
                                            '{{route('admin.business-settings.web-app.system-payment-method.update', $payment->id)}}',
                                            '{{route('admin.business-settings.web-app.system-payment-method.delete', ['id' => $payment->id])}}'
                                        )">
                                            <i class="tio-edit"></i> {{translate('Edit Settings')}}
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
                
                <div class="col-md-6">
                    <div class="card h-100 border-dashed">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center">
                            <img src="{{asset('public/assets/admin/img/plus-icon.png')}}" alt="" class="mb-3" style="width: 50px; opacity: 0.5;">
                            <a href="javascript:" data-toggle="modal" data-target="#addPaymentMethodModal" class="btn btn-primary">
                                <i class="tio-add"></i> {{translate('Add New Payment Method')}}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Method Modal -->
    <div class="modal fade" id="addPaymentMethodModal" tabindex="-1" role="dialog" aria-labelledby="addPaymentMethodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentMethodModalLabel">{{translate('Add New Payment Method')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('admin.business-settings.web-app.system-payment-method.store')}}" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="method_name">{{translate('Method Name')}}</label>
                                    <input type="text" name="method_name" class="form-control" placeholder="{{translate('Ex: Stripe')}}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="driver_name">{{translate('Driver')}}</label>
                                    <select name="driver_name" class="form-control" required>
                                        <option value="">{{translate('Select Driver')}}</option>
                                        @foreach($supportedDrivers as $key => $driver)
                                            <option value="{{$key}}">{{$driver}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="mode">{{translate('Mode')}}</label>
                                    <select name="mode" class="form-control" required>
                                        <option value="test">{{translate('Test')}}</option>
                                        <option value="live">{{translate('Live')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Gateway Image')}}</label>
                                    <div class="custom-file">
                                        <input type="file" name="gateway_image" class="custom-file-input" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" onchange="previewImage(this, '#add-gateway-image-preview')">
                                        <label class="custom-file-label">{{translate('Choose File')}}</label>
                                    </div>
                                    <div class="mt-2 text-center">
                                         <img id="add-gateway-image-preview" style="height: 50px; object-fit: contain" src="{{asset('public/assets/admin/img/placeholder.png')}}" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>{{translate('Settings / Configuration')}}</h5>
                        <p>{{translate('Add key-value pairs for the driver configuration (e.g., api_key, secret_key, merchant_id).')}}</p>

                        <div id="add-settings-container">
                            <div class="row setting-row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <input type="text" name="keys[]" class="form-control" placeholder="{{translate('Key (e.g. api_key)')}}">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <input type="text" name="values[]" class="form-control" placeholder="{{translate('Value')}}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger remove-row" style="display:none;"><i class="tio-remove"></i></button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-secondary mt-2" onclick="addSettingRow('#add-settings-container')"><i class="tio-add"></i> {{translate('Add Setting')}}</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{translate('Close')}}</button>
                        <button type="submit" class="btn btn-primary">{{translate('Submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Payment Method Modal -->
    <div class="modal fade" id="editPaymentMethodModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentMethodModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPaymentMethodModalLabel">{{translate('Edit Payment Method')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="edit-payment-form" action="" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="edit_method_name">{{translate('Method Name')}}</label>
                                    <input type="text" name="method_name" id="edit_method_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Driver')}}</label>
                                    <input type="text" class="form-control" id="edit_driver_name" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label" for="edit_mode">{{translate('Mode')}}</label>
                                    <select name="mode" id="edit_mode" class="form-control" required>
                                        <option value="test">{{translate('Test')}}</option>
                                        <option value="live">{{translate('Live')}}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">{{translate('Gateway Image')}}</label>
                                    <div class="custom-file">
                                        <input type="file" name="gateway_image" class="custom-file-input" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" onchange="previewImage(this, '#edit-gateway-image-preview')">
                                        <label class="custom-file-label">{{translate('Choose File')}}</label>
                                    </div>
                                    <div class="mt-2 text-center">
                                         <img id="edit-gateway-image-preview" style="height: 50px; object-fit: contain" src="{{asset('public/assets/admin/img/placeholder.png')}}" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>{{translate('Settings / Configuration')}}</h5>
                        <p>{{translate('Configure the keys and values for this payment method.')}}</p>

                        <div id="edit-settings-container">
                            <!-- Populated via JS -->
                        </div>

                        <button type="button" class="btn btn-secondary mt-2" onclick="addSettingRow('#edit-settings-container')"><i class="tio-add"></i> {{translate('Add Setting')}}</button>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <a href="#" id="delete-payment-method-link" class="btn btn-danger" onclick="return confirm('{{translate('Want to delete this method ?')}}')">{{translate('Delete')}}</a>
                        <div>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{translate('Close')}}</button>
                            <button type="submit" class="btn btn-primary">{{translate('Update')}}</button>
                        </div>
                    </div>
                </form>
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

        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $(previewId).attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Add Setting Row
        function addSettingRow(containerId, key = '', value = '') {
            var html = `
                <div class="row setting-row mt-2">
                    <div class="col-md-5">
                        <div class="form-group">
                            <input type="text" name="keys[]" class="form-control" placeholder="{{translate('Key')}}" value="${key}">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <input type="text" name="values[]" class="form-control" placeholder="{{translate('Value')}}" value="${value}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-row"><i class="tio-remove"></i></button>
                    </div>
                </div>
            `;
            $(containerId).append(html);
        }

        $(document).on('click', '.remove-row', function() {
            $(this).closest('.setting-row').remove();
        });

        // Edit Modal Population
        window.editPaymentMethod = function(id, name, driver, mode, imageUrl, settings, updateUrl, deleteUrl) {
            $('#edit_method_name').val(name);
            $('#edit_driver_name').val(driver); // Driver name is informational only in edit
            $('#edit_mode').val(mode);
            $('#edit-gateway-image-preview').attr('src', imageUrl);
            $('#edit-payment-form').attr('action', updateUrl);
            $('#delete-payment-method-link').attr('href', deleteUrl);

            $('#edit-settings-container').empty();
            if (settings) {
                // If settings is a string (JSON), parse it. If it's already an object, use it.
                // In Blade, we pass it as PHP array -> json_encode -> JS object.
                $.each(settings, function(key, value) {
                    addSettingRow('#edit-settings-container', key, value);
                });
            }
            // Add one empty row if no settings, or just always allow adding more
            addSettingRow('#edit-settings-container'); // Add an empty row for new settings

            $('#editPaymentMethodModal').modal('show');
        };


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