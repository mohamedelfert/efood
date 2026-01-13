@extends('layouts.admin.app')

@section('title', translate('Sale Report'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/sales.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Sale_Report')}}
                </span>
            </h2>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="media flex-column flex-sm-row flex-wrap align-items-sm-center gap-4">
                    <div class="avatar avatar-xl">
                        <img class="avatar-img" src="{{asset('public/assets/admin')}}/svg/illustrations/credit-card.svg"
                            alt="{{ translate('sale_report') }}">
                    </div>

                    <div class="media-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="">
                                <h2 class="page-header-title">{{translate('sale')}} {{translate('report')}} {{translate('overview')}}</h2>

                                <div class="">
                                    <span>{{translate('admin')}}:</span>
                                    <a href="#">{{auth('admin')->user()->name}}</a>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a class="btn btn-icon btn-primary rounded-circle px-2" href="{{route('admin.dashboard')}}">
                                    <i class="tio-home-outlined"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <form action="javascript:" id="search-form" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">{{translate('branch')}}</label>
                            <select class="form-control" name="branch_id" id="branch_id" required>
                                <option value="all">{{ translate('all_branches') }}</option>
                                @foreach(\App\Model\Branch::all() as $branch)
                                    <option value="{{$branch['id']}}" {{session('branch_filter')==$branch['id']?'selected':''}}>
                                        {{$branch['name']}}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <label class="form-label">{{translate('from')}}</label>
                            <input type="date" name="from" id="from_date" class="form-control" required>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <label class="form-label">{{translate('to')}}</label>
                            <input type="date" name="to" id="to_date" class="form-control" required>
                        </div>

                        <div class="col-sm-6 col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="tio-filter-list"></i>
                                {{translate('filter')}}
                            </button>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card border-0 bg-soft-success">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-sm-6 col-md-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-circle bg-primary text-white me-3">
                                                    <i class="tio-shopping-cart"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{translate('total_orders')}}</h6>
                                                    <h4 class="mb-0" id="order_count">0</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-circle bg-warning text-white me-3">
                                                    <i class="tio-category"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{translate('total_Item_Qty')}}</h6>
                                                    <h4 class="mb-0" id="item_count">0</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <div class="d-flex align-items-center">
                                                <div class="icon-circle bg-success text-white me-3">
                                                    <i class="tio-dollar"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{translate('total_amount')}}</h6>
                                                    <h4 class="mb-0" id="order_amount">{{\App\CentralLogics\Helpers::set_symbol(0)}}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="card mt-3">
                    <div class="card-header border-0">
                        <h5 class="card-title">
                            <i class="tio-receipt"></i>
                            {{translate('sales_details')}}
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{route('admin.report.export-sale-report')}}" 
                            class="btn btn-outline-danger btn-sm" 
                            id="export-sale-pdf-btn"
                            style="display: none;">
                                <i class="tio-document-pdf"></i> {{translate('download_pdf')}}
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" id="set-rows">
                            @include('admin-views.report.partials._table',['data'=>[]])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        $(document).ready(function () {
            // DataTable initialization removed since we're using partials now
        });

        $('#search-form').on('submit', function (e) {
            e.preventDefault();
            
            let fromDate = $('#from_date').val();
            let toDate = $('#to_date').val();

            if (!fromDate || !toDate) {
                toastr.error('{{translate('Please select date range')}}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            $.post({
                url: "{{route('admin.report.sale-report-filter')}}",
                data: $('#search-form').serialize(),
                beforeSend: function () {
                    $('#loading').show();
                    $('#export-sale-pdf-btn').hide();
                },
                success: function (data) {
                    $('#order_count').html(data.order_count);
                    $('#order_amount').html(data.order_sum);
                    $('#item_count').html(data.item_qty);
                    $('#set-rows').html(data.view);
                    
                    // Show export button if data exists
                    if (data.order_count > 0) {
                        $('#export-sale-pdf-btn').show();
                    }
                    
                    toastr.success('{{translate('Report generated successfully')}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                error: function(xhr) {
                    toastr.error('{{translate('Something went wrong')}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        });

        $('#from_date,#to_date').change(function () {
            let fr = $('#from_date').val();
            let to = $('#to_date').val();
            if (fr != '' && to != '') {
                if (fr > to) {
                    $('#from_date').val('');
                    $('#to_date').val('');
                    toastr.error('{{translate('Invalid date range!')}}', 'Error', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            }
        });
    </script>
@endpush