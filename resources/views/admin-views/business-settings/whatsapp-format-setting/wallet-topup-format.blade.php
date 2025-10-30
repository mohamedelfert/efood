@extends('layouts.admin.app')

@section('title', translate('WhatsApp_Template'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="">
            <div class="d-flex flex-wrap justify-content-between align-items-center __gap-15px">
                <h1 class="page-header-title mr-3 mb-0">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/whatsapp-icon.png') }}" class="w--26" alt="">
                    </span>
                    <span class="ml-2">
                        {{ translate('WhatsApp_Templates') }}
                    </span>
                </h1>
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active">
                <!-- Status Toggle -->
                <div class="card mb-3">
                    @php($whatsapp_status = \App\Model\BusinessSetting::where('key','wallet_topup_whatsapp_status_user')->first())
                    @php($whatsapp_status = $whatsapp_status ? $whatsapp_status->value : '0')
                    <div class="card-body">
                        <div class="maintainance-mode-toggle-bar d-flex flex-wrap justify-content-between border rounded align-items-center p-2">
                            <h5 class="text-capitalize m-0 text--primary pl-2">
                                {{translate('Send_WhatsApp_On_Wallet_Top-up')}}
                                <span class="input-label-secondary" data-toggle="tooltip" data-placement="right" 
                                    data-original-title="{{ translate('Customers_will_receive_an_automated_WhatsApp_message_after_a_successful_wallet_top-up.')}}">
                                    <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                </span>
                            </h5>
                            <label class="toggle-switch toggle-switch-sm">
                                <input type="checkbox" class="status toggle-switch-input" 
                                    onclick="location.href='{{route('admin.business-settings.whatsapp.status',[$whatsapp_status == '1'?0:1])}}'" 
                                    id="whatsapp-status" {{$whatsapp_status == '1'?'checked':''}}>
                                <span class="toggle-switch-label text mb-0">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Template Form -->
                <form action="{{ route('admin.business-settings.whatsapp.update') }}" method="POST" enctype="multipart/form-data" id="whatsapp-form">
                    @csrf
                    <div class="card border-0">
                        <div class="card-body">
                            <div class="email-format-wrapper">
                                <!-- Left Content - Preview -->
                                <div class="left-content">
                                    <div class="d-inline-block mb-3">
                                        <h5>{{ translate('Select_Template') }}</h5>
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check" name="whatsapp_template" value="1" 
                                                id="template1" {{$selectedTemplate == 1 ? 'checked' : ''}}>
                                            <label class="btn btn-outline-primary" for="template1">{{ translate('Template_1') }}</label>

                                            <input type="radio" class="btn-check" name="whatsapp_template" value="2" 
                                                id="template2" {{$selectedTemplate == 2 ? 'checked' : ''}}>
                                            <label class="btn btn-outline-primary" for="template2">{{ translate('Template_2') }}</label>

                                            <input type="radio" class="btn-check" name="whatsapp_template" value="3" 
                                                id="template3" {{$selectedTemplate == 3 ? 'checked' : ''}}>
                                            <label class="btn btn-outline-primary" for="template3">{{ translate('Template_3') }}</label>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-body">
                                            @include('admin-views.business-settings.whatsapp-format-setting.templates.whatsapp-format-'.$selectedTemplate)
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Content - Form -->
                                <div class="right-content">
                                    <!-- Language Tabs -->
                                    <div class="d-flex flex-wrap justify-content-between __gap-15px mt-2 mb-5">
                                        @php($language = \App\Model\BusinessSetting::where('key','language')->first())
                                        @php($language = $language->value ?? null)
                                        @php($default_lang = str_replace('_', '-', app()->getLocale()))
                                        
                                        @if($language)
                                            <ul class="nav nav-tabs m-0 border-0">
                                                <li class="nav-item">
                                                    <a class="nav-link lang_link active" href="#" id="default-link">
                                                        {{translate('default')}}
                                                    </a>
                                                </li>
                                                @foreach (json_decode($language) as $lang)
                                                    <li class="nav-item">
                                                        <a class="nav-link lang_link" href="#" id="{{ $lang->code }}-link">
                                                            {{ \App\CentralLogics\Helpers::get_language_name($lang->code) . '(' . strtoupper($lang->code) . ')' }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#instructionsModal">
                                            <i class="tio-info-outlined"></i>
                                            {{translate('Read_Instructions')}}
                                        </button>
                                    </div>

                                    <!-- Logo Upload -->
                                    <div class="mb-3">
                                        <h5 class="card-title mb-3">
                                            {{translate('Logo')}}
                                            <span class="input-label-secondary" data-toggle="tooltip" 
                                                data-original-title="{{ translate('Logo_will_appear_in_PDF_receipt._Recommended_size:_200x200px')}}">
                                                <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" alt="">
                                            </span>
                                        </h5>
                                        <div class="d-flex align-items-center gap-3">
                                            <label class="custom-file flex-grow-1">
                                                <input type="file" name="logo" id="whatsapp-logo" class="custom-file-input" 
                                                    accept=".jpg, .png, .jpeg" onchange="previewLogo(this)">
                                                <span class="custom-file-label">{{ translate('Choose_File') }}</span>
                                            </label>
                                            @if($template && $template->logo)
                                                <button type="button" class="btn btn-danger btn-sm" onclick="removeLogo()">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            @endif
                                        </div>
                                        <div class="mt-2">
                                            @if($template && $template->logo)
                                                <img id="logoViewer" src="{{ asset('storage/whatsapp_template/'.$template->logo) }}" 
                                                    alt="" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                                            @else
                                                <img id="logoViewer" src="{{ asset('public/assets/admin/img/placeholder.png') }}" 
                                                    alt="" class="img-thumbnail" style="max-width: 150px; max-height: 150px; object-fit: cover;">
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Header Image -->
                                    <div class="mb-3">
                                        <h5 class="card-title mb-3">{{translate('Header_Image')}}</h5>
                                        <label class="custom-file">
                                            <input type="file" name="image" id="whatsapp-image" class="custom-file-input" 
                                                accept=".jpg, .png, .jpeg">
                                            <span class="custom-file-label">{{ translate('Choose_File') }}</span>
                                        </label>
                                        @if($template && $template->image)
                                            <div class="mt-2">
                                                <img id="imageViewer" src="{{ asset('storage/app/public/whatsapp_template/'.$template->image) }}" 
                                                    alt="" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        @else
                                            <div class="mt-2">
                                                <img id="imageViewer" src="{{ asset('public/assets/admin/img/placeholder.png') }}" 
                                                    alt="" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Header Content -->
                                    <div class="mb-3">
                                        <h5 class="card-title mb-3">
                                            <img src="{{asset('public/assets/admin/img/pointer.png')}}" class="mr-2" alt="">
                                            {{translate('Header_Content')}}
                                        </h5>

                                        @if ($language)
                                            <!-- Default Language Form -->
                                            <div class="__bg-F8F9FC-card default-form lang_form" id="default-form">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        {{translate('Main_Title')}}({{ translate('default') }})
                                                    </label>
                                                    <input type="text" maxlength="255" name="title[]" 
                                                        value="{{ $template?->getRawOriginal('title') }}" 
                                                        data-id="whatsapp-title" 
                                                        placeholder="{{ translate('Wallet_Topped_Up_Successfully') }}" 
                                                        class="form-control">
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label class="form-label">
                                                        {{ translate('Message_Body') }} ({{ translate('default') }})
                                                    </label>
                                                    <textarea name="body[]" data-id="whatsapp-body" 
                                                        class="form-control" rows="5" 
                                                        placeholder="{{ translate('Your_wallet_has_been_topped_up_successfully!') }}">{{ $template?->getRawOriginal('body') }}</textarea>
                                                </div>
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">

                                            <!-- Other Languages -->
                                            @foreach(json_decode($language) as $lang)
                                                <?php
                                                $lang_code = $lang->code;
                                                $translate = [];
                                                if ($template && count($template['translations'])) {
                                                    foreach ($template['translations'] as $t) {
                                                        if ($t->locale == $lang_code && $t->key == "title") {
                                                            $translate[$lang_code]['title'] = $t->value;
                                                        }
                                                        if ($t->locale == $lang_code && $t->key == "body") {
                                                            $translate[$lang_code]['body'] = $t->value;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <div class="__bg-F8F9FC-card d-none lang_form" id="{{$lang->code}}-form">
                                                    <div class="form-group">
                                                        <label class="form-label">
                                                            {{translate('Main_Title')}}({{strtoupper($lang->code)}})
                                                        </label>
                                                        <input type="text" maxlength="255" name="title[]" 
                                                            placeholder="{{ translate('Wallet_Topped_Up_Successfully') }}" 
                                                            class="form-control" value="{{$translate[$lang->code]['title']??''}}">
                                                    </div>
                                                    <div class="form-group mb-0">
                                                        <label class="form-label">
                                                            {{ translate('Message_Body') }}({{strtoupper($lang->code)}})
                                                        </label>
                                                        <textarea name="body[]" class="form-control" rows="5">{{$translate[$lang_code]['body']??''}}</textarea>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{$lang_code}}">
                                            @endforeach
                                        @else
                                            <div class="__bg-F8F9FC-card default-form">
                                                <div class="form-group">
                                                    <label class="form-label">{{translate('Main_Title')}}</label>
                                                    <input type="text" maxlength="255" name="title[]" 
                                                        placeholder="{{ translate('Wallet_Topped_Up_Successfully') }}" 
                                                        class="form-control">
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label class="form-label">{{ translate('Message_Body') }}</label>
                                                    <textarea name="body[]" class="form-control" rows="5"></textarea>
                                                </div>
                                                <small class="form-text text-muted">
                                                    <strong>{{translate('Available_Placeholders')}}:</strong><br>
                                                    {customer_name}, {amount}, {currency}, {new_balance}, {previous_balance}, {transaction_id}, {date}, {account_number}, {branch}
                                                </small>
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">
                                        @endif
                                    </div>

                                    <!-- Button Content -->
                                    <div class="mb-3">
                                        <h5 class="card-title mb-3">
                                            <img src="{{asset('public/assets/admin/img/pointer.png')}}" class="mr-2" alt="">
                                            {{translate('Button_Content')}}
                                        </h5>
                                        <div class="__bg-F8F9FC-card">
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    @if ($language)
                                                        <div class="form-group m-0 lang_form default-form">
                                                            <label class="form-label text-capitalize">
                                                                {{translate('Button_Name')}}({{ translate('default') }})
                                                            </label>
                                                            <input type="text" maxlength="15" data-id="whatsapp-button" 
                                                                name="button_name[]" placeholder="{{translate('View_Wallet')}}" 
                                                                class="form-control h--45px" 
                                                                value="{{ $template?->getRawOriginal('button_name') }}">
                                                        </div>
                                                        @foreach(json_decode($language) as $lang)
                                                            <?php
                                                            $lang_code = $lang->code;
                                                            if($template && count($template['translations'])){
                                                                $translate = [];
                                                                foreach($template['translations'] as $t)
                                                                {
                                                                    if($t->locale == $lang_code && $t->key=="button_name"){
                                                                        $translate[$lang_code]['button_name'] = $t->value;
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                            <div class="form-group m-0 d-none lang_form" id="{{$lang->code}}-form1">
                                                                <label class="form-label text-capitalize">
                                                                    {{translate('Button_Name')}}({{strtoupper($lang->code)}})
                                                                </label>
                                                                <input type="text" maxlength="15" name="button_name[]" 
                                                                    placeholder="{{translate('View_Wallet')}}" 
                                                                    class="form-control h--45px" 
                                                                    value="{{ $translate[$lang->code]['button_name']??'' }}">
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div class="form-group m-0">
                                                            <label class="form-label text-capitalize">
                                                                {{translate('Button_Name')}}
                                                            </label>
                                                            <input type="text" maxlength="15" 
                                                                placeholder="{{translate('View_Wallet')}}" 
                                                                class="form-control h--45px" name="button_name[]" value="">
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="form-group m-0">
                                                        <label class="form-label">{{translate('Redirect_Link')}}</label>
                                                        <input type="url" name="button_url" 
                                                            placeholder="{{ translate('https://example.com/wallet') }}" 
                                                            class="form-control" value="{{ $template['button_url']??'' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer Content -->
                                    <div class="mb-3">
                                        <h5 class="card-title mb-3">
                                            <img src="{{asset('public/assets/admin/img/pointer.png')}}" class="mr-2" alt="">
                                            {{translate('Footer_Content')}}
                                        </h5>
                                        <div class="__bg-F8F9FC-card">
                                            @if ($language)
                                                <div class="form-group lang_form default-form">
                                                    <label class="form-label">
                                                        {{translate('Footer_Text')}}({{ translate('default') }})
                                                    </label>
                                                    <input type="text" maxlength="500" data-id="whatsapp-footer" 
                                                        name="footer_text[]" 
                                                        placeholder="{{ translate('Thank_you_for_using_our_service') }}" 
                                                        class="form-control" 
                                                        value="{{ $template?->getRawOriginal('footer_text') }}">
                                                </div>
                                                @foreach(json_decode($language) as $lang)
                                                    <?php
                                                    $lang_code = $lang->code;
                                                    if($template && count($template['translations'])){
                                                        $translate = [];
                                                        foreach($template['translations'] as $t)
                                                        {
                                                            if($t->locale == $lang_code && $t->key=="footer_text"){
                                                                $translate[$lang_code]['footer_text'] = $t->value;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <div class="form-group d-none lang_form" id="{{$lang->code}}-form2">
                                                        <label class="form-label">
                                                            {{translate('Footer_Text')}}({{strtoupper($lang->code)}})
                                                        </label>
                                                        <input type="text" maxlength="500" name="footer_text[]" 
                                                            placeholder="{{ translate('Thank_you_for_using_our_service') }}" 
                                                            class="form-control" 
                                                            value="{{ $translate[$lang->code]['footer_text']??'' }}">
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="form-group">
                                                    <label class="form-label">{{translate('Footer_Text')}}</label>
                                                    <input type="text" maxlength="500" 
                                                        placeholder="{{ translate('Thank_you_for_using_our_service') }}" 
                                                        class="form-control" name="footer_text[]" value="">
                                                        </div>
                                            @endif

                                            <!-- Page Links -->
                                            <div class="form-group">
                                                <label class="form-label">{{translate('Page_Links')}}</label>
                                                <ul class="page-links-checkgrp">
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input privacy-check" 
                                                                onchange="checkWhatsAppElement('privacy-check')" 
                                                                type="checkbox" name="privacy" value="1" 
                                                                {{ (isset($template['privacy']) && $template['privacy'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('Privacy_Policy')}}</span>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input refund-check" 
                                                                onchange="checkWhatsAppElement('refund-check')" 
                                                                type="checkbox" name="refund" value="1" 
                                                                {{ (isset($template['refund']) && $template['refund'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('Refund_Policy')}}</span>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input cancelation-check" 
                                                                onchange="checkWhatsAppElement('cancelation-check')" 
                                                                type="checkbox" name="cancelation" value="1" 
                                                                {{ (isset($template['cancelation']) && $template['cancelation'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('Cancelation_Policy')}}</span>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input contact-check" 
                                                                onchange="checkWhatsAppElement('contact-check')" 
                                                                type="checkbox" name="contact" value="1" 
                                                                {{ (isset($template['contact']) && $template['contact'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('Contact_Us')}}</span>
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Social Media Links -->
                                            <div class="form-group">
                                                <label class="form-label">{{translate('Social_Media_Links')}}</label>
                                                <ul class="page-links-checkgrp">
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input facebook-check" 
                                                                type="checkbox" onchange="checkWhatsAppElement('facebook-check')" 
                                                                name="facebook" value="1" 
                                                                {{ (isset($template['facebook']) && $template['facebook'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('Facebook')}}</span>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input instagram-check" 
                                                                type="checkbox" onchange="checkWhatsAppElement('instagram-check')" 
                                                                name="instagram" value="1" 
                                                                {{ (isset($template['instagram']) && $template['instagram'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('Instagram')}}</span>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input twitter-check" 
                                                                type="checkbox" onchange="checkWhatsAppElement('twitter-check')" 
                                                                name="twitter" value="1" 
                                                                {{ (isset($template['twitter']) && $template['twitter'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('Twitter')}}</span>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input linkedin-check" 
                                                                type="checkbox" onchange="checkWhatsAppElement('linkedin-check')" 
                                                                name="linkedin" value="1" 
                                                                {{ (isset($template['linkedin']) && $template['linkedin'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('LinkedIn')}}</span>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="form-check form--check">
                                                            <input class="form-check-input pinterest-check" 
                                                                type="checkbox" onchange="checkWhatsAppElement('pinterest-check')" 
                                                                name="pinterest" value="1" 
                                                                {{ (isset($template['pinterest']) && $template['pinterest'] == 1)?'checked':'' }}>
                                                            <span class="form-check-label">{{translate('Pinterest')}}</span>
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>

                                            <!-- Copyright Text -->
                                            <div class="form-group mb-0">
                                                @if ($language)
                                                    <div class="form-group lang_form default-form">
                                                        <label class="form-label">
                                                            {{translate('Copyright_Content')}}({{ translate('default') }})
                                                        </label>
                                                        <input type="text" maxlength="50" data-id="whatsapp-copyright" 
                                                            name="copyright_text[]" 
                                                            placeholder="{{ translate('Copyright_2025._All_rights_reserved')}}" 
                                                            class="form-control" 
                                                            value="{{ $template?->getRawOriginal('copyright_text') }}">
                                                    </div>
                                                    @foreach(json_decode($language) as $lang)
                                                        <?php
                                                        $translate = [];
                                                        if($template && count($template['translations'])){
                                                            foreach($template['translations'] as $t)
                                                            {
                                                                if($t->locale == $lang->code && $t->key=="copyright_text"){
                                                                    $translate[$lang->code]['copyright_text'] = $t->value;
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <div class="form-group d-none lang_form" id="{{$lang->code}}-form3">
                                                            <label class="form-label">
                                                                {{translate('Copyright_Content')}}({{strtoupper($lang->code)}})
                                                            </label>
                                                            <input type="text" maxlength="50" name="copyright_text[]" 
                                                                placeholder="{{ translate('Copyright_2025._All_rights_reserved')}}" 
                                                                class="form-control" 
                                                                value="{{ $translate[$lang->code]['copyright_text']??'' }}">
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="form-group">
                                                        <label class="form-label">{{translate('Copyright_Content')}}</label>
                                                        <input type="text" maxlength="50" 
                                                            placeholder="{{ translate('Copyright_2025._All_rights_reserved')}}" 
                                                            class="form-control" name="copyright_text[]" value="">
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="btn--container justify-content-end mt-3">
                                        <button type="reset" id="reset_btn" class="btn btn-secondary">
                                            {{translate('Reset')}}
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            {{translate('Save')}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>{{translate('Validation_Error')}}!</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Instructions Modal -->
    <div class="modal fade" id="instructionsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="tio-info-outlined"></i>
                        {{translate('WhatsApp_Template_Instructions')}}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6>{{translate('Available_Placeholders')}}:</h6>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>{{translate('Placeholder')}}</th>
                                <th>{{translate('Description')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>{customer_name}</code></td>
                                <td>{{translate('Customer_full_name')}}</td>
                            </tr>
                            <tr>
                                <td><code>{amount}</code></td>
                                <td>{{translate('Transaction_amount')}}</td>
                            </tr>
                            <tr>
                                <td><code>{currency}</code></td>
                                <td>{{translate('Currency_code')}}</td>
                            </tr>
                            <tr>
                                <td><code>{new_balance}</code></td>
                                <td>{{translate('New_wallet_balance')}}</td>
                            </tr>
                            <tr>
                                <td><code>{previous_balance}</code></td>
                                <td>{{translate('Previous_wallet_balance')}}</td>
                            </tr>
                            <tr>
                                <td><code>{transaction_id}</code></td>
                                <td>{{translate('Unique_transaction_ID')}}</td>
                            </tr>
                            <tr>
                                <td><code>{date}</code></td>
                                <td>{{translate('Transaction_date')}}</td>
                            </tr>
                            <tr>
                                <td><code>{account_number}</code></td>
                                <td>{{translate('Customer_account_number')}}</td>
                            </tr>
                            <tr>
                                <td><code>{branch}</code></td>
                                <td>{{translate('Branch_name')}}</td>
                            </tr>
                        </tbody>
                    </table>

                    <h6 class="mt-4">{{translate('Tips')}}:</h6>
                    <ul>
                        <li>{{translate('Use_placeholders_in_curly_braces_like')}} <code>{customer_name}</code></li>
                        <li>{{translate('Logo_will_appear_in_PDF_receipt')}}</li>
                        <li>{{translate('Keep_message_body_concise_for_better_readability')}}</li>
                        <li>{{translate('Test_template_after_saving_changes')}}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
<script>
    $(document).ready(function () {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Language Tab Switching
        $(".lang_link").click(function(e){
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.substring(0, form_id.length - 5);

            $("#"+lang+"-form").removeClass('d-none');
            $("#"+lang+"-form1").removeClass('d-none');
            $("#"+lang+"-form2").removeClass('d-none');
            $("#"+lang+"-form3").removeClass('d-none');
            
            if(lang == 'default') {
                $(".default-form").removeClass('d-none');
            }
        });

        // Live Preview Updates with debounce
        function updatePreview(inputElement) {
            var dataId = $(inputElement).data('id');
            var value = $(inputElement).val();
            $('#'+dataId).text(value);
        }

        let debounceTimer;
        $('input[data-id], textarea[data-id]').on('keyup', function() {
            clearTimeout(debounceTimer);
            const element = this;
            debounceTimer = setTimeout(() => updatePreview(element), 300);
        });

        // Image Preview Functions
        window.previewLogo = function(input) {
            readURL(input, 'logoViewer');
        };

        window.previewImage = function(input) {
            readURL(input, 'imageViewer');
        };

        function readURL(input, viewer) {
            if (input.files && input.files[0]) {
                // Check file size (max 2MB)
                if (input.files[0].size > 2048000) {
                    toastr.error('{{ translate("File_size_must_be_less_than_2MB") }}');
                    input.value = '';
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(input.files[0].type)) {
                    toastr.error('{{ translate("Only_JPG_and_PNG_files_are_allowed") }}');
                    input.value = '';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#' + viewer).attr('src', e.target.result);
                    $('#' + viewer).show();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#whatsapp-logo").change(function() {
            previewLogo(this);
        });

        $("#whatsapp-image").change(function() {
            previewImage(this);
        });

        // Remove Logo
        window.removeLogo = function() {
            if(confirm('{{ translate("Are_you_sure_you_want_to_remove_the_logo") }}?')) {
                $('#logoViewer').attr('src', '{{ asset("public/assets/admin/img/placeholder.png") }}');
                $('#whatsapp-logo').val('');
                // Add hidden input to signal logo removal
                $('<input>').attr({
                    type: 'hidden',
                    name: 'remove_logo',
                    value: '1'
                }).appendTo('#whatsapp-form');
            }
        };

        // Template Selection with confirmation
        $('input[name="whatsapp_template"]').on('change', function() {
            const templateNumber = $(this).val();
            toastr.info('{{ translate("Template") }} ' + templateNumber + ' {{ translate("selected._Save_to_apply_changes") }}');
        });

        // Form validation before submit
        $('#whatsapp-form').on('submit', function(e) {
            const title = $('input[name="title[]"]').first().val();
            const body = $('textarea[name="body[]"]').first().val();

            if (!title || !body) {
                e.preventDefault();
                toastr.error('{{ translate("Please_fill_in_required_fields") }}');
                return false;
            }

            // Show loading
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm mr-2"></span>{{ translate("Saving") }}...'
            );
        });

        // Reset button functionality
        $('#reset_btn').on('click', function(e) {
            if(!confirm('{{ translate("Are_you_sure_you_want_to_reset_all_changes") }}?')) {
                e.preventDefault();
            }
        });
    });

    // Toggle checkbox elements in preview
    function checkWhatsAppElement(className) {
        var checkbox = $('.' + className);
        var element = $('.whatsapp-' + className.replace('-check', ''));
        
        if (checkbox.is(':checked')) {
            element.removeClass('d-none').fadeIn();
        } else {
            element.fadeOut().addClass('d-none');
        }
    }
</script>
@endpush