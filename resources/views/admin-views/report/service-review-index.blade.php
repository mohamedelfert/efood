@extends('layouts.admin.app')

@section('title', translate('Service Review Report'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/customer-service.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Service_Review_Reports')}}
                </span>
            </h2>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="media flex-column flex-sm-row flex-wrap align-items-sm-center gap-4">
                    <div class="avatar avatar-xl">
                        <img class="avatar-img" src="{{asset('public/assets/admin')}}/svg/illustrations/customer-service.svg"
                            alt="{{ translate('service_review_report') }}">
                    </div>

                    <div class="media-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="">
                                <h2 class="page-header-title">{{translate('Service_Review_Report_Overview')}}</h2>

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

        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="tio-filter-list mr-1"></i>
                    {{translate('Filter_Service_Reviews')}}
                </h5>
            </div>

            <div class="card-body">
                <form action="javascript:" id="service-review-form" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <label>{{translate('Service Type')}}</label>
                            <select class="custom-select" name="service_type" required>
                                <option value="all">{{translate('All Services')}}</option>
                                <option value="delivery">{{translate('Delivery')}}</option>
                                <option value="packaging">{{translate('Packaging')}}</option>
                                <option value="customer_service">{{translate('Customer Service')}}</option>
                                <option value="food_quality">{{translate('Food Quality')}}</option>
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-2">
                            <label>{{translate('Min Rating')}}</label>
                            <select class="custom-select" name="min_rating">
                                <option value="0">{{translate('All')}}</option>
                                <option value="1">1 {{translate('Star')}}</option>
                                <option value="2">2 {{translate('Stars')}}</option>
                                <option value="3">3 {{translate('Stars')}}</option>
                                <option value="4">4 {{translate('Stars')}}</option>
                                <option value="5">5 {{translate('Stars')}}</option>
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-2">
                            <label>{{translate('Max Rating')}}</label>
                            <select class="custom-select" name="max_rating">
                                <option value="5">5 {{translate('Stars')}}</option>
                                <option value="4">4 {{translate('Stars')}}</option>
                                <option value="3">3 {{translate('Stars')}}</option>
                                <option value="2">2 {{translate('Stars')}}</option>
                                <option value="1">1 {{translate('Star')}}</option>
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

                        <div class="col-sm-6 col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="tio-search"></i> {{translate('Show')}}
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Statistics Cards -->
                <div class="row mt-4" id="review-stats" style="display: none;">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card card-sm">
                            <div class="card-body">
                                <h6 class="mb-2">{{translate('Total Reviews')}}</h6>
                                <h3 id="total-reviews">0</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card card-sm bg-success text-white">
                            <div class="card-body">
                                <h6 class="mb-2 text-white">{{translate('Average Rating')}}</h6>
                                <h3 id="average-rating" class="text-white">0.0</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-sm">
                            <div class="card-body">
                                <h6 class="mb-3">{{translate('Rating Distribution')}}</h6>
                                <div class="rating-bars">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="mr-2" style="width: 60px;">5 <i class="tio-star text-warning"></i></span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-success" id="bar-5" style="width: 0%"></div>
                                        </div>
                                        <span class="ml-2" id="count-5">0</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="mr-2" style="width: 60px;">4 <i class="tio-star text-warning"></i></span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-info" id="bar-4" style="width: 0%"></div>
                                        </div>
                                        <span class="ml-2" id="count-4">0</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="mr-2" style="width: 60px;">3 <i class="tio-star text-warning"></i></span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-warning" id="bar-3" style="width: 0%"></div>
                                        </div>
                                        <span class="ml-2" id="count-3">0</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="mr-2" style="width: 60px;">2 <i class="tio-star text-warning"></i></span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-orange" id="bar-2" style="width: 0%"></div>
                                        </div>
                                        <span class="ml-2" id="count-2">0</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="mr-2" style="width: 60px;">1 <i class="tio-star text-warning"></i></span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-danger" id="bar-1" style="width: 0%"></div>
                                        </div>
                                        <span class="ml-2" id="count-1">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Aspect Ratings -->
                <div class="row mt-3" id="aspect-ratings" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="mb-3">{{translate('Service Aspect Ratings')}}</h6>
                                <div id="aspect-ratings-content"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3 mb-3" id="action-buttons" style="display: none !important;">
                    <button type="button" class="btn btn-success" id="export-excel">
                        <i class="tio-download"></i> {{translate('Export Excel')}}
                    </button>
                    <button type="button" class="btn btn-primary" id="print-report">
                        <i class="tio-print"></i> {{translate('Print Report')}}
                    </button>
                </div>

                <div class="table-responsive" id="review-table"></div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        'use strict';

        $('#service-review-form').on('submit', function() {
            let formData = $(this).serialize();
            
            $.post({
                url: "{{route('admin.report.service-review-report')}}",
                data: formData,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#review-table').html(data.view);
                    $('#review-stats').show();
                    $('#action-buttons').show();
                    
                    // Update stats
                    $('#total-reviews').text(data.stats.total);
                    $('#average-rating').text(data.stats.average_rating);
                    
                    // Update rating distribution
                    let total = data.stats.total;
                    let dist = data.stats.rating_distribution;
                    
                    updateRatingBar(5, dist['5_star'], total);
                    updateRatingBar(4, dist['4_star'], total);
                    updateRatingBar(3, dist['3_star'], total);
                    updateRatingBar(2, dist['2_star'], total);
                    updateRatingBar(1, dist['1_star'], total);

                    // Show aspect ratings if available
                    if (data.stats.aspect_ratings && Object.keys(data.stats.aspect_ratings).length > 0) {
                        $('#aspect-ratings').show();
                        let aspectHtml = '';
                        $.each(data.stats.aspect_ratings, function(aspect, rating) {
                            let aspectName = aspect.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            aspectHtml += `
                                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                    <span class="font-weight-bold">${aspectName}</span>
                                    <span class="badge badge-soft-warning">${rating} <i class="tio-star"></i></span>
                                </div>
                            `;
                        });
                        $('#aspect-ratings-content').html(aspectHtml);
                    } else {
                        $('#aspect-ratings').hide();
                    }
                },
                complete: function() {
                    $('#loading').hide();
                },
                error: function(xhr) {
                    toastr.error('{{translate("Failed to load report")}}');
                }
            });
        });

        function updateRatingBar(rating, count, total) {
            let percentage = total > 0 ? (count / total * 100) : 0;
            $('#bar-' + rating).css('width', percentage + '%');
            $('#count-' + rating).text(count);
        }

        // Print Report
        $('#print-report').on('click', function() {
            let form = $('#service-review-form');
            let tempForm = $('<form>', {
                'method': 'POST',
                'action': '{{route("admin.report.print-service-review-report")}}',
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

        // Export Excel
        $('#export-excel').on('click', function() {
            window.location.href = '{{route("admin.report.export-service-review-report")}}';
        });

        // Date validation
        $('input[type="date"]').change(function() {
            let form = $(this).closest('form');
            let from = form.find('input[name="from"]').val();
            let to = form.find('input[name="to"]').val();
            
            if (from && to && from > to) {
                form.find('input[name="from"]').val('');
                form.find('input[name="to"]').val('');
                toastr.error('{{translate("Invalid date range!")}}');
            }
        });
    </script>
@endpush