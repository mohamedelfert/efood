@extends('layouts.admin.app')

@section('title', translate('Add new sub category'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/category.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('add_New_Sub_Category')}}
                </span>
            </h2>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <div class="card card-body">
                    <form action="{{route('admin.category.store')}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @php($data = Helpers::get_business_settings('language'))
                        @php($defaultLang = Helpers::get_default_language())

                        @if($data && array_key_exists('code', $data[0]))
                        <ul class="nav nav-tabs w-fit-content mb-4">
                            @foreach($data as $lang)
                                <li class="nav-item">
                                    <a class="nav-link lang_link {{$lang['default'] == true? 'active':''}}" href="#" id="{{$lang['code']}}-link">{{\App\CentralLogics\Helpers::get_language_name($lang['code']).'('.strtoupper($lang['code']).')'}}</a>
                                </li>
                            @endforeach
                        </ul>
                        @endif

                        <div class="row">
                            <!-- Left Column: Name, Parent Category, and Branch Selection -->
                            <div class="col-lg-8">
                                <div class="row">
                                    <div class="col-12">
                                        @if($data && array_key_exists('code', $data[0]))
                                            @foreach($data as $lang)
                                                <div class="form-group {{$lang['default'] == false ? 'd-none':''}} lang_form" id="{{$lang['code']}}-form">
                                                    <label class="input-label">{{translate('sub_category')}} {{translate('name')}} ({{strtoupper($lang['code'])}})</label>
                                                    <input type="text" name="name[]" class="form-control" maxlength="255" placeholder="{{ translate('New Sub Category') }}" {{$lang['status'] == true ? 'required':''}}
                                                    @if($lang['status'] == true) oninvalid="document.getElementById('{{$lang['code']}}-link').click()" @endif>
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                            @endforeach
                                        @else
                                            <div class="form-group lang_form" id="{{$defaultLang}}-form">
                                                <label class="input-label" for="exampleFormControlInput1">{{translate('sub_category')}} {{translate('name')}}({{strtoupper($defaultLang)}})</label>
                                                <input type="text" name="name[]" class="form-control" placeholder="{{ translate('New Sub Category') }}" required>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{$defaultLang}}">
                                        @endif
                                        <input class="position-area" name="position" value="1">
                                        <input type="hidden" name="type" value="sub_category">
                                    </div>

                                    <div class="col-12 mb-4">
                                        <div class="form-group">
                                            <label for="exampleFormControlSelect1" class="input-label">
                                                {{translate('main_category')}}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select id="exampleFormControlSelect1" name="parent_id" class="form-control js-select2-custom" required>
                                                <option disabled selected>{{\App\CentralLogics\translate('Select a category')}}</option>
                                            @foreach(\App\Model\Category::where(['position'=>0])->get() as $category)
                                                    <option value="{{$category['id']}}">{{$category['name']}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Branch Selection -->
                                    <div class="col-12">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h4 class="mb-0 d-flex gap-2 align-items-center">
                                                    <i class="tio-label"></i>
                                                    {{translate('Branch')}}
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-group mb-0">
                                                    <label class="input-label">{{translate('select branch')}}</label>
                                                    <select name="branch_ids[]" class="form-control js-select2-custom" id="choose_branch" required multiple>
                                                        <option value="" disabled>---{{translate('select branch')}}---</option>
                                                        <option value="0">{{translate('All_Branches')}}</option>
                                                        @foreach($branches as $branch)
                                                            <option value="{{$branch['id']}}">{{$branch['name']}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Images -->
                            <div class="col-lg-4">
                                <!-- Sub-Category Image -->
                                <div class="mb-4">
                                    <div class="text-center mb-3">
                                        <img width="150" class="rounded border" id="viewer"
                                             src="{{ asset('public/assets/admin/img/400x400/img2.jpg') }}" alt="Sub-Category" />
                                    </div>
                                    <label>{{ translate('sub_category_Image') }} <small class="text-danger">(1:1)</small></label>
                                    <div class="custom-file">
                                        <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                               accept="image/*" required>
                                        <label class="custom-file-label" for="customFileEg1">{{ translate('choose file') }}</label>
                                    </div>
                                </div>

                                <!-- Banner Image -->
                                <div>
                                    <div class="text-center mb-3">
                                        <img width="100%" class="rounded border" id="viewer2"
                                             src="{{ asset('public/assets/admin/img/900x400/img1.jpg') }}" alt="Banner" />
                                    </div>
                                    <label>{{ translate('banner image') }} <small class="text-danger">(8:1)</small></label>
                                    <div class="custom-file">
                                        <input type="file" name="banner_image" id="customFileEg2" class="custom-file-input"
                                               accept="image/*">
                                        <label class="custom-file-label" for="customFileEg2">{{ translate('choose file') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4">
                            <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                            <button type="submit" class="btn btn-primary">{{translate('submit')}}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-top px-card pt-4">
                        <div class="row justify-content-between align-items-center gy-2">
                            <div class="col-sm-4 col-md-6 col-lg-8">
                                <h5 class="d-flex mb-0 gap-2 align-items-center">
                                    {{translate('Sub_Category_Table')}}
                                    <span class="badge badge-soft-dark rounded-50 fz-12">{{ $categories->total() }}</span>
                                </h5>
                            </div>
                            <div class="col-sm-8 col-md-6 col-lg-4">
                                <form action="{{url()->current()}}" method="GET">
                                    <div class="input-group">
                                        <input id="datatableSearch_" type="search" name="search" class="form-control" placeholder="{{translate('Search by sub category name')}}" aria-label="Search" value="{{$search}}" required autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">{{ translate('Search') }}</button>
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
                                    <th>{{translate('SL')}}</th>
                                    <th>{{translate('image')}}</th>
                                    <th>{{translate('main_Category')}}</th>
                                    <th>{{translate('sub_Category')}}</th>
                                    <th>{{translate('status')}}</th>
                                    <th>{{translate('branch')}}</th>
                                    <th class="text-center">{{translate('action')}}</th>
                                </tr>
                                </thead>
                                <tbody id="set-rows">
                                @foreach($categories as $key=>$category)
                                    <tr>
                                        <td>{{$categories->firstitem()+$key}}</td>
                                        <td>
                                            <div>
                                                <img width="50" class="avatar-img rounded" src="{{$category->imageFullPath}}" alt="">
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                {{$category->parent['name']}}
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                {{$category['name']}}
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <label class="switcher">
                                                    <input class="switcher_input status-change" type="checkbox" {{$category['status']==1? 'checked' : ''}} id="{{$category['id']}}"
                                                           data-url="{{route('admin.category.status',[$category['id'],1])}}"
                                                    >
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            @if($category['all_branches'])
                                                <span class="badge badge-soft-primary">{{translate('All')}}</span>
                                            @else
                                                @foreach(json_decode($category['branch_ids'], true) as $branchId)
                                                    <span class="badge badge-soft-info">{{ $branches->find($branchId)->name ?? '' }}</span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a class="btn btn-outline-info btn-sm edit square-btn"
                                                    href="{{route('admin.category.edit',[$category['id']])}}">
                                                    <i class="tio-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-danger btn-sm delete square-btn form-alert"
                                                    data-id="category-{{$category['id']}}" data-message="{{translate("Want to delete this sub category ?")}}">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </div>
                                            <form action="{{route('admin.category.delete',[$category['id']])}}"
                                                method="post" id="category-{{$category['id']}}">
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
                                {!! $categories->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script>
        "use strict";
        function readURL(input, viewerId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#' + viewerId).attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this, 'viewer');
        });

        $("#customFileEg2").change(function () {
            readURL(this, 'viewer2');
        });

        $(document).on('ready', function () {
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $(".lang_link").click(function(e){
            e.preventDefault();

            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.split("-")[0];

            $("#"+lang+"-form").removeClass('d-none');

            if(lang == '{{$defaultLang}}')
            {
                $(".from_part_2").removeClass('d-none');
            }
            else
            {
                $(".from_part_2").addClass('d-none');
            }
        });

        // Initialize Select2 for branch selection
        $("#choose_branch").select2({
            placeholder: "Select Branch",
            allowClear: true
        });

        $('#choose_branch').on('change', function() {
            let selected = $(this).val();
            if (selected && selected.includes('0')) {
                $(this).val(['0']).trigger('change.select2');
            }
        });
    </script>
@endpush