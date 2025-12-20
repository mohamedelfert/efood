@extends('layouts.admin.app')

@section('title', translate('Map API Settings'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/third-party.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('third_party')}}
                </span>
            </h2>
        </div>

        @include('admin-views.business-settings.partials._3rdparty-inline-menu')

        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{env('APP_MODE')!='demo'?route('admin.business-settings.web-app.third-party.map_api_settings'):'javascript:'}}" method="post"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                @php($apiKey=\App\Model\BusinessSetting::where('key','map_api_key')->first()?->value )
                                <div class="form-group col-md-12">
                                    <label class="form-label">{{translate('Google Maps API Key')}}</label>
                                    <textarea name="map_api_key" class="form-control" rows="3" placeholder="Enter your Google Maps API Key">{{env('APP_MODE')!='demo'?$apiKey:''}}</textarea>
                                    <small class="form-text text-muted">
                                        {{translate('This API key will be used for all Google Maps services (Places, Distance Matrix, Geocoding)')}}
                                    </small>
                                </div>
                            </div>
                            <div class="btn--container mt-3">
                                <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                <button type="{{env('APP_MODE')!='demo'?'submit':'button'}}"
                                        class="btn btn-primary call-demo">{{translate('submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection