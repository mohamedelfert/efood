@extends('layouts.admin.app')

@section('title', translate('Update banner'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/banner.png')}}" alt="">
                <span class="page-header-title">{{translate('Update_Banner')}}</span>
            </h2>
        </div>

        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.banner.update',[$banner['id']])}}" method="post" enctype="multipart/form-data" id="banner-edit-form">
                    @csrf @method('put')

                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('title')}}<span class="text-danger ml-1">*</span></label>
                                        <input type="text" name="title" value="{{$banner['title']}}" class="form-control" placeholder="{{translate('New banner')}}" required>
                                    </div>

                                    <div class="form-group">
                                        <label class="input-label">{{translate('banner_type')}}<span class="text-danger ml-1">*</span></label>
                                        <select name="banner_type" class="custom-select" id="banner-type-select" required>
                                            <option value="single_product" {{$banner->banner_type == 'single_product' ? 'selected' : ''}}>{{translate('single_product')}}</option>
                                            <option value="multiple_products" {{$banner->banner_type == 'multiple_products' ? 'selected' : ''}}>{{translate('multiple_products')}}</option>
                                            <option value="category" {{$banner->banner_type == 'category' ? 'selected' : ''}}>{{translate('category')}}</option>
                                        </select>
                                    </div>

                                    <!-- Single Product -->
                                    <div class="form-group banner-type-section" id="single-product-section" style="display: {{$banner->banner_type == 'single_product' ? 'block' : 'none'}};">
                                        <label class="input-label">{{translate('product')}} <span class="text-danger ml-1">*</span></label>
                                        <select name="product_id" class="custom-select js-select2-custom" id="single-product-select">
                                            <option value="">{{translate('select_a_product')}}</option>
                                            @foreach($products as $product)
                                                <option value="{{$product['id']}}" data-price="{{$product->price}}" {{$banner->product_id == $product['id'] ? 'selected' : ''}}>{{$product['name']}} - {{$product->price}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Multiple Products -->
                                    <div class="form-group banner-type-section" id="multiple-products-section" style="display: {{$banner->banner_type == 'multiple_products' ? 'block' : 'none'}};">
                                        <label class="input-label">{{translate('products')}} <span class="text-danger ml-1">*</span></label>
                                        <select name="product_ids[]" class="custom-select js-select2-custom" id="multiple-products-select" multiple>
                                            @foreach($products as $product)
                                                <option value="{{$product['id']}}" data-price="{{$product->price}}" 
                                                    {{$banner->products->contains($product['id']) ? 'selected' : ''}}>
                                                    {{$product['name']}} - {{$product->price}}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">{{translate('select_multiple_products_for_offer')}}</small>
                                    </div>

                                    <!-- Category -->
                                    <div class="form-group banner-type-section" id="category-section" style="display: {{$banner->banner_type == 'category' ? 'block' : 'none'}};">
                                        <label class="input-label">{{translate('category')}} <span class="text-danger ml-1">*</span></label>
                                        <select name="category_id" class="custom-select js-select2-custom" id="category-select">
                                            <option value="">{{translate('select_a_category')}}</option>
                                            @foreach($categories as $category)
                                                <option value="{{$category['id']}}" {{$banner->category_id == $category['id'] ? 'selected' : ''}}>{{$category['name']}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Pricing Section -->
                                    <div id="pricing-section" style="display: {{$banner->banner_type != 'category' ? 'block' : 'none'}};">
                                        <div class="card bg-light mt-3">
                                            <div class="card-body">
                                                <h5 class="mb-3">{{translate('offer_pricing')}}</h5>
                                                
                                                <!-- Original Price Display -->
                                                <div class="alert alert-info" id="original-price-display">
                                                    <strong>{{translate('total_original_price')}}:</strong> 
                                                    <span id="original-price-value">{{$banner->calculateOriginalPrice()}}</span>
                                                </div>

                                                <!-- Discount Type -->
                                                <div class="form-group">
                                                    <label class="input-label">{{translate('discount_type')}}<span class="text-danger ml-1">*</span></label>
                                                    <select name="discount_type" class="custom-select" id="discount-type-select" required>
                                                        <option value="fixed" {{$banner->discount_type == 'fixed' ? 'selected' : ''}}>{{translate('fixed_amount')}}</option>
                                                        <option value="percentage" {{$banner->discount_type == 'percentage' ? 'selected' : ''}}>{{translate('percentage')}}</option>
                                                    </select>
                                                </div>

                                                <!-- Fixed Discount -->
                                                <div class="form-group" id="fixed-discount-section" style="display: {{$banner->discount_type == 'fixed' ? 'block' : 'none'}};">
                                                    <label class="input-label">{{translate('discount_amount')}}</label>
                                                    <input type="number" name="total_discount_amount" id="discount-amount-input" value="{{$banner->total_discount_amount}}" class="form-control" placeholder="0.00" step="0.01" min="0">
                                                    <small class="text-muted">{{translate('enter_discount_amount')}}</small>
                                                </div>

                                                <!-- Percentage Discount -->
                                                <div class="form-group" id="percentage-discount-section" style="display: {{$banner->discount_type == 'percentage' ? 'block' : 'none'}};">
                                                    <label class="input-label">{{translate('discount_percentage')}}</label>
                                                    <input type="number" name="total_discount_percentage" id="discount-percentage-input" value="{{$banner->total_discount_percentage}}" class="form-control" placeholder="0" step="0.01" min="0" max="100">
                                                    <small class="text-muted">{{translate('enter_discount_percentage')}}</small>
                                                </div>

                                                <!-- Or Offer Price -->
                                                <div class="text-center my-2">
                                                    <small class="text-muted">{{translate('or')}}</small>
                                                </div>

                                                <div class="form-group">
                                                    <label class="input-label">{{translate('total_offer_price')}}</label>
                                                    <input type="number" name="total_offer_price" id="offer-price-input" value="{{$banner->total_offer_price}}" class="form-control" placeholder="0.00" step="0.01" min="0">
                                                    <small class="text-muted">{{translate('set_final_offer_price_directly')}}</small>
                                                </div>

                                                <!-- Final Price Display -->
                                                <div class="alert alert-success" id="final-price-display">
                                                    <strong>{{translate('final_offer_price')}}:</strong> 
                                                    <span id="final-price-value">{{$banner->calculateFinalPrice()}}</span>
                                                    <br>
                                                    <strong>{{translate('you_save')}}:</strong> 
                                                    <span id="savings-value">{{$banner->getDiscountAmount()}}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date Range -->
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="input-label">{{translate('start_date')}}</label>
                                                <input type="date" name="start_date" value="{{$banner->start_date ? $banner->start_date->format('Y-m-d') : ''}}" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="input-label">{{translate('end_date')}}</label>
                                                <input type="date" name="end_date" value="{{$banner->end_date ? $banner->end_date->format('Y-m-d') : ''}}" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <label class="mb-0">{{translate('banner_image')}}</label>
                                            <small class="text-danger">* ( {{translate('ratio 2:1')}} )</small>
                                        </div>
                                        <div class="d-flex justify-content-center mt-4">
                                            <div class="upload-file">
                                                <input type="file" name="image" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" class="upload-file__input">
                                                <div class="upload-file__img_drag upload-file__img max-h-200px overflow-hidden">
                                                    <img width="465" id="viewer" src="{{$banner->imageFullPath}}" alt="{{ translate('banner image') }}"/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-4">
                                <a href="{{route('admin.banner.list')}}" class="btn btn-secondary">{{translate('cancel')}}</a>
                                <button type="submit" class="btn btn-primary">{{translate('update')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        "use strict";

        let originalPrice = {{$banner->calculateOriginalPrice()}};

        $('.js-select2-custom').each(function () {
            var select2 = $.HSCore.components.HSSelect2.init($(this));
        });

        // Banner type change
        $('#banner-type-select').change(function() {
            var selectedType = $(this).val();
            $('.banner-type-section').hide();
            $('#pricing-section').hide();
            
            // Reset required
            $('#single-product-select').prop('required', false);
            $('#multiple-products-select').prop('required', false);
            $('#category-select').prop('required', false);
            
            if (selectedType === 'single_product') {
                $('#single-product-section').show();
                $('#single-product-select').prop('required', true);
                $('#pricing-section').show();
            } else if (selectedType === 'multiple_products') {
                $('#multiple-products-section').show();
                $('#multiple-products-select').prop('required', true);
                $('#pricing-section').show();
            } else if (selectedType === 'category') {
                $('#category-section').show();
                $('#category-select').prop('required', true);
            }
        });

        // Single product selection
        $('#single-product-select').change(function() {
            var selectedOption = $(this).find('option:selected');
            originalPrice = parseFloat(selectedOption.data('price')) || 0;
            updatePriceDisplay();
        });

        // Multiple products selection
        $('#multiple-products-select').change(function() {
            var total = 0;
            $(this).find('option:selected').each(function() {
                total += parseFloat($(this).data('price')) || 0;
            });
            originalPrice = total;
            updatePriceDisplay();
        });

        // Discount type change
        $('#discount-type-select').change(function() {
            var discountType = $(this).val();
            
            if (discountType === 'fixed') {
                $('#fixed-discount-section').show();
                $('#percentage-discount-section').hide();
                $('#discount-percentage-input').val('');
            } else {
                $('#fixed-discount-section').hide();
                $('#percentage-discount-section').show();
                $('#discount-amount-input').val('');
            }
            
            calculateFinalPrice();
        });

        // Price inputs change
        $('#discount-amount-input, #discount-percentage-input, #offer-price-input').on('input', function() {
            calculateFinalPrice();
        });

        function updatePriceDisplay() {
            if (originalPrice > 0) {
                $('#original-price-value').text(originalPrice.toFixed(2));
                $('#original-price-display').show();
                calculateFinalPrice();
            } else {
                $('#original-price-display').hide();
                $('#final-price-display').hide();
            }
        }

        function calculateFinalPrice() {
            if (originalPrice <= 0) return;
            
            var finalPrice = originalPrice;
            var offerPrice = parseFloat($('#offer-price-input').val()) || 0;
            
            // If offer price is set directly, use it
            if (offerPrice > 0) {
                finalPrice = offerPrice;
                // Clear discount fields
                $('#discount-amount-input').val('');
                $('#discount-percentage-input').val('');
            } else {
                // Calculate based on discount
                var discountType = $('#discount-type-select').val();
                
                if (discountType === 'fixed') {
                    var discountAmount = parseFloat($('#discount-amount-input').val()) || 0;
                    finalPrice = Math.max(0, originalPrice - discountAmount);
                } else if (discountType === 'percentage') {
                    var discountPercentage = parseFloat($('#discount-percentage-input').val()) || 0;
                    finalPrice = originalPrice - (originalPrice * (discountPercentage / 100));
                }
                
                // Clear offer price if discount is used
                if ($('#discount-amount-input').val() || $('#discount-percentage-input').val()) {
                    $('#offer-price-input').val('');
                }
            }
            
            var savings = originalPrice - finalPrice;
            
            $('#final-price-value').text(finalPrice.toFixed(2));
            $('#savings-value').text(savings.toFixed(2));
            $('#final-price-display').show();
        }

        // Image preview
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("input[name='image']").change(function () {
            readURL(this);
        });

        // Initialize on page load
        $(document).ready(function() {
            updatePriceDisplay();
        });
    </script>
@endpush