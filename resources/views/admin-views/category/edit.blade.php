@extends('layouts.admin.app')

@section('title', $category->parent_id == 0 ? translate('Update Category') : translate('Update Sub Category')))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/category.png')}}" alt="">
                <span class="page-header-title">
                    {{ $category->parent_id == 0 ? translate('Update Category') : translate('Update Sub Category') }}
                </span>
            </h2>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card card-body">
                    <form action="{{route('admin.category.update',[$category['id']])}}" method="post"
                        enctype="multipart/form-data">
                        @csrf

                        @php($data = Helpers::get_business_settings('language'))
                        @php($defaultLang = Helpers::get_default_language())

                        @if($data && array_key_exists('code', $data[0]))
                            <ul class="nav nav-tabs w-fit-content mb-4">
                                @foreach($data as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link lang_link {{$lang['default'] == true? 'active':''}}" href="#"
                                        id="{{$lang['code']}}-link">{{\App\CentralLogics\Helpers::get_language_name($lang['code']).'('.strtoupper($lang['code']).')'}}</a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="row align-items-end">
                                <div class="col-12">
                                    @foreach($data as $lang)
                                        <?php
                                        if (count($category['translations'])) {
                                            $translate = [];
                                            foreach ($category['translations'] as $t) {
                                                if ($t->locale == $lang['code'] && $t->key == "name") {
                                                    $translate[$lang['code']]['name'] = $t->value;
                                                }
                                            }
                                        }
                                        ?>
                                        <div class="form-group {{$lang['default'] == false ? 'd-none':''}} lang_form"
                                            id="{{$lang['code']}}-form">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{translate('name')}}
                                                ({{strtoupper($lang['code'])}})</label>
                                            <input type="text" name="name[]" maxlength="255"
                                                value="{{$lang['code'] == 'en' ? $category['name'] : ($translate[$lang['code']]['name']??'')}}"
                                                class="form-control" @if($lang['status'] == true) oninvalid="document.getElementById('{{$lang['code']}}-link').click()" @endif
                                                placeholder="{{ translate('New Category') }}" {{$lang['status'] == true ? 'required':''}}>
                                        </div>
                                        <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                    @endforeach
                                    @else
                                    <div class="row">
                                        <div class="col-6 mb-4">
                                            <div class="form-group lang_form" id="{{$defaultLang}}-form">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{translate('name')}}
                                                    ({{strtoupper($defaultLang)}})</label>
                                                <input type="text" name="name[]" value="{{$category['name']}}"
                                                    class="form-control" oninvalid="document.getElementById('en-link').click()"
                                                    placeholder="{{ translate('New Category') }}" required>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{$defaultLang}}">
                                            @endif
                                            <input class="position-area" name="position" value="0">
                                        </div>

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
                                                                <select name="branch_ids[]" id="choose_branch" class="form-control js-select2-custom" multiple>
                                                                    <option value="" disabled>---{{translate('select branch')}}---</option>
                                                                    <option value="0" {{ $category->all_branches ? 'selected' : '' }}>{{translate('All_Branches')}}</option>
                                                                    @foreach(\App\Model\Branch::active()->get() as $branch)
                                                                        <option value="{{ $branch->id }}"
                                                                            {{ in_array($branch->id, (is_string($category->branch_ids) ? json_decode($category->branch_ids, true) : $category->branch_ids) ?? []) ? 'selected' : '' }}>
                                                                            {{ $branch->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                            <div class="col-md-6 mb-4">
                                                <div class="from_part_2 mt-2">
                                                    <div class="form-group">
                                                        <div class="text-center">
                                                            <img width="105" class="rounded-10 border" id="viewer"
                                                                src="{{$category->imageFullPath}}" alt="image" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="from_part_2">
                                                    <label>{{ translate('category_Image') }}</label>
                                                    <small class="text-danger">* ( {{ translate('ratio') }} 1:1 )</small>
                                                    <div class="custom-file">
                                                        <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*"
                                                            oninvalid="document.getElementById('en-link').click()">
                                                        <label class="custom-file-label" for="customFileEg1">{{ translate('choose file') }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div class="from_part_2">
                                                    <div class="form-group">
                                                        <div class="text-center max-h-200px overflow-hidden">
                                                            <img width="500" class="rounded-10 border" id="viewer2"
                                                                src="{{$category->bannerImageFullPath}}" alt="image" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="from_part_2">
                                                    <label>{{ translate('banner image') }}</label>
                                                    <small class="text-danger">* ( {{ translate('ratio') }} 8:1 )</small>
                                                    <div class="custom-file">
                                                        <input type="file" name="banner_image" id="customFileEg2" class="custom-file-input"
                                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*"
                                                            oninvalid="document.getElementById('en-link').click()">
                                                        <label class="custom-file-label" for="customFileEg2">{{ translate('choose file') }}</label>
                                                    </div>
                                                </div>
                                            </div>
                                    </div>
                                    <div class="d-flex justify-content-end gap-3">
                                        <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                        <button type="submit" class="btn btn-primary">{{translate('update')}}</button>
                                    </div>
                    </form>
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

        $(document).on('ready', function () {
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });

        $('#choose_branch').on('change', function() {
            let selected = $(this).val();
            if (selected && selected.includes('0')) {
                $(this).val(['0']).trigger('change.select2');
            }
        });
    </script>
@endpush
