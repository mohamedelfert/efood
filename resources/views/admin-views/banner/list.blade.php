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
            <a href="{{route('admin.banner.add-new')}}" class="btn btn--primary">
                <i class="tio-add"></i> {{translate('add_new_banner')}}
            </a>
        </div>

        <div class="row g-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-top px-card pt-4">
                        <div class="row align-items-center gy-2">
                            <div class="col-sm-8 col-md-6 col-lg-6">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="input-group">
                                        <input type="search" name="search" value="{{ request('search') }}" 
                                               class="form-control" placeholder="{{translate('search_by_title')}}" autocomplete="off">
                                        <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
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
                                        <th>{{translate('branch')}}</th>
                                        <th>{{translate('products/category')}}</th>
                                        <th>{{translate('pricing')}}</th>
                                        <th>{{translate('dates')}}</th>
                                        <th>{{translate('status')}}</th>
                                        <th class="text-center">{{translate('action')}}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                @forelse($banners as $key => $banner)
                                    <tr>
                                        <td>{{ $banners->firstItem() + $key }}</td>
                                        <td>
                                            <img class="img-vertical-150 rounded" 
                                                 src="{{ $banner->imageFullPath }}" 
                                                 alt="{{ translate('banner image') }}"
                                                 onerror="this.src='{{ asset('public/assets/admin/img/placeholders/banner-placeholder.jpg') }}'">
                                        </td>
                                        <td>
                                            <div class="max-w-300 text-wrap font-weight-bold">
                                                {{ $banner->title }}
                                            </div>
                                        </td>
                                        <td>
                                            @switch($banner->banner_type)
                                                @case('single_product')
                                                    <span class="badge badge-soft-primary">{{translate('single_product')}}</span>
                                                    @break
                                                @case('multiple_products')
                                                    <span class="badge badge-soft-info">{{translate('multiple_products')}}</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-soft-success">{{translate('category')}}</span>
                                            @endswitch
                                        </td>

                                        <td>
                                            @if($banner->is_global)
                                                <span class="badge badge-soft-dark">{{translate('global')}}</span>
                                            @else
                                                @if($banner->branches->isNotEmpty())
                                                    @php
                                                        $branchNames = $banner->branches->pluck('name')->take(3);
                                                        $count = $banner->branches->count();
                                                    @endphp
                                                    
                                                    {{ $branchNames->implode('، ') }}
                                                    @if($count > 3)
                                                        <span class="badge badge-soft-info ms-1">+{{ $count - 3 }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @endif
                                        </td>

                                        <td>
                                            @if($banner->banner_type == 'category' && $banner->category)
                                                <small class="text-muted">{{ Str::limit($banner->category->name, 35) }}</small>
                                            @elseif($banner->banner_type == 'single_product' && $banner->product)
                                                <small class="text-muted">{{ Str::limit($banner->product->name, 35) }}</small>
                                            @elseif($banner->banner_type == 'multiple_products' && $banner->products->count())
                                                <small>{{ $banner->products->count() }} {{translate('products')}}</small>
                                                <button type="button" class="btn btn-sm btn-outline-info ml-2" 
                                                        data-toggle="modal" data-target="#productsModal{{ $banner->id }}">
                                                    <i class="tio-visible"></i> {{translate('view')}}
                                                </button>

                                                <!-- Products Modal -->
                                                <div class="modal fade" id="productsModal{{ $banner->id }}" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{translate('products_in_offer')}}</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">×</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body max-h-400 overflow-auto">
                                                                <ul class="list-group list-group-flush">
                                                                    @foreach($banner->products as $product)
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                            {{ Str::limit($product->name, 45) }}
                                                                            <span class="badge badge-primary badge-pill">
                                                                                ج.م {{ number_format($product->price, 2) }}
                                                                            </span>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <!-- Fixed currency symbol - No helper function needed -->
                                        <td>
                                            @php
                                                $currency = 'ج.م '; // ← Egyptian Pound - change if needed
                                                $original = $banner->calculateOriginalPrice();
                                                $final    = $banner->calculateFinalPrice();
                                                $save     = $banner->getDiscountAmount();
                                            @endphp

                                            @if($banner->banner_type != 'category' && $original > 0)
                                                <div class="small">
                                                    <strong>{{translate('original')}}:</strong> 
                                                    <span class="text-muted">{{ $currency }}{{ number_format($original, 2) }}</span>
                                                </div>
                                                <div class="small text-success">
                                                    <strong>{{translate('offer')}}:</strong> 
                                                    {{ $currency }}{{ number_format($final, 2) }}
                                                </div>
                                                <div class="small text-danger">
                                                    <strong>{{translate('save')}}:</strong> 
                                                    {{ $currency }}{{ number_format($save, 2) }}
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if($banner->start_date && $banner->end_date)
                                                <div class="small">
                                                    <strong>{{translate('from')}}:</strong> 
                                                    {{ $banner->start_date->format('Y-m-d') }}
                                                </div>
                                                <div class="small">
                                                    <strong>{{translate('to')}}:</strong> 
                                                    {{ $banner->end_date->format('Y-m-d') }}
                                                </div>
                                                @if($banner->isOfferActive())
                                                    <span class="badge badge-success mt-1">{{translate('active')}}</span>
                                                @else
                                                    <span class="badge badge-secondary mt-1">{{translate('expired')}}</span>
                                                @endif
                                            @else
                                                <span class="badge badge-soft-dark">{{translate('no_date_limit')}}</span>
                                            @endif
                                        </td>

                                        <td>
                                            <label class="switcher switcher-sm">
                                                <input type="checkbox" class="switcher_input status-change" 
                                                       id="status-{{ $banner->id }}"
                                                       {{ $banner->status ? 'checked' : '' }}
                                                       data-url="{{ route('admin.banner.status', [$banner->id, !$banner->status]) }}">
                                                <span class="switcher_control"></span>
                                            </label>
                                        </td>

                                        <td class="text-center">
                                            <div class="btn-group gap-2">
                                                <a href="{{ route('admin.banner.edit', $banner->id) }}" 
                                                   class="btn btn-outline-info btn-sm square-btn" 
                                                   title="{{translate('edit')}}">
                                                    <i class="tio-edit"></i>
                                                </a>

                                                <button type="button" class="btn btn-outline-danger btn-sm square-btn form-alert"
                                                        data-id="banner-delete-{{ $banner->id }}"
                                                        data-message="{{translate('want_to_delete_banner')}}"
                                                        data-method="delete"
                                                        data-action="{{ route('admin.banner.delete', $banner->id) }}">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </div>

                                            <form id="banner-delete-{{ $banner->id }}" 
                                                  action="{{ route('admin.banner.delete', $banner->id) }}" 
                                                  method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="tio-info-outlined tio-3x"></i><br><br>
                                                {{ translate('no_banner_found') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center justify-content-lg-end mt-4 px-3">
                            {{ $banners->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection