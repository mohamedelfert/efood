@extends('layouts.admin.app')

@section('title', translate('System Payment Methods'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title"><i class="tio-credit-card"></i> {{translate('System Payment Methods')}}
                    </h1>
                </div>
                <div class="col-sm-auto">
                    <a class="btn btn-primary"
                        href="{{route('admin.business-settings.web-app.system-payment-method.create')}}">
                        <i class="tio-add"></i> {{translate('Add New Method')}}
                    </a>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header">
                        <h5>{{translate('Payment Methods List')}} <span class="badge badge-soft-dark ml-2"
                                id="itemCount">{{$paymentMethods->total()}}</span></h5>
                        <form action="{{url()->current()}}" method="GET">
                            <div class="input-group input-group-merge input-group-flush">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="tio-search"></i>
                                    </div>
                                </div>
                                <input id="datatableSearch_" type="search" name="search" class="form-control"
                                    placeholder="{{translate('Search')}}" aria-label="Search" value="{{$search}}" required>
                                <button type="submit" class="btn btn-primary">{{translate('search')}}</button>
                            </div>
                        </form>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{translate('#')}}</th>
                                    <th style="width: 30%">{{translate('Method Name')}}</th>
                                    <th>{{translate('Driver')}}</th>
                                    <th>{{translate('Mode')}}</th>
                                    <th>{{translate('Status')}}</th>
                                    <th>{{translate('Action')}}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($paymentMethods as $key => $method)
                                    <tr>
                                        <td>{{$paymentMethods->firstItem() + $key}}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($method->image)
                                                    <img class="avatar avatar-sm mr-3"
                                                        src="{{asset('storage/app/public/payment_modules/gateway_image')}}/{{$method->image}}"
                                                        onerror="this.src='{{asset('public/assets/admin/img/160x160/img1.jpg')}}'"
                                                        alt="Image">
                                                @endif
                                                <span class="d-block font-size-sm text-body">
                                                    {{$method->method_name}}
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{Str::ucfirst($method->driver_name)}}</td>
                                        <td>
                                            <span class="badge badge-soft-{{$method->mode == 'live' ? 'success' : 'warning'}}">
                                                {{translate($method->mode)}}
                                            </span>
                                        </td>
                                        <td>
                                            <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$method->id}}">
                                                <input type="checkbox"
                                                    onclick="location.href='{{route('admin.business-settings.web-app.system-payment-method.status', [$method->id, $method->is_active ? 0 : 1])}}'"
                                                    class="toggle-switch-input" id="stocksCheckbox{{$method->id}}"
                                                    {{$method->is_active ? 'checked' : ''}}>
                                                <span class="toggle-switch-label">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-white"
                                                href="{{route('admin.business-settings.web-app.system-payment-method.edit', [$method->id])}}"
                                                title="{{translate('edit')}}"><i class="tio-edit"></i></a>
                                            <a class="btn btn-sm btn-white" href="javascript:"
                                                onclick="form_alert('method-{{$method->id}}','{{translate('Want to delete this method ?')}}')"
                                                title="{{translate('delete')}}"><i class="tio-delete-outlined"></i></a>
                                            <form
                                                action="{{route('admin.business-settings.web-app.system-payment-method.delete', [$method->id])}}"
                                                method="post" id="method-{{$method->id}}">
                                                @csrf @method('delete')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <hr>
                        <table>
                            <tfoot>
                                {!! $paymentMethods->links() !!}
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection