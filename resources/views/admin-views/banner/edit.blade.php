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

        <div class="row g-2">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.banner.update', $banner->id)}}" method="post" enctype="multipart/form-data" id="banner-edit-form">
                    @csrf
                    @method('PUT')

                    <div class="card banner-form">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">

                                    <!-- Title -->
                                    <div class="form-group">
                                        <label class="input-label">{{translate('title')}}<span class="text-danger ml-1">*</span></label>
                                        <input type="text" name="title" value="{{old('title', $banner->title)}}" 
                                               class="form-control" placeholder="{{translate('New banner')}}" required>
                                    </div>

                                    <!-- Branch Availability -->
                                    <div class="card bg-light mb-3">
                                        <div class="card-body">
                                            <h5 class="mb-3">{{translate('branch_availability')}}</h5>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="global-banner" name="is_global" value="1" 
                                                           class="custom-control-input" {{old('is_global', $banner->is_global ? 1 : 0) == 1 ? 'checked' : ''}}>
                                                    <label class="custom-control-label" for="global-banner">
                                                        {{translate('global_banner')}}
                                                        <small class="text-muted d-block">{{translate('show_in_all_branches')}}</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="form-group mb-0">
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="branch-specific" name="is_global" value="0" 
                                                           class="custom-control-input" {{old('is_global', $banner->is_global ? 1 : 0) == 0 ? 'checked' : ''}}>
                                                    <label class="custom-control-label" for="branch-specific">
                                                        {{translate('branch_specific')}}
                                                        <small class="text-muted d-block">{{translate('show_in_selected_branches_only')}}</small>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Branch Selection -->
                                            <div class="form-group mt-3" id="branch-selection" 
                                                 style="display: {{old('is_global', $banner->is_global ? 1 : 0) == 0 ? 'block' : 'none'}};">
                                                <label class="input-label">{{translate('select_branches')}}<span class="text-danger ml-1">*</span></label>
                                                <select name="branch_ids[]" class="form-control js-select2-custom" multiple id="branch-select">
                                                    @foreach($branches as $branch)
                                                        <option value="{{ $branch->id }}"
                                                            {{ in_array($branch->id, old('branch_ids', $banner->branches->pluck('id')->toArray())) ? 'selected' : '' }}>
                                                            {{ $branch->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">{{translate('select_one_or_more_branches')}}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Banner Type -->
                                    <div class="form-group">
                                        <label class="input-label">{{translate('banner_type')}}<span class="text-danger ml-1">*</span></label>
                                        <select name="banner_type" class="custom-select" id="banner-type-select" required>
                                            <option value="single_product" {{old('banner_type', $banner->banner_type) == 'single_product' ? 'selected' : ''}}>
                                                {{translate('single_product')}}
                                            </option>
                                            <option value="multiple_products" {{old('banner_type', $banner->banner_type) == 'multiple_products' ? 'selected' : ''}}>
                                                {{translate('multiple_products')}}
                                            </option>
                                            <option value="category" {{old('banner_type', $banner->banner_type) == 'category' ? 'selected' : ''}}>
                                                {{translate('category')}}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Single Product -->
                                    <div class="form-group banner-type-section" id="single-product-section" 
                                         style="display: {{old('banner_type', $banner->banner_type) == 'single_product' ? 'block' : 'none'}};">
                                        <label class="input-label">{{translate('product')}} <span class="text-danger ml-1">*</span></label>
                                        <select name="product_id" class="custom-select js-select2-custom" id="single-product-select">
                                            <option value="">{{translate('select_a_product')}}</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" 
                                                        data-price="{{ $product->price }}"
                                                        {{ old('product_id', $banner->product_id) == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} - {{ $product->price }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Multiple Products -->
                                    <div class="form-group banner-type-section" id="multiple-products-section" 
                                         style="display: {{old('banner_type', $banner->banner_type) == 'multiple_products' ? 'block' : 'none'}};">
                                        <label class="input-label">{{translate('products')}} <span class="text-danger ml-1">*</span></label>
                                        <select name="product_ids[]" class="custom-select js-select2-custom" id="multiple-products-select" multiple>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" 
                                                        data-price="{{ $product->price }}"
                                                        {{ in_array($product->id, old('product_ids', $banner->products->pluck('id')->toArray())) ? 'selected' : '' }}>
                                                    {{ $product->name }} - {{ $product->price }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">{{translate('select_multiple_products_for_offer')}}</small>
                                    </div>

                                    <!-- Category -->
                                    <div class="form-group banner-type-section" id="category-section" 
                                         style="display: {{old('banner_type', $banner->banner_type) == 'category' ? 'block' : 'none'}};">
                                        <label class="input-label">{{translate('category')}} <span class="text-danger ml-1">*</span></label>
                                        <select name="category_id" class="custom-select js-select2-custom" id="category-select">
                                            <option value="">{{translate('select_a_category')}}</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" 
                                                        {{ old('category_id', $banner->category_id) == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Pricing Section -->
                                    <div id="pricing-section" style="display: {{old('banner_type', $banner->banner_type) != 'category' ? 'block' : 'none'}};">
                                        <div class="card bg-light mt-3">
                                            <div class="card-body">
                                                <h5 class="mb-3">{{translate('offer_pricing')}}</h5>

                                                <div class="alert alert-info" id="original-price-display">
                                                    <strong>{{translate('total_original_price')}}:</strong> 
                                                    <span id="original-price-value">{{ $banner->calculateOriginalPrice() }}</span>
                                                </div>

                                                <div class="form-group">
                                                    <label class="input-label">{{translate('discount_type')}}<span class="text-danger ml-1">*</span></label>
                                                    <select name="discount_type" class="custom-select" id="discount-type-select" required>
                                                        <option value="fixed"   {{old('discount_type', $banner->discount_type) == 'fixed' ? 'selected' : ''}}>
                                                            {{translate('fixed_amount')}}
                                                        </option>
                                                        <option value="percentage" {{old('discount_type', $banner->discount_type) == 'percentage' ? 'selected' : ''}}>
                                                            {{translate('percentage')}}
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="form-group" id="fixed-discount-section" 
                                                     style="display: {{old('discount_type', $banner->discount_type) == 'fixed' ? 'block' : 'none'}};">
                                                    <label class="input-label">{{translate('discount_amount')}}</label>
                                                    <input type="number" name="total_discount_amount" id="discount-amount-input" 
                                                           value="{{old('total_discount_amount', $banner->total_discount_amount)}}" 
                                                           class="form-control" placeholder="0.00" step="0.01" min="0">
                                                    <small class="text-muted">{{translate('enter_discount_amount')}}</small>
                                                </div>

                                                <div class="form-group" id="percentage-discount-section" 
                                                     style="display: {{old('discount_type', $banner->discount_type) == 'percentage' ? 'block' : 'none'}};">
                                                    <label class="input-label">{{translate('discount_percentage')}}</label>
                                                    <input type="number" name="total_discount_percentage" id="discount-percentage-input" 
                                                           value="{{old('total_discount_percentage', $banner->total_discount_percentage)}}" 
                                                           class="form-control" placeholder="0" step="0.01" min="0" max="100">
                                                    <small class="text-muted">{{translate('enter_discount_percentage')}}</small>
                                                </div>

                                                <div class="text-center my-2">
                                                    <small class="text-muted">{{translate('or')}}</small>
                                                </div>

                                                <div class="form-group">
                                                    <label class="input-label">{{translate('total_offer_price')}}</label>
                                                    <input type="number" name="total_offer_price" id="offer-price-input" 
                                                           value="{{old('total_offer_price', $banner->total_offer_price)}}" 
                                                           class="form-control" placeholder="0.00" step="0.01" min="0">
                                                    <small class="text-muted">{{translate('set_final_offer_price_directly')}}</small>
                                                </div>

                                                <div class="alert alert-success" id="final-price-display">
                                                    <strong>{{translate('final_offer_price')}}:</strong> 
                                                    <span id="final-price-value">{{ $banner->calculateFinalPrice() }}</span>
                                                    <br>
                                                    <strong>{{translate('you_save')}}:</strong> 
                                                    <span id="savings-value">{{ $banner->getDiscountAmount() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date Range -->
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="input-label">{{translate('start_date')}}</label>
                                                <input type="date" name="start_date" 
                                                       value="{{ old('start_date', $banner->start_date ? $banner->start_date->format('Y-m-d') : '') }}" 
                                                       class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="input-label">{{translate('end_date')}}</label>
                                                <input type="date" name="end_date" 
                                                       value="{{ old('end_date', $banner->end_date ? $banner->end_date->format('Y-m-d') : '') }}" 
                                                       class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Banner Image -->
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <label class="mb-0">{{translate('banner_image')}}</label>
                                            <small class="text-danger">* ( {{translate('ratio_2_1')}} )</small>
                                        </div>
                                        <div class="d-flex justify-content-center mt-4">
                                            <div class="upload-file">
                                                <input type="file" name="image" accept="image/*" class="upload-file__input">
                                                <div class="upload-file__img_drag upload-file__img max-h-200px overflow-hidden">
                                                    <img width="465" id="viewer" src="{{ $banner->imageFullPath }}" alt="{{ translate('banner image') }}"/>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2 text-center">
                                            {{translate('leave_empty_to_keep_current_image')}}
                                        </small>
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

let originalPrice = {{ $banner->calculateOriginalPrice() ?? 0 }};

$('.js-select2-custom').each(function () {
    $.HSCore.components.HSSelect2.init($(this));
});

// Branch toggle
$('input[name="is_global"]').on('change', function() {
    if ($(this).val() == '0') {
        $('#branch-selection').slideDown();
        $('#branch-select').prop('required', true);
    } else {
        $('#branch-selection').slideUp();
        $('#branch-select').prop('required', false);
    }
});

// Banner type change
$('#banner-type-select').change(function() {
    const type = $(this).val();
    $('.banner-type-section').hide();
    $('#pricing-section').hide();
    
    $('#single-product-select, #multiple-products-select, #category-select').prop('required', false);
    
    if (type === 'single_product') {
        $('#single-product-section').show();
        $('#single-product-select').prop('required', true);
        $('#pricing-section').show();
    } else if (type === 'multiple_products') {
        $('#multiple-products-section').show();
        $('#multiple-products-select').prop('required', true);
        $('#pricing-section').show();
    } else if (type === 'category') {
        $('#category-section').show();
        $('#category-select').prop('required', true);
    }
});

// Price calculations...
$('#single-product-select').change(function() {
    originalPrice = parseFloat($(this).find('option:selected').data('price')) || 0;
    updatePriceDisplay();
});

$('#multiple-products-select').change(function() {
    let total = 0;
    $(this).find('option:selected').each(function() {
        total += parseFloat($(this).data('price') || 0);
    });
    originalPrice = total;
    updatePriceDisplay();
});

$('#discount-type-select').change(function() {
    const type = $(this).val();
    $('#fixed-discount-section').toggle(type === 'fixed');
    $('#percentage-discount-section').toggle(type === 'percentage');
    calculateFinalPrice();
});

$('#discount-amount-input, #discount-percentage-input, #offer-price-input').on('input', calculateFinalPrice);

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
    
    let finalPrice = originalPrice;
    const offerPrice = parseFloat($('#offer-price-input').val()) || 0;

    if (offerPrice > 0) {
        finalPrice = offerPrice;
        $('#discount-amount-input').val('');
        $('#discount-percentage-input').val('');
    } else {
        const type = $('#discount-type-select').val();
        if (type === 'fixed') {
            const amount = parseFloat($('#discount-amount-input').val()) || 0;
            finalPrice = Math.max(0, originalPrice - amount);
        } else if (type === 'percentage') {
            const perc = parseFloat($('#discount-percentage-input').val()) || 0;
            finalPrice = originalPrice * (1 - perc/100);
        }
    }

    const savings = originalPrice - finalPrice;
    $('#final-price-value').text(finalPrice.toFixed(2));
    $('#savings-value').text(savings.toFixed(2));
    $('#final-price-display').show();
}

// Image preview
function readURL(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => $('#viewer').attr('src', e.target.result);
        reader.readAsDataURL(input.files[0]);
    }
}

$("input[name='image']").change(function() { readURL(this); });

// Initial state
$(document).ready(function() {
    updatePriceDisplay();
    $('#banner-type-select').trigger('change');
    $('input[name="is_global"]:checked').trigger('change');
});
</script>
@endpush