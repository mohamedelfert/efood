@extends('layouts.admin.app')

@section('title', translate('banner_list'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/banner.png')}}" alt="">
                <span class="page-header-title">{{translate('banner_list')}}</span>
                <span class="badge badge-soft-dark rounded-50 fz-12">{{ $banners->total() }}</span>
            </h2>
            <a href="{{route('admin.banner.add-new')}}" class="btn btn-primary">
                <i class="tio-add"></i> {{translate('add_new_banner')}}
            </a>
        </div>

        <div class="row g-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-top px-card pt-4">
                        <div class="row align-items-center gy-2">
                            <div class="col-sm-8 col-md-6 col-lg-8">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group">
                                        <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="{{translate('search_by_title')}}" autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">{{translate('search')}}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="py-4">
                        <div class="table-responsive datatable-custom">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                <tr>
                                    <th>{{translate('sl')}}</th>
                                    <th>{{translate('banner_image')}}</th>
                                    <th>{{translate('title')}}</th>
                                    <th>{{translate('type')}}</th>
                                    <th>{{translate('products/category')}}</th>
                                    <th>{{translate('pricing')}}</th>
                                    <th>{{translate('dates')}}</th>
                                    <th>{{translate('status')}}</th>
                                    <th class="text-center">{{translate('action')}}</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($banners as $key=>$banner)
                                    <tr>
                                        <td>{{$banners->firstitem()+$key}}</td>
                                        <td>
                                            <img class="img-vertical-150" src="{{$banner->imageFullPath}}" alt="{{ translate('banner image') }}">
                                        </td>
                                        <td>
                                            <div class="max-w300 text-wrap">{{$banner['title']}}</div>
                                        </td>
                                        <td>
                                            @if($banner->banner_type == 'single_product')
                                                <span class="badge badge-soft-primary">{{translate('single_product')}}</span>
                                            @elseif($banner->banner_type == 'multiple_products')
                                                <span class="badge badge-soft-info">{{translate('multiple_products')}}</span>
                                            @else
                                                <span class="badge badge-soft-success">{{translate('category')}}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($banner->banner_type == 'category' && $banner->category)
                                                <small>{{substr($banner->category->name, 0, 30)}}</small>
                                            @elseif($banner->banner_type == 'single_product' && $banner->product)
                                                <small>{{substr($banner->product->name, 0, 30)}}</small>
                                            @elseif($banner->banner_type == 'multiple_products')
                                                <small>{{$banner->products->count()}} {{translate('products')}}</small>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                    data-toggle="modal" 
                                                    data-target="#productsModal{{$banner->id}}">
                                                    <i class="tio-visible"></i> {{translate('view')}}
                                                </button>
                                                
                                                <!-- Products Modal -->
                                                <div class="modal fade" id="productsModal{{$banner->id}}" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{translate('products_in_offer')}}</h5>
                                                                <button type="button" class="close" data-dismiss="modal">
                                                                    <span>&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <ul class="list-group">
                                                                    @foreach($banner->products as $product)
                                                                        <li class="list-group-item">
                                                                            {{$product->name}} 
                                                                            <span class="badge badge-primary float-right">{{$product->price}}</span>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $originalPrice = $banner->calculateOriginalPrice();
                                                $finalPrice = $banner->calculateFinalPrice();
                                                $savings = $banner->getDiscountAmount();
                                            @endphp
                                            
                                            @if($banner->banner_type != 'category' && $originalPrice > 0)
                                                <small class="d-block">
                                                    <strong>{{translate('original')}}:</strong> {{$originalPrice}}
                                                </small>
                                                <small class="d-block text-success">
                                                    <strong>{{translate('offer')}}:</strong> {{$finalPrice}}
                                                </small>
                                                <small class="d-block text-danger">
                                                    <strong>{{translate('save')}}:</strong> {{$savings}}
                                                </small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($banner->start_date && $banner->end_date)
                                                <small class="d-block">{{translate('from')}}: {{$banner->start_date->format('Y-m-d')}}</small>
                                                <small class="d-block">{{translate('to')}}: {{$banner->end_date->format('Y-m-d')}}</small>
                                                @if($banner->isOfferActive())
                                                    <span class="badge badge-success">{{translate('active')}}</span>
                                                @else
                                                    <span class="badge badge-secondary">{{translate('expired')}}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">{{translate('no_date_limit')}}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <label class="switcher">
                                                <input class="switcher_input status-change" type="checkbox" 
                                                    {{$banner['status']==1 ? 'checked' : ''}} 
                                                    id="{{$banner['id']}}"
                                                    data-url="{{route('admin.banner.status',[$banner['id'],0])}}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a class="btn btn-outline-info btn-sm edit square-btn"
                                                    href="{{route('admin.banner.edit',[$banner['id']])}}">
                                                    <i class="tio-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete square-btn form-alert" 
                                                    data-id="banner-{{$banner['id']}}" 
                                                    data-message="{{translate('want_to_delete_banner')}}">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </div>
                                            <form action="{{route('admin.banner.delete',[$banner['id']])}}" 
                                                method="post" id="banner-{{$banner['id']}}">
                                                @csrf @method('delete')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="table-responsive mt-4 px-3">
                            <div class="d-flex justify-content-lg-end">
                                {!! $banners->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection