@extends('layouts.admin.app')

@section('title', translate('Branch Review Report'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/reviews.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Branch_Review_Reports')}}
                </span>
            </h2>
        </div>

        <!-- Overview Card -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="media flex-column flex-sm-row flex-wrap align-items-sm-center gap-4">
                    <div class="avatar avatar-xl">
                        <img class="avatar-img" src="{{asset('public/assets/admin')}}/svg/illustrations/rating.svg"
                            alt="{{ translate('branch_review_report') }}">
                    </div>

                    <div class="media-body">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="">
                                <h2 class="page-header-title">{{translate('Branch_Review_Report_Overview')}}</h2>

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

        <!-- Filter Card -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="tio-filter-list mr-1"></i>
                    {{translate('Filter_Reviews')}}
                </h5>
            </div>

            <div class="card-body">
                <form action="javascript:" id="branch-review-form" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <label>{{translate('Select_Branch')}}</label>
                            <select class="form-control custom-select" name="branch_id" required>
                                <option value="all">{{translate('All_Branches')}}</option>
                                @foreach($branches as $branch)
                                    <option value="{{$branch['id']}}">{{$branch['name']}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-2">
                            <label>{{translate('Min_Rating')}}</label>
                            <select class="form-control custom-select" name="min_rating">
                                <option value="0">{{translate('All')}}</option>
                                <option value="1">1 {{translate('Star')}}</option>
                                <option value="2">2 {{translate('Stars')}}</option>
                                <option value="3">3 {{translate('Stars')}}</option>
                                <option value="4">4 {{translate('Stars')}}</option>
                                <option value="5">5 {{translate('Stars')}}</option>
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-2">
                            <label>{{translate('Max_Rating')}}</label>
                            <select class="form-control custom-select" name="max_rating">
                                <option value="5" selected>5 {{translate('Stars')}}</option>
                                <option value="4">4 {{translate('Stars')}}</option>
                                <option value="3">3 {{translate('Stars')}}</option>
                                <option value="2">2 {{translate('Stars')}}</option>
                                <option value="1">1 {{translate('Star')}}</option>
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-2">
                            <label>{{translate('From_Date')}}</label>
                            <input type="date" name="from" class="form-control" required>
                        </div>

                        <div class="col-sm-6 col-md-2">
                            <label>{{translate('To_Date')}}</label>
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
                                <h6 class="mb-2">{{translate('Total_Reviews')}}</h6>
                                <h3 id="total-reviews">0</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card card-sm bg-warning text-white">
                            <div class="card-body">
                                <h6 class="mb-2 text-white">{{translate('Average_Rating')}}</h6>
                                <h3 id="average-rating" class="text-white">0.0</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-sm">
                            <div class="card-body">
                                <h6 class="mb-3">{{translate('Rating_Distribution')}}</h6>
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

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2 mt-3 mb-3" id="action-buttons" style="display: none !important;">
                    <button type="button" class="btn btn-success" id="export-excel">
                        <i class="tio-download"></i> {{translate('Export_Excel')}}
                    </button>
                    <button type="button" class="btn btn-primary" id="print-report">
                        <i class="tio-print"></i> {{translate('Print_Report')}}
                    </button>
                </div>

                <!-- Reviews Table -->
                <div class="table-responsive" id="review-table"></div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{translate('Review_Image')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="modalImage" src="" alt="review" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        'use strict';

        // Branch Review Form Submit
        $('#branch-review-form').on('submit', function() {
            let formData = $(this).serialize();
            
            $.post({
                url: "{{route('admin.report.branch-review-report')}}",
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
                },
                complete: function() {
                    $('#loading').hide();
                },
                error: function(xhr) {
                    toastr.error('{{translate("Failed to load report")}}');
                }
            });
        });

        // Update Rating Bar Function
        function updateRatingBar(rating, count, total) {
            let percentage = total > 0 ? (count / total * 100) : 0;
            $('#bar-' + rating).css('width', percentage + '%');
            $('#count-' + rating).text(count);
        }

        // Print Report
        $('#print-report').on('click', function() {
            let form = $('#branch-review-form');
            let tempForm = $('<form>', {
                'method': 'POST',
                'action': '{{route("admin.report.print-branch-review-report")}}',
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
            window.location.href = '{{route("admin.report.export-branch-review-report")}}';
        });

        // Date Validation
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

        // Image Preview Modal
        window.showImageModal = function(imageSrc) {
            $('#modalImage').attr('src', imageSrc);
            $('#imageModal').modal('show');
        };
    </script>
@endpush