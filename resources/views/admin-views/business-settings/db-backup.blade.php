@extends('layouts.admin.app')

@section('title', translate('Database Backup'))

@push('css_or_js')
    <!-- Optional: Add any custom CSS -->
@endpush

@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <h2 class="h1 mb-0 d-flex align-items-center gap-2">
            <img width="20" class="avatar-img" src="{{ asset('public/assets/admin/img/icons/cloud-database.png') }}" alt="">
            <span class="page-header-title">
                {{ translate('Database Backup') }}
            </span>
        </h2>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="tio-cloud-download"></i> {{ translate('Create Database Backup') }}
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        {{ translate('Create a secure, compressed backup of your entire database.') }}
                        <br>
                        <small>{{ translate('Backups are automatically cleaned up after 7 days (configurable).') }}</small>
                    </p>

                    <form action="{{ route('admin.business-settings.web-app.system-setup.db-backup') }}"
                          method="POST"
                          onsubmit="return confirm('{{ translate('Are you sure you want to create a backup now? This may take a few minutes.') }}');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="tio-cloud-download"></i> {{ translate('Backup Database Now') }}
                        </button>
                    </form>

                    <div class="mt-4">
                        <small class="text-muted">
                            {{ translate('Backup files are stored in:') }}
                            <code>storage/app/backup-laravel/</code>
                            <br>
                            {{ translate('File format:') }} <strong>.zip</strong> {{ translate('(contains .sql file inside)') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional: Future backup list can go here -->
</div>
@endsection