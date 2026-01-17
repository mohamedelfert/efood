@extends('layouts.admin.app')

@section('title', translate('Edit System Payment Method'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i class="tio-edit"></i> {{translate('Edit Payment Method')}}: {{$method->method_name}}</h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.business-settings.web-app.system-payment-method.update', $method->id)}}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('Method Name')}}</label>
                                        <input type="text" name="method_name" class="form-control" value="{{$method->method_name}}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Driver')}}</label>
                                        <input type="text" class="form-control" value="{{Str::ucfirst($method->driver_name)}}" disabled>
                                        <input type="hidden" name="driver_name" value="{{$method->driver_name}}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label" for="mode">{{translate('Mode')}}</label>
                                        <select name="mode" class="form-control" required>
                                            <option value="test" {{$method->mode == 'test' ? 'selected' : ''}}>{{translate('Test')}}</option>
                                            <option value="live" {{$method->mode == 'live' ? 'selected' : ''}}>{{translate('Live')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Gateway Image')}}</label>
                                        <div class="custom-file">
                                            <input type="file" name="gateway_image" id="customFileEg1" class="custom-file-input" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <label class="custom-file-label" for="customFileEg1">{{translate('Choose File')}}</label>
                                        </div>
                                        @if($method->image)
                                            <div class="mt-2">
                                                <img width="100" src="{{asset('storage/app/public/payment_modules/gateway_image')}}/{{$method->image}}" alt="Image">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            <h5>{{translate('Settings / Configuration')}}</h5>
                            <p>{{translate('Configure the keys and values for this payment method.')}}</p>

                            <div id="settings-container">
                                @if($method->settings && is_array($method->settings))
                                    @foreach($method->settings as $key => $value)
                                        <div class="row setting-row mt-2">
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <input type="text" name="keys[]" class="form-control" value="{{$key}}" placeholder="{{translate('Key')}}">
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <div class="form-group">
                                                    <input type="text" name="values[]" class="form-control" value="{{$value}}" placeholder="{{translate('Value')}}">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger remove-row"><i class="tio-remove"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                                
                                <div class="row setting-row mt-2">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <input type="text" name="keys[]" class="form-control" placeholder="{{translate('New Key')}}">
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <input type="text" name="values[]" class="form-control" placeholder="{{translate('New Value')}}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-row" style="display:none;"><i class="tio-remove"></i></button>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" class="btn btn-secondary mt-2" id="add-setting-row"><i class="tio-add"></i> {{translate('Add Setting')}}</button>
                            
                            <hr>
                            <div class="btn--container justify-content-end">
                                <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                <button type="submit" class="btn btn-primary">{{translate('update')}}</button>
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

        $(document).on('click', '#add-setting-row', function() {
            var html = `
                <div class="row setting-row mt-2">
                    <div class="col-md-5">
                        <div class="form-group">
                            <input type="text" name="keys[]" class="form-control" placeholder="{{translate('Key')}}">
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

        $(document).on('click', '.remove-row', function() {
            $(this).closest('.setting-row').remove();
        });
    </script>
@endpush
