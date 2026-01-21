@extends('layouts.admin.app')

@section('title', translate('Print Voucher'))

@push('css_or_js')
    <style>
        @media print {
            .non-printable {
                display: none;
            }

            .printable {
                display: block;
            }
        }

        .hr-style-2 {
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0));
        }

        .hr-style-1 {
            overflow: visible;
            padding: 0;
            border: none;
            border-top: medium double #000000;
            text-align: center;
        }

        #printableAreaContent * {
            font-weight: normal !important;
        }
    </style>

    <style type="text/css" media="print">
        @page {
            size: auto;
            margin: 2px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid" style="color: black">
        <div class="row justify-content-center" id="printableArea">
            <div class="col-md-12">
                <div class="text-center">
                    <input type="button" class="btn btn-primary non-printable" onclick="printDiv('printableArea')"
                        value="{{translate('Proceed, If thermal printer is ready.')}}" />
                    <a href="{{url()->previous()}}" class="btn btn-danger non-printable">{{translate('Back')}}</a>
                </div>
                <hr class="non-printable">
            </div>
            <div class="col-5" id="printableAreaContent">
                <div class="text-center pt-4 mb-3">
                    <h2 style="line-height: 1">
                        {{\App\Model\BusinessSetting::where(['key' => 'restaurant_name'])->first()->value}}</h2>
                    <h5 style="font-size: 20px;font-weight: lighter;line-height: 1">
                        {{\App\Model\BusinessSetting::where(['key' => 'address'])->first()->value}}
                    </h5>
                    <h5 style="font-size: 16px;font-weight: lighter;line-height: 1">
                        {{translate('Phone')}} : {{\App\Model\BusinessSetting::where(['key' => 'phone'])->first()->value}}
                    </h5>
                </div>
                <hr class="text-dark hr-style-1">

                <div class="row mt-4">
                    <div class="col-6">
                        <h5>{{translate('Transaction ID')}} : {{$transaction['transaction_id']}}</h5>
                    </div>
                    <div class="col-6 text-right">
                        <h5 style="font-weight: lighter">
                            <span
                                class="font-weight-normal">{{date('d/M/Y h:i a', strtotime($transaction['created_at']))}}</span>
                        </h5>
                    </div>
                    <div class="col-12 mt-3">
                        @if(isset($transaction->user))
                            <h5>
                                {{translate('Customer Name')}} : <span
                                    class="font-weight-normal">{{$transaction->user['name']}}</span>
                            </h5>
                            <h5>
                                {{translate('Phone')}} : <span class="font-weight-normal">{{$transaction->user['phone']}}</span>
                            </h5>
                        @endif
                    </div>
                </div>

                <hr class="text-dark hr-style-2">

                <div class="py-3">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{translate('Description')}}</th>
                                <th class="text-right">{{translate('Amount')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    {{translate('Wallet Fund Addition')}}
                                    @if($transaction->reference)
                                        <br><small>{{translate('Reference')}}: {{$transaction->reference}}</small>
                                    @endif
                                </td>
                                <td class="text-right">
                                    {{ \App\CentralLogics\Helpers::set_symbol($transaction->credit) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end mb-3 m-0">
                    <div class="col-12 p-0">
                        <dl class="row text-right" style="color: black!important;">
                            <dt class="col-6" style="font-size: 20px">{{translate('Total')}}:</dt>
                            <dd class="col-6" style="font-size: 20px">
                                {{ \App\CentralLogics\Helpers::set_symbol($transaction->credit) }}</dd>

                            <dt class="col-6">{{translate('Current Wallet Balance')}}:</dt>
                            <dd class="col-6">{{ \App\CentralLogics\Helpers::set_symbol($transaction->balance) }}</dd>
                        </dl>
                    </div>
                </div>

                <hr class="text-dark hr-style-2">
                <h5 class="text-center pt-3">
                    "{{translate('THANK YOU')}}"
                </h5>
                <hr class="text-dark hr-style-2">
                <div class="text-center">{{\App\Model\BusinessSetting::where(['key' => 'footer_text'])->first()->value}}</div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        function printDiv(divName) {
            if ($('html').attr('dir') === 'rtl') {
                $('html').attr('dir', 'ltr')
                var printContents = document.getElementById(divName).innerHTML;
                var originalContents = document.body.innerHTML;
                document.body.innerHTML = printContents;
                $('#printableAreaContent').attr('dir', 'rtl')
                window.print();
                document.body.innerHTML = originalContents;
                $('html').attr('dir', 'rtl')
            } else {
                var printContents = document.getElementById(divName).innerHTML;
                var originalContents = document.body.innerHTML;
                document.body.innerHTML = printContents;
                window.print();
                document.body.innerHTML = originalContents;
            }
        }

        // Auto print on load
        $(document).ready(function () {
            // printDiv('printableArea');
        });
    </script>
@endpush