@php($currency=\App\Model\BusinessSetting::where(['key'=>'currency'])->first()->value)

    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>
        @yield('title')
    </title>
    <!-- SEO Meta Tags-->
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <!-- Viewport-->
    {{--<meta name="_token" content="{{csrf_token()}}">--}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon and Touch Icons-->
    <link rel="shortcut icon" href="favicon.ico">
    <!-- Font -->
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">
    <script
        src="{{asset('public/assets/admin')}}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js"></script>
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">

    {{--stripe--}}
    <script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>
    <script src="https://js.stripe.com/v3/"></script>
    {{--stripe--}}

    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/bootstrap.css">

</head>
<!-- Body-->
<body class="toolbar-enabled">
{{--loader--}}
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="loading" style="display: none;">
                <div style="position: fixed;z-index: 9999; left: 40%;top: 37% ;width: 100%">
                    <img width="200" src="{{asset('public/assets/admin/img/loader.gif')}}">
                </div>
            </div>
        </div>
    </div>
</div>
{{--loader--}}
<!-- Page Content-->
<div class="container pb-5 mb-2 mb-md-4" style="display: none">
    <div class="row">
        <div class="col-md-12 mb-5 pt-5">
            <center class="">
                <h1>{{ translate('Payment method') }}</h1>
            </center>
        </div>
        <section class="col-lg-12">
            <div class="mt-3">
                <div class="row">
                    @php($order_amount = session('order_amount'))
                    @php($customer = \App\User::find(session('customer_id')))
                    @php($callback = session('callback'))

                    @php($config=\App\CentralLogics\Helpers::get_business_settings('paymob'))
                    @if(isset($config) && $config['status'])
                        <div class="col-md-6 mb-4" style="cursor: pointer">
                            <div class="card">
                                <div class="card-body" style="height: 70px">
                                    <form class="needs-validation" method="POST" id="payment-form-paymob"

                                          action="{!! route('paymob-credit',['order_amount'=>$order_amount,'customer_id'=>$customer['id'],'callback'=>$callback]) !!}">
                                        {{ csrf_field() }}
                                        <button class="btn btn-block click-if-alone" id="paymob-button">

                                            <img width="100" src="{{asset('public/assets/admin/img/paymob.png')}}"/>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                     @php($config=\App\CentralLogics\Helpers::get_business_settings('qib'))
                    @if(isset($config) && $config['status'])
                        <div class="col-md-6 mb-4" style="cursor: pointer">
                            <div class="card">
                                <div class="card-body" style="height: 70px">
                                    <form class="needs-validation" method="POST" id="payment-form-qib"

                                          action="{!! route('qib-credit',['order_amount'=>$order_amount,'customer_id'=>$customer['id'],'callback'=>$callback]) !!}">
                                        {{ csrf_field() }}
                                        <button class="btn btn-block click-if-alone" id="qib-button">

                                            <img width="100" src="{{asset('public/assets/admin/img/qib.png')}}"/>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    @php($config=\App\CentralLogics\Helpers::get_business_settings('internal_point'))
                    @if(isset($config) && $config['status'])
                        <div class="col-md-6 mb-4" style="cursor: pointer">
                            <div class="card">
                                <div class="card-body" style="height: 70px">
                                    <button class="btn btn-block" type="button" data-toggle="modal"
                                            data-target="#exampleModal">
                                        <i class="czi-card"></i> {{ translate('Wallet Point') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog"
                             aria-labelledby="exampleModalLabel"
                             aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h3 class="modal-title" id="exampleModalLabel">{{ translate('Payment by Wallet Point') }}</h3>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <hr>
                                        @php($value=\App\Model\BusinessSetting::where(['key'=>'point_per_currency'])->first()->value)

                                        {{--                                        @php($order=\App\Model\Order::find(session('order_id')))--}}
                                        @php($point = $customer['point'])
                                        <span>{{ translate('Order Amount') }} : {{ \App\CentralLogics\Helpers::set_symbol($order_amount) }}</span><br>
                                        <span>{{ translate('Order Amount in Wallet Point') }} : {{$value*$order_amount}} {{ translate('Points') }}</span><br>
                                        <span>{{ translate('Your Available Points') }} : {{$point}} {{ translate('Points') }}</span><br>
                                        <hr>
                                        <center>
                                            @if(($value*$order_amount)<=$point)
                                                <label class="badge badge-soft-success">{{ translate('You have sufficient balance to proceed!') }}</label>
                                            @else
                                                <label class="badge badge-soft-danger">{{ translate('Your balance is insufficient!') }}</label>
                                            @endif
                                        </center>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-dismiss="modal">{{ translate('Close') }}
                                        </button>
                                        @if(($value*$order_amount)<=$point)
                                            <form action="{!! route('internal-point-pay',['order_amount'=>$order_amount,'customer_id'=>$customer['id'],'callback'=>$callback]) !!}" method="POST">
                                                @csrf
                                                <input name="order_id" value="" style="display: none">
                                                <button type="submit" class="btn btn-primary click-if-alone" id="internal-point-pay-button">{{ translate('Proceed') }}</button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-primary">{{ translate('Sorry! Next time.') }}</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>

<!-- JS Front -->
<script src="{{asset('public/assets/admin')}}/js/jquery.js"></script>
<script src="{{asset('public/assets/admin')}}/js/bootstrap.js"></script>
<script src="{{asset('public/assets/admin')}}/js/sweet_alert.js"></script>
<script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
{!! Toastr::message() !!}

<script>
    $(document).ready(function (){
        let payment_method = "{{$payment_method}}"

       if (payment_method === 'qib') {
            $('#qib-button').click();
        } else if (payment_method === 'paymob') {
            $('#paymob-button').click();
        } else if (payment_method === 'digital_payment') {
            $('#internal-point-pay-button').click();
        }
    });
</script>

<script>
    function click_if_alone() {
        let total = $('.click-if-alone').length;
        if (Number.parseInt(total) < 2) {
            $('.click-if-alone').click();
        }
    }
    @if(!$errors->any())
    click_if_alone();
    @endif
</script>


</body>
</html>
