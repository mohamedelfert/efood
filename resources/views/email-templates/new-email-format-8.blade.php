<!DOCTYPE html>
<?php
$lang = \App\CentralLogics\Helpers::get_default_language();
?>
<html lang="{{ $lang }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Email_Template') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', 'Cairo', sans-serif;
            background-color: #f4f4f4;
            color: #5C4033;
        }

        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f4f4;
            padding: 40px 0;
        }

        .main {
            background-color: #FDF8F3;
            margin: 0 auto;
            width: 100%;
            max-width: 500px;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #E6D5C3;
        }

        .header-accent {
            height: 8px;
            background: linear-gradient(90deg, #5C4033, #A07855);
        }

        .content {
            padding: 40px;
            text-align: center;
        }

        .restaurant-logo {
            margin-bottom: 25px;
        }

        .mail-img-logo {
            max-width: 120px;
            height: auto;
        }

        .mail-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #5C4033;
        }

        .mail-body {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #5C4033;
        }

        .submit-btn {
            background-color: #5C4033;
            color: #ffffff;
            padding: 12px 30px;
            text-decoration: none;
            font-weight: 700;
            border-radius: 6px;
            display: inline-block;
            margin-bottom: 30px;
        }

        .footer {
            text-align: center;
            padding: 30px;
            border-top: 1px dashed #A07855;
            font-size: 13px;
            color: #8B735B;
        }

        .privacy-links {
            margin-bottom: 15px;
        }

        .privacy-links a {
            color: #5C4033;
            text-decoration: none;
            margin: 0 10px;
        }

        .copyright {
            color: #8B735B;
        }
    </style>
</head>

<body>
    <center class="wrapper">
        <table class="main">
            <tr>
                <td class="header-accent"></td>
            </tr>
            <tr>
                <td class="content">
                    <div class="restaurant-logo">
                        @php($logo = \App\Model\BusinessSetting::where(['key' => 'logo'])->first()->value)
                        <img class="mail-img-logo" src="{{ asset('storage/app/public/restaurant/' . $logo) }}"
                            onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'" alt="Logo">
                    </div>

                    <h1 class="mail-title">✧ {{ $title ?? translate('Notification') }} ✧</h1>

                    <div class="mail-body">
                        {!! $body ?? '' !!}
                    </div>

                    @if (isset($data) && $data->button_url)
                        <a href="{{ $data->button_url }}"
                            class="submit-btn">{{ $data->button_name ?? translate('Explore') }}</a>
                    @endif

                    <div style="margin-top: 30px; text-align: left; border-top: 1px solid #E6D5C3; padding-top: 20px;">
                        <span style="font-size: 13px; color: #8B735B;">{{ $footer_text ?? '' }}</span><br><br>
                        {{ translate('Thanks_&_Regards') }},<br>
                        <strong>{{ $company_name }}</strong>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="footer">
                    <div class="privacy-links">
                        @if(isset($data['privacy']) && $data['privacy'] == 1)
                            <a href="{{ route('privacy-policy') }}">{{ translate('Privacy_Policy')}}</a>
                        @endif
                        @if (isset($data['contact']) && $data['contact'] == 1)
                            <a href="{{ route('about-us') }}">{{ translate('About_Us')}}</a>
                        @endif
                    </div>
                    <div class="copyright">
                        {{ $copyright_text ?? '' }}
                    </div>
                </td>
            </tr>
        </table>
    </center>
</body>

</html>