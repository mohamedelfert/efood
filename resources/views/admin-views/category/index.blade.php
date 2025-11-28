@extends('layouts.admin.app')

@section('title', translate('Add new category'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <h2 class="h1 mb-0 d-flex align-items-center gap-2">
            <img width="20" src="{{asset('public/assets/admin/img/icons/category.png')}}" alt="">
            {{translate('add_New_Category')}}
        </h2>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.category.store')}}" method="post" enctype="multipart/form-data">
                        @csrf

                        @php($languages = Helpers::get_business_settings('language') ?? [])
                        @php($defaultLang = Helpers::get_default_language())

                        <!-- Language Tabs -->
                        @if($languages && count($languages))
                            <ul class="nav nav-tabs mb-4">
                                @foreach($languages as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link lang_link {{ $lang['default'] ? 'active' : '' }}"
                                           href="#" id="{{ $lang['code'] }}-link">
                                            {{ \App\CentralLogics\Helpers::get_language_name($lang['code']) }}
                                            ({{ strtoupper($lang['code']) }})
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="row">
                            <!-- Left: Name + Branch Selection -->
                            <div class="col-lg-8">
                                <!-- Multi-language Name Inputs -->
                                @foreach($languages ?? [['code' => $defaultLang, 'default' => true]] as $lang)
                                    <div class="form-group lang_form {{ !$lang['default'] ? 'd-none' : '' }}"
                                         id="{{ $lang['code'] }}-form">
                                        <label class="input-label">
                                            {{ translate('name') }} ({{ strtoupper($lang['code']) }})
                                            @if($lang['default'] ?? true) <span class="text-danger">*</span> @endif
                                        </label>
                                        <input type="text" name="name[]" class="form-control"
                                               placeholder="{{ translate('New Category') }}"
                                               {{ ($lang['default'] ?? true) ? 'required' : '' }}>
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{ $lang['code'] ?? $defaultLang }}">
                                @endforeach

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
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="">
                                                        <label class="input-label">{{translate('select branch')}}</label>
                                                        <select name="branch_ids[]" class="form-control js-select2-custom" id="choose_branch" required multiple>
                                                            <option value="" disabled>---{{translate('select branch')}}---</option>
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
                            </div>

                            <!-- Right: Images -->
                            <div class="col-lg-4">
                                <!-- Category Image -->
                                <div class="mb-4">
                                    <div class="text-center mb-3">
                                        <img id="viewer" width="150" class="rounded border"
                                             src="{{ asset('public/assets/admin/img/400x400/img2.jpg') }}" alt="Category">
                                    </div>
                                    <label>{{ translate('category_Image') }} <small class="text-danger">(1:1)</small></label>
                                    <div class="custom-file">
                                        <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                               accept="image/*" required>
                                        <label class="custom-file-label" for="customFileEg1">{{ translate('choose file') }}</label>
                                    </div>
                                </div>

                                <!-- Banner Image -->
                                <div>
                                    <div class="text-center mb-3">
                                        <img id="viewer2" width="100%" class="rounded border"
                                             src="{{ asset('public/assets/admin/img/900x400/img1.jpg') }}" alt="Banner">
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
                            <button type="reset" class="btn btn-secondary">{{ translate('reset') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 mt-4">
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-top px-card pt-4">
                        <div class="row justify-content-between align-items-center gy-2">
                            <div class="col-sm-4 col-md-6 col-lg-8">
                                <h5 class="d-flex gap-1 mb-0">
                                    {{translate('Category_Table')}}
                                    <span class="badge badge-soft-dark rounded-50 fz-12">{{ $categories->total() }}</span>
                                </h5>
                            </div>
                            <div class="col-sm-8 col-md-6 col-lg-4">
                                <form action="{{url()->current()}}" method="GET">
                                    <div class="input-group">
                                        <input id="datatableSearch_" type="search" name="search"
                                            class="form-control"
                                            placeholder="{{translate('Search by category name')}}" aria-label="{{translate('Search')}}"
                                            value="{{$search}}" required autocomplete="off">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">{{translate('Search')}}</button>
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
                                        <th>{{translate('Category_Image')}}</th>
                                        <th>{{translate('name')}}</th>
                                        <th>{{translate('status')}}</th>
                                        <th>{{translate('priority')}}</th>
                                        <th class="text-center">{{translate('action')}}</th>
                                    </tr>
                                </thead>

                                <tbody>
                                @foreach($categories as $key=>$category)
                                    <tr>
                                        <td>{{$categories->firstitem()+$key}}</td>
                                        <td>
                                            <div>
                                                <img width="50" class="avatar-img rounded" src="{{$category->imageFullPath}}"  alt="">
                                            </div>
                                        </td>
                                        <td><div class="text-capitalize">{{$category['name']}}</div></td>
                                        <td>
                                            <div class="">
                                                <label class="switcher">
                                                    <input class="switcher_input status-change" type="checkbox" {{$category['status']==1? 'checked' : ''}} id="{{$category['id']}}"
                                                           data-url="{{route('admin.category.status',[$category['id'],1])}}"
                                                    >
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="">
                                                <select name="priority" class="custom-select redirect-url-value"
                                                        data-url="{{ route('admin.category.priority', ['id' => $category['id'], 'priority' => '']) }}">
                                                    @for($i = 1; $i <= 10; $i++)
                                                        <option value="{{ $i }}" {{ $category->priority == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                    @endfor
                                                </select>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a class="btn btn-outline-info btn-sm edit square-btn"
                                                href="{{route('admin.category.edit',[$category['id']])}}">
                                                    <i class="tio-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete square-btn form-alert"
                                                    data-id="category-{{$category['id']}}" data-message="{{translate("Want to delete this")}}">
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
</div>
@endsection

@push('script_2')
{{--    <script src="{{asset('public/assets/admin/js/read-url.js')}}"></script>--}}
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

       function change_priority(id, priority, message) {
           Swal.fire({
               title: '{{translate("Are you sure?")}}',
               text: message,
               type: 'warning',
               showCancelButton: true,
               cancelButtonColor: 'default',
               confirmButtonColor: '#FC6A57',
               cancelButtonText: '{{translate("No")}}',
               confirmButtonText: '{{translate("Yes")}}',
               reverseButtons: true
           }).then((result) => {
               if (result.value) {
                   const csrfToken = $('meta[name="csrf-token"]').attr('content');

                   const formData = new FormData();
                   formData.append('_token', csrfToken);
                   formData.append('id', id);
                   formData.append('priority', priority);

                   $.ajax({
                       url: "{{ route('admin.category.priority') }}",
                       method: "POST",
                       data: formData,
                       processData: false,
                       contentType: false,
                       success: function(response) {
                           toastr.success("{{translate('Priority changed successfully')}}");
                           setTimeout(function() {
                               location.reload();
                           }, 2000);
                       },
                       error: function(xhr) {
                           toastr.error("{{translate('Priority changed failed')}}");
                       }
                   });
               }
           })
       }
    </script>

    <script>
        $("#choose_branch").select2({
            placeholder: "Select Branch",
            allowClear: true
        });
    </script>

@endpush
