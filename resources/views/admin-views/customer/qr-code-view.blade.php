@extends('layouts.admin.app')

@section('title', translate('Customer QR Code'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-print-none pb-2">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" src="{{asset('public/assets/admin/img/icons/qr-code.png')}}" alt="">
                <span class="page-header-title">{{translate('Customer QR Code')}}</span>
            </h2>
        </div>

        <div class="d-flex gap-2 mb-3">
            <a href="{{ route('admin.customer.view', ['user_id' => $customer->id]) }}" class="btn btn-secondary">
                <i class="tio-chevron-left"></i> {{translate('Back to Customer')}}
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="tio-print"></i> {{translate('Print QR Code')}}
            </button>
            <a href="{{ route('admin.customer.download-qr-code', ['id' => $customer->id]) }}" class="btn btn-info">
                <i class="tio-download"></i> {{translate('Download')}}
            </a>
        </div>
    </div>

    <!-- QR Code Display -->
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body text-center p-5">
                    <!-- Customer Info -->
                    <div class="mb-4">
                        <div class="avatar avatar-xl avatar-circle mb-3">
                            <img class="img-fit rounded-circle" 
                                 src="{{ $customer->image_full_path }}" 
                                 alt="{{ $customer->name }}">
                        </div>
                        <h3 class="mb-1">{{ $customer->name }}</h3>
                        <p class="text-muted mb-0">{{ $customer->phone }}</p>
                        <small class="text-muted">{{ $customer->email }}</small>
                    </div>

                    <!-- QR Code -->
                    <div class="mb-4">
                        <div class="border rounded p-4 bg-light d-inline-block">
                            <img src="{{ $customer->qr_code_image_url }}" 
                                 alt="Customer QR Code" 
                                 style="max-width: 350px; width: 100%;">
                        </div>
                    </div>

                    <!-- QR Code ID -->
                    <div class="mb-4">
                        <label class="text-muted small">{{translate('QR Code ID')}}</label>
                        <div class="font-weight-bold" style="word-break: break-all; font-size: 14px;">
                            {{ $customer->qr_code }}
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="alert alert-soft-info text-left">
                        <h6 class="mb-2">{{translate('How to use this QR code:')}}</h6>
                        <ol class="mb-0 pl-3">
                            <li>{{translate('Open the mobile app')}}</li>
                            <li>{{translate('Go to Wallet section')}}</li>
                            <li>{{translate('Select "Send Money"')}}</li>
                            <li>{{translate('Scan this QR code')}}</li>
                            <li>{{translate('Enter amount and confirm transfer')}}</li>
                        </ol>
                    </div>

                    <!-- Wallet Balance -->
                    <div class="d-print-none">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="card bg-soft-primary">
                                    <div class="card-body py-3">
                                        <div class="text-muted small">{{translate('Wallet Balance')}}</div>
                                        <h4 class="mb-0">{{ Helpers::set_symbol($customer->wallet_balance ?? 0) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-soft-success">
                                    <div class="card-body py-3">
                                        <div class="text-muted small">{{translate('Loyalty Points')}}</div>
                                        <h4 class="mb-0">{{ $customer->point ?? 0 }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none {
        display: none !important;
    }
    
    body {
        background: white !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .alert {
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
}
</style>
@endsection