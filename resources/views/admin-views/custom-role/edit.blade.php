@extends('layouts.admin.app')

@section('title', translate('Edit Role'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/product.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('update_employee_role')}}
                </span>
            </h2>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form id="submit-create-role" action="{{route('admin.custom-role.update', [$role['id']])}}"
                            method="post">
                            @csrf
                            <div class="form-group">
                                <label for="name">{{translate('role_name')}}</label>
                                <input type="text" name="name" value="{{$role['name']}}" class="form-control" id="name"
                                    aria-describedby="emailHelp" placeholder="{{translate('Ex')}} : {{translate('Store')}}">
                            </div>

                            <div class="form-group">
                                <label>{{translate('branch')}}</label>
                                <select name="branch_id" id="choose_branch" class="form-control" required>
                                    <option value="0" {{ $role->all_branches ? 'selected' : '' }}>{{translate('All_Branches')}}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{$branch->id}}" {{$role->branch_id == $branch->id ? 'selected' : ''}}>
                                            {{$branch->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-5 d-flex flex-wrap align-items-center gap-3">
                                <h5 class="mb-0">{{translate('Module_Permission')}} : </h5>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="select-all-btn">
                                    <label class="form-check-label" for="select-all-btn">{{translate('Select_All')}}</label>
                                </div>
                            </div>
                            <div class="row">
                                @foreach(MANAGEMENT_SECTION as $section)
                                    <div class="col-xl-4 col-lg-4 col-sm-6">
                                        <div class="form-group form-check">
                                            <input type="checkbox" name="modules[]" value="{{$section}}"
                                                class="form-check-input select-all-associate"
                                                {{in_array($section, (array) json_decode($role['module_access'])) ? 'checked' : ''}}
                                                id="{{$section}}">
                                            <label class="form-check-label ml-3"
                                                for="{{$section}}">{{translate($section)}}</label>
                                        </div>
                                    </div>
                                @endforeach
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
    </div>
@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin/js/role.js')}}"></script>
    <script>
        "use strict";

        $('#submit-create-role').on('submit', function (e) {

            var fields = $("input[name='modules[]']").serializeArray();
            if (fields.length === 0) {
                toastr.warning('{{ translate('select_minimum_one_selection_box') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return false;
            } else {
                $('#submit-create-role').submit();
            }
        });

        $('#all_branches').on('change', function () {
            if ($(this).is(':checked')) {
                $('#choose_branch').val('').trigger('change');
                $('#choose_branch').prop('disabled', true);
                $('#choose_branch').prop('required', false);
            } else {
                $('#choose_branch').prop('disabled', false);
                $('#choose_branch').prop('required', true);
            }
        });
    </script>
@endpush