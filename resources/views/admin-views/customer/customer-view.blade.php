@extends('layouts.admin.app')

@section('title', translate('Customer Details'))

@section('content')
    <div class="content container-fluid">
        <div class="d-print-none pb-2">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3 border-bottom pb-3">
                <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                    <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/customer.png')}}" alt="">
                    <span class="page-header-title">
                        {{translate('customer_Details')}}
                    </span>
                </h2>
            </div>

            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3">
                <div class="d-flex flex-column gap-2">
                    <h2 class="page-header-title h1">{{translate('customer_ID')}} #{{$customer['id']}}</h2>
                    <span class="">
                        <i class="tio-date-range"></i>
                        {{translate('joined_at')}} : {{date('d M Y H:i:s',strtotime($customer['created_at']))}}
                    </span>
                </div>

                <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                    <a class="btn btn-primary" href="{{ route('admin.customer.customer_transaction',[$customer['id']]) }}">
                        {{translate('point_History')}}
                    </a>
                    <a href="{{route('admin.dashboard')}}" class="btn btn-primary">
                        <i class="tio-home-outlined"></i>
                        {{translate('dashboard')}}
                    </a>
                </div>
            </div>
        </div>

        <div class="row mb-2 g-2">


            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="resturant-card bg--2">
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/dashboard/1.png')}}" alt="{{translate('dashboard')}}">
                    <div class="for-card-text font-weight-bold  text-uppercase mb-1">{{translate('wallet')}} {{translate('balance')}}</div>
                    <div class="for-card-count">{{Helpers::set_symbol($customer->wallet_balance??0)}}</div>
                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="resturant-card bg--3">
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/dashboard/3.png')}}" alt="{{translate('dashboard')}}">
                    <div class="for-card-text font-weight-bold  text-uppercase mb-1">{{translate('loyalty_point')}} {{translate('balance')}}</div>
                    <div class="for-card-count">{{$customer->point??0}}</div>
                </div>
            </div>
        </div>

        <div class="row flex-wrap-reverse g-2" id="printableArea">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-top px-card pt-4">
                        <div class="row align-items-center">
                            <div class="col-sm-4 col-md-6 col-xl-7">
                                <h5 class="d-flex gap-2 align-items-center">
                                    {{translate('Order List')}}
                                    <span class="badge badge-soft-dark rounded-50 fz-12">{{ $orders->total() }}</span>
                                </h5>
                            </div>
                            <div class="col-sm-8 col-md-6 col-xl-5">
                                <form action="{{url()->current()}}" method="GET">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="{{translate('Search by order ID')}}" aria-label="Search" value="{{$search}}" required="" autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">{{translate('Search')}}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="py-3">
                        <div class="table-responsive datatable-custom">
                            <table id="columnSearchDatatable"
                                class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100"
                                data-hs-datatables-options='{
                                    "order": [],
                                    "orderCellsTop": true
                                }'>
                                <thead class="thead-light">
                                    <tr>
                                        <th>{{translate('SL')}}</th>
                                        <th class="text-center">{{translate('order_ID')}}</th>
                                        <th class="text-center">{{translate('total_Amount')}}</th>
                                        <th class="text-center">{{translate('action')}}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                @foreach($orders as $key=>$order)
                                    <tr>
                                        <td>{{$orders->firstItem() + $key}}</td>
                                        <td class="table-column-pl-0 text-center">
                                            <a class="text-dark" href="{{route('admin.orders.details',['id'=>$order['id']])}}">{{$order['id']}}</a>
                                        </td>
                                        <td class="text-center">{{ Helpers::set_symbol($order['order_amount'] + $order['delivery_charge']) }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                    <a class="btn btn-outline-success btn-sm square-btn"
                                                    href="{{route('admin.orders.details',['id'=>$order['id']])}}"><i
                                                            class="tio-visible"></i></a>
                                                    <a class="btn btn-outline-info btn-sm square-btn" target="_blank"
                                                    href="{{route('admin.orders.generate-invoice',[$order['id']])}}"><i
                                                            class="tio-download"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="table-responsive px-3">
                        <div class="d-flex justify-content-lg-end">
                            {!! $orders->links() !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-header-title d-flex gap-2"><span class="tio-user"></span> {{$customer['name']}}</h4>
                    </div>

                    @if($customer)
                        <div class="card-body">
                            <div class="media gap-3">
                                <div class="avatar avatar-xl avatar-circle">
                                    <img
                                        class="img-fit rounded-circle"
                                        src="{{$customer->imageFullPath}}"
                                        alt="{{translate('Image Description')}}">
                                </div>
                                <div class="media-body d-flex flex-column gap-1">
                                    <div class="text-dark d-flex gap-2 align-items-center"><span class="tio-email"></span> <a class="text-dark" href="mailto:{{$customer['email']}}">{{$customer['email']}}</a></div>
                                    <div class="text-dark d-flex gap-2 align-items-center"><span class="tio-call-talking-quiet"></span> <a class="text-dark" href="tel:{{$customer['phone']}}">{{$customer['phone']}}</a></div>
                                    <div class="text-dark d-flex gap-2 align-items-center"><span class="tio-shopping-basket-outlined"></span> {{$customer->orders->count()}} {{translate('orders')}}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-header-title d-flex gap-2"><span class="tio-home"></span> {{translate('addresses')}}</h4>
                    </div>

                    @if($customer)
                        <div class="card-body">
                            @foreach($customer->addresses as $address)
                                <ul class="list-unstyled list-unstyled-py-2">
                                    <li>
                                        <i class="tio-city mr-2"></i>
                                        {{$address['address_type']}}
                                    </li>
                                    <li>
                                        <i class="tio-call-talking-quiet mr-2"></i>
                                        {{$address['contact_person_number']}}
                                    </li>
                                    <li class="li-pointer">
                                        <a class="text-muted" target="_blank"
                                           href="http://maps.google.com/maps?z=12&t=m&q=loc:{{$address['latitude']}}+{{$address['longitude']}}">
                                            <i class="tio-map mr-2"></i>
                                            {{$address['address']}}
                                        </a>
                                    </li>
                                </ul>
                                <hr>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-header-title d-flex gap-2 align-items-center">
                        <span class="tio-qr-code"></span> 
                        {{translate('Wallet QR Code')}}
                    </h4>
                </div>

                @if($customer)
                    <div class="card-body text-center">
                        @if($customer->qr_code_image)
                            <!-- Display QR Code Image -->
                            <div class="mb-3">
                                <img src="{{ $customer->qr_code_image_url }}" 
                                    alt="Customer QR Code" 
                                    class="border rounded p-2 bg-white"
                                    style="max-width: 250px; width: 100%;"
                                    onerror="this.onerror=null; this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}';">
                            </div>
                            
                            <!-- QR Code Info -->
                            <div class="text-left mb-3">
                                <div class="mb-2">
                                    <small class="text-muted">{{translate('QR Code ID')}}:</small>
                                    <div class="font-weight-bold" style="word-break: break-all; font-size: 12px;">
                                        {{ $customer->qr_code }}
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                <!-- Download QR Code -->
                                <a href="{{ $customer->qr_code_image_url }}" 
                                download="customer_{{ $customer->id }}_qr.png"
                                class="btn btn-sm btn-primary">
                                    <i class="tio-download"></i> {{translate('Download')}}
                                </a>

                                <!-- Print QR Code -->
                                <button type="button" 
                                        class="btn btn-sm btn-info"
                                        onclick="printQRCode()">
                                    <i class="tio-print"></i> {{translate('Print')}}
                                </button>

                                <!-- Regenerate QR Code -->
                                <button type="button" 
                                        class="btn btn-sm btn-warning"
                                        onclick="regenerateQRCode({{ $customer->id }})">
                                    <i class="tio-refresh"></i> {{translate('Regenerate')}}
                                </button>

                                <!-- View Full Size -->
                                <button type="button" 
                                        class="btn btn-sm btn-success"
                                        data-toggle="modal" 
                                        data-target="#qrCodeModal">
                                    <i class="tio-visible"></i> {{translate('View Full')}}
                                </button>
                            </div>

                            <!-- Instructions -->
                            <div class="alert alert-soft-info mt-3 text-left" role="alert">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="tio-info-outined"></i>
                                    </div>
                                    <div class="flex-grow-1 ml-2">
                                        <small>
                                            {{translate('This QR code can be used to receive money transfers from other users.')}}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- No QR Code -->
                            <div class="text-center py-4">
                                <i class="tio-qr-code" style="font-size: 48px; color: #ddd;"></i>
                                <p class="text-muted mt-2">{{translate('No QR code available')}}</p>
                                <button type="button" 
                                        class="btn btn-sm btn-primary"
                                        onclick="generateQRCode({{ $customer->id }})">
                                    <i class="tio-add"></i> {{translate('Generate QR Code')}}
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- QR Code Modal for Full View -->
            <div class="modal fade" id="qrCodeModal" tabindex="-1" role="dialog" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="qrCodeModalLabel">
                                {{translate('Customer Wallet QR Code')}}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            @if($customer->qr_code_image)
                                <img src="{{ $customer->qr_code_image_url }}" 
                                    alt="Customer QR Code" 
                                    class="img-fluid border rounded p-3 bg-white"
                                    style="max-width: 400px;">
                                
                                <div class="mt-3">
                                    <h6>{{ $customer->name }}</h6>
                                    <p class="text-muted mb-0">{{ $customer->phone }}</p>
                                    <small class="text-muted" style="word-break: break-all;">
                                        {{ $customer->qr_code }}
                                    </small>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <a href="{{ $customer->qr_code_image_url ?? '#' }}" 
                            download="customer_{{ $customer->id }}_qr.png"
                            class="btn btn-primary">
                                <i class="tio-download"></i> {{translate('Download')}}
                            </a>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                {{translate('Close')}}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden print area -->
            <div id="qrCodePrintArea" style="display: none;">
                <div style="text-align: center; padding: 20px;">
                    <h2>{{ $customer->name }}</h2>
                    <p>{{ $customer->phone }}</p>
                    <img src="{{ $customer->qr_code_image_url ?? '' }}" 
                        alt="QR Code" 
                        style="max-width: 400px; margin: 20px auto;">
                    <p style="font-size: 12px; word-break: break-all;">{{ $customer->qr_code ?? '' }}</p>
                    <p style="margin-top: 20px; color: #666;">
                        {{translate('Scan this QR code to send money to this customer')}}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade point-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">

                    <h5 class="modal-title h4" id="mySmallModalLabel"> {{translate('add')}} {{translate('point')}} </h5>
                    <button type="button" class="btn btn-xs btn-icon btn-ghost-secondary" data-dismiss="modal"
                            aria-label="Close">
                        <i class="tio-clear tio-lg"></i>
                    </button>
                </div>

                <form action="{{route('admin.customer.AddPoint',[$customer['id']])}}" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <input type="number" name="point" class="form-control" min="1" max="100000"
                                   placeholder="{{translate('EX')}} : 100" required>
                        </div>
                        <button class="btn btn-primary">{{translate('submit')}}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

@push('script_2')
        <script src="{{asset('public/assets/admin/js/customer-view.js')}}"></script>
@endpush
