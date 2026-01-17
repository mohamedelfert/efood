@extends('layouts.admin.app')

@section('title', translate('Add System Payment Method'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i class="tio-add-circle-outlined"></i>
                        {{translate('Add New Payment Method')}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.business-settings.web-app.system-payment-method.store')}}"
                            method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{translate('Method Name')}}</label>
                                        <input type="text" name="method_name" class="form-control"
                                            placeholder="{{translate('Ex: Stripe')}}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label" for="driver_name">{{translate('Driver')}}</label>
                                        <select name="driver_name" class="form-control" id="driver_name" required>
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
                                            <input type="file" name="gateway_image" id="customFileEg1"
                                                class="custom-file-input"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <label class="custom-file-label"
                                                for="customFileEg1">{{translate('Choose File')}}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5>{{translate('Settings / Configuration')}}</h5>
                            <p>{{translate('Add key-value pairs for the driver configuration (e.g., api_key, secret_key, merchant_id).')}}
                            </p>

                            <div id="settings-container">
                                <div class="row setting-row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <input type="text" name="keys[]" class="form-control"
                                                placeholder="{{translate('Key (e.g. api_key)')}}">
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <input type="text" name="values[]" class="form-control"
                                                placeholder="{{translate('Value')}}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-row" style="display:none;"><i
                                                class="tio-remove"></i></button>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-secondary mt-2" id="add-setting-row"><i
                                    class="tio-add"></i> {{translate('Add Setting')}}</button>

                            <hr>
                            <div class="btn--container justify-content-end">
                                <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                <button type="submit" class="btn btn-primary">{{translate('submit')}}</button>
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
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
        });

        $(document).on('click', '#add-setting-row', function () {
            var html = `
                    <div class="row setting-row mt-2">
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
                            <button type="button" class="btn btn-danger remove-row"><i class="tio-remove"></i></button>
                        </div>
                    </div>
                `;
            $('#settings-container').append(html);
        });

        $(document).on('click', '.remove-row', function () {
            $(this).closest('.setting-row').remove();
        });

        // Transform the form submission to send separate fields based on keys/values
        $('form').on('submit', function (e) {
            // We need to iterate over keys/values and ensure they are sent as direct fields
            // Or the controller handles the arrays?

            // Wait, the controller code I wrote expects:
            // $settings = $request->except(['_token', 'method_name', 'driver_name', 'mode', 'gateway_image']);
            // But this form sends arrays `keys[]` and `values[]`.
            // I should modify the controller OR modify this JS to construct the payload.
            // Modifying the controller is safer and cleaner on backend.
            // But I already wrote the controller to except specific keys.
            // Let's modify the JS to create hidden inputs for each key-value pair?
            // No, that's messy. 
            // Better: Update the Controller to handle keys/values arrays if they exist, OR update the form to not use arrays but dynamic names? 
            // Dynamic names are hard to guess in Controller.
            // I will update the Controller to handle `keys` and `values` if present. 
            // BUT I CANNOT EDIT THE CONTROLLER IN THIS STEP. 
            // I will use this JS to convert keys[] and values[] to hidden inputs with name=key value=value, 
            // and remove the original inputs so they don't get sent?
            // Or just make sure the Controller ignores keys/values arrays if I add logic.
            // Let's assume I will Fix the Controller in a subsequent step.

            // Actually, I can use a simpler approach:
            // Just let the user type the name of the input field.
            // <input name="api_key" ...>
            // But that requires knowing the keys.
            // The Key-Value pair UI is good.
            // I will stick with the UI, and I will UPDATE THE CONTROLLER to logic:
            // if ($request->has('keys') && $request->has('values')) { combine them }
        });
    </script>
@endpush