@extends('layouts.admin.app')

@section('title', translate('Branch Report'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/sales.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Branch_Reports')}}
                </span>
            </h2>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="media flex-column flex-sm-row flex-wrap align-items-sm-center gap-4">
                    <div class="avatar avatar-xl">
                        <img class="avatar-img" src="{{asset('public/assets/admin')}}/svg/illustrations/credit-card.svg"
                            alt="{{ translate('branch_report') }}">
                    </div>

                    <div class="media-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="">
                                <h2 class="page-header-title">{{translate('Branch_Report_Overview')}}</h2>

                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span>{{translate('admin')}}:</span>
                                        <a href="#">{{auth('admin')->user()->name}}</a>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex">
                                <a class="btn btn-icon btn-primary rounded-circle px-2" href="{{route('admin.dashboard')}}">
                                    <i class="tio-home-outlined"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for different reports -->
        <div class="card mt-3">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="order-report-tab" data-toggle="tab" href="#order-report" role="tab">
                            <i class="tio-shopping-cart mr-1"></i> {{translate('Order_Report')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="sales-report-tab" data-toggle="tab" href="#sales-report" role="tab">
                            <i class="tio-dollar mr-1"></i> {{translate('Sales_Report')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="product-report-tab" data-toggle="tab" href="#product-report" role="tab">
                            <i class="tio-category mr-1"></i> {{translate('Product_Report')}}
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    <!-- Order Report Tab -->
                    <div class="tab-pane fade show active" id="order-report" role="tabpanel">
                        <form action="javascript:" id="order-report-form" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-sm-6 col-md-3">
                                    <label>{{translate('Select Branch')}}</label>
                                    <select class="custom-select" name="branch_id" required>
                                        <option value="all">{{translate('All Branches')}}</option>
                                        @foreach($branches as $branch)
                                            <option value="{{$branch['id']}}">{{$branch['name']}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6 col-md-3">
                                    <label>{{translate('From Date')}}</label>
                                    <input type="date" name="from" class="form-control" required>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <label>{{translate('To Date')}}</label>
                                    <input type="date" name="to" class="form-control" required>
                                </div>
                                <div class="col-sm-6 col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">{{translate('Show')}}</button>
                                </div>
                            </div>
                        </form>

                        <!-- Statistics Cards -->
                        <div class="row mt-4" id="order-stats" style="display: none;">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2">{{translate('Total Orders')}}</h6>
                                        <h3 id="total-orders">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2 text-success">{{translate('Delivered')}}</h6>
                                        <h3 id="delivered-orders" class="text-success">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2 text-danger">{{translate('Canceled')}}</h6>
                                        <h3 id="canceled-orders" class="text-danger">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2 text-info">{{translate('Total Amount')}}</h6>
                                        <h3 id="total-amount" class="text-info">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3 mb-3">
                            <button type="button" class="btn btn-success" id="print-order-report" style="display: none;">
                                <i class="tio-print"></i> {{translate('Print Report')}}
                            </button>
                        </div>

                        <div class="table-responsive" id="order-report-table"></div>
                    </div>

                    <!-- Sales Report Tab -->
                    <div class="tab-pane fade" id="sales-report" role="tabpanel">
                        <form action="javascript:" id="sales-report-form" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-sm-6 col-md-3">
                                    <label>{{translate('Select Branch')}}</label>
                                    <select class="custom-select" name="branch_id" required>
                                        <option value="all">{{translate('All Branches')}}</option>
                                        @foreach($branches as $branch)
                                            <option value="{{$branch['id']}}">{{$branch['name']}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6 col-md-3">
                                    <label>{{translate('From Date')}}</label>
                                    <input type="date" name="from" class="form-control" required>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <label>{{translate('To Date')}}</label>
                                    <input type="date" name="to" class="form-control" required>
                                </div>
                                <div class="col-sm-6 col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">{{translate('Show')}}</button>
                                </div>
                            </div>
                        </form>

                        <div class="row mt-4" id="sales-stats" style="display: none;">
                            <div class="col-md-4 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2">{{translate('Total Orders')}}</h6>
                                        <h3 id="sales-order-count">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2">{{translate('Total Items')}}</h6>
                                        <h3 id="sales-item-qty">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2">{{translate('Total Amount')}}</h6>
                                        <h3 id="sales-amount">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3 mb-3">
                            <button type="button" class="btn btn-success" id="print-sales-report" style="display: none;">
                                <i class="tio-print"></i> {{translate('Print Report')}}
                            </button>
                        </div>

                        <div class="table-responsive" id="sales-report-table"></div>
                    </div>

                    <!-- Product Report Tab -->
                    <div class="tab-pane fade" id="product-report" role="tabpanel">
                        <form action="javascript:" id="product-report-form" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-sm-6 col-md-3">
                                    <label>{{translate('Select Branch')}}</label>
                                    <select class="custom-select" name="branch_id" required>
                                        <option value="all">{{translate('All Branches')}}</option>
                                        @foreach($branches as $branch)
                                            <option value="{{$branch['id']}}">{{$branch['name']}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6 col-md-2">
                                    <label>{{translate('Select Product')}}</label>
                                    <select class="form-control js-select2-custom" name="product_id" required>
                                        <option value="all">{{translate('All Products')}}</option>
                                        @foreach(\App\Model\Product::all() as $product)
                                            <option value="{{$product['id']}}">{{$product['name']}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6 col-md-2">
                                    <label>{{translate('From Date')}}</label>
                                    <input type="date" name="from" class="form-control" required>
                                </div>
                                <div class="col-sm-6 col-md-2">
                                    <label>{{translate('To Date')}}</label>
                                    <input type="date" name="to" class="form-control" required>
                                </div>
                                <div class="col-sm-6 col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary btn-block">{{translate('Show')}}</button>
                                </div>
                            </div>
                        </form>

                        <div class="row mt-4" id="product-stats" style="display: none;">
                            <div class="col-md-4 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2">{{translate('Total Orders')}}</h6>
                                        <h3 id="product-order-count">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2">{{translate('Total Items')}}</h6>
                                        <h3 id="product-item-qty">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card card-sm">
                                    <div class="card-body">
                                        <h6 class="mb-2">{{translate('Total Amount')}}</h6>
                                        <h3 id="product-amount">0</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3 mb-3">
                            <button type="button" class="btn btn-success" id="print-product-report" style="display: none;">
                                <i class="tio-print"></i> {{translate('Print Report')}}
                            </button>
                        </div>

                        <div class="table-responsive" id="product-report-table"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        'use strict';

        $(document).ready(function() {
            $('.js-select2-custom').each(function () {
                $.HSCore.components.HSSelect2.init($(this));
            });
        });

        // Order Report Form
        $('#order-report-form').on('submit', function() {
            let formData = $(this).serialize();
            
            $.post({
                url: "{{route('admin.report.branch-order-report')}}",
                data: formData,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#order-report-table').html(data.view);
                    $('#order-stats').show();
                    $('#print-order-report').show();
                    
                    // Update stats
                    $('#total-orders').text(data.stats.total);
                    $('#delivered-orders').text(data.stats.delivered);
                    $('#canceled-orders').text(data.stats.canceled);
                    $('#total-amount').text('{{ \App\CentralLogics\Helpers::currency_symbol() }}' + data.stats.total_amount.toFixed(2));
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        });

        // Sales Report Form
        $('#sales-report-form').on('submit', function() {
            let formData = $(this).serialize();
            
            $.post({
                url: "{{route('admin.report.branch-sales-report')}}",
                data: formData,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#sales-report-table').html(data.view);
                    $('#sales-stats').show();
                    $('#print-sales-report').show();
                    
                    // Update stats
                    $('#sales-order-count').text(data.order_count);
                    $('#sales-item-qty').text(data.item_qty);
                    $('#sales-amount').html(data.order_sum);
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        });

        // Product Report Form
        $('#product-report-form').on('submit', function() {
            let formData = $(this).serialize();
            
            $.post({
                url: "{{route('admin.report.branch-product-report')}}",
                data: formData,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#product-report-table').html(data.view);
                    $('#product-stats').show();
                    $('#print-product-report').show();
                    
                    // Update stats
                    $('#product-order-count').text(data.order_count);
                    $('#product-item-qty').text(data.item_qty);
                    $('#product-amount').html(data.order_sum);
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        });

        // Print Order Report
        $('#print-order-report').on('click', function() {
            let form = $('#order-report-form');
            let formData = form.serialize();
            
            // Create a temporary form for PDF download
            let tempForm = $('<form>', {
                'method': 'POST',
                'action': '{{route("admin.report.print-branch-order-report")}}',
                'target': '_blank'
            });
            
            // Add CSRF token
            tempForm.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{csrf_token()}}'
            }));
            
            // Add form data
            form.serializeArray().forEach(function(item) {
                tempForm.append($('<input>', {
                    'type': 'hidden',
                    'name': item.name,
                    'value': item.value
                }));
            });
            
            $('body').append(tempForm);
            tempForm.submit();
            tempForm.remove();
        });

        // Print Sales Report
        $('#print-sales-report').on('click', function() {
            let form = $('#sales-report-form');
            
            let tempForm = $('<form>', {
                'method': 'POST',
                'action': '{{route("admin.report.print-branch-sales-report")}}',
                'target': '_blank'
            });
            
            tempForm.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{csrf_token()}}'
            }));
            
            form.serializeArray().forEach(function(item) {
                tempForm.append($('<input>', {
                    'type': 'hidden',
                    'name': item.name,
                    'value': item.value
                }));
            });
            
            $('body').append(tempForm);
            tempForm.submit();
            tempForm.remove();
        });

        // Print Product Report
        $('#print-product-report').on('click', function() {
            let form = $('#product-report-form');
            
            let tempForm = $('<form>', {
                'method': 'POST',
                'action': '{{route("admin.report.print-branch-product-report")}}',
                'target': '_blank'
            });
            
            tempForm.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{csrf_token()}}'
            }));
            
            form.serializeArray().forEach(function(item) {
                tempForm.append($('<input>', {
                    'type': 'hidden',
                    'name': item.name,
                    'value': item.value
                }));
            });
            
            $('body').append(tempForm);
            tempForm.submit();
            tempForm.remove();
        });

        // Date validation
        $('input[type="date"]').change(function() {
            let form = $(this).closest('form');
            let from = form.find('input[name="from"]').val();
            let to = form.find('input[name="to"]').val();
            
            if (from && to && from > to) {
                form.find('input[name="from"]').val('');
                form.find('input[name="to"]').val('');
                toastr.error('{{translate("Invalid date range!")}}', 'Error', {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
        });
    </script>
@endpush