@extends('layouts.admin.app')

@section('title', translate('Database Backup'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <i class="tio-database"></i>
            </span>
            <span>{{ translate('Database Backup') }}</span>
        </h1>
    </div>

    <!-- Backup Actions Card -->
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="card-title">
                <i class="tio-cloud-upload"></i>
                {{ translate('Backup Actions') }}
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <form action="{{ route('admin.business-settings.web-app.system-setup.db-backup-create') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="tio-add"></i>
                            {{ translate('Create New Backup') }}
                        </button>
                    </form>
                    <small class="text-muted d-block mt-2">
                        {{ translate('Click to create a manual backup of your database. Automatic backups run every 7 days.') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup List Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="tio-folder"></i>
                {{ translate('Available Backups') }}
            </h5>
        </div>
        <div class="card-body">
            @if(count($backups) > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-borderless">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('File Name') }}</th>
                                <th>{{ translate('Size') }}</th>
                                <th>{{ translate('Created At') }}</th>
                                <th class="text-center">{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $backup)
                                <tr>
                                    <td>
                                        <i class="tio-file-outlined"></i>
                                        {{ $backup['name'] }}
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-info">{{ $backup['size'] }}</span>
                                    </td>
                                    <td>
                                        <i class="tio-time"></i>
                                        {{ $backup['date'] }}
                                    </td>
                                    <td class="text-center">
                                        <!-- Download Button -->
                                        <a href="{{ route('admin.business-settings.web-app.system-setup.db-backup-download', $backup['name']) }}" 
                                           class="btn btn-sm btn-success"
                                           title="{{ translate('Download') }}">
                                            <i class="tio-download"></i>
                                        </a>

                                        <!-- Restore Button -->
                                        <button type="button" 
                                                class="btn btn-sm btn-warning"
                                                onclick="confirmRestore('{{ $backup['name'] }}')"
                                                title="{{ translate('Restore') }}">
                                            <i class="tio-refresh"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button" 
                                                class="btn btn-sm btn-danger"
                                                onclick="confirmDelete('{{ $backup['name'] }}')"
                                                title="{{ translate('Delete') }}">
                                            <i class="tio-delete"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <img src="{{ asset('public/assets/admin/img/empty-state.png') }}" 
                         alt="No backups" 
                         class="mb-3"
                         style="width: 100px;">
                    <h5>{{ translate('No Backups Found') }}</h5>
                    <p class="text-muted">{{ translate('Create your first backup to get started') }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Information Card -->
    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title">
                <i class="tio-info"></i>
                {{ translate('Important Information') }}
            </h5>
            <ul class="list-unstyled">
                <li><i class="tio-checkmark-circle text-success"></i> {{ translate('Backups are stored in: storage/app/backups/') }}</li>
                <li><i class="tio-checkmark-circle text-success"></i> {{ translate('Automatic backups run every 7 days at 2:00 AM') }}</li>
                <li><i class="tio-checkmark-circle text-success"></i> {{ translate('Only the last 10 backups are kept automatically') }}</li>
                <li><i class="tio-warning text-warning"></i> {{ translate('Always download important backups to external storage') }}</li>
                <li><i class="tio-warning text-warning"></i> {{ translate('Restoring a backup will overwrite your current database') }}</li>
            </ul>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="delete-backup-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<!-- Restore Confirmation Form -->
<form id="restore-backup-form" action="{{ route('admin.business-settings.web-app.system-setup.db-backup-restore') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="filename" id="restore-filename">
</form>

@endsection

@push('script_2')
<script>
    function confirmDelete(filename) {
        Swal.fire({
            title: '{{ translate('Are you sure?') }}',
            text: '{{ translate('You want to delete this backup?') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ translate('Yes, delete it!') }}',
            cancelButtonText: '{{ translate('Cancel') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('delete-backup-form');
                form.action = '{{ route('admin.business-settings.web-app.system-setup.db-backup-delete', '') }}/' + filename;
                form.submit();
            }
        });
    }

    function confirmRestore(filename) {
        Swal.fire({
            title: '{{ translate('Are you sure?') }}',
            text: '{{ translate('This will overwrite your current database! Make sure you have a recent backup.') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ translate('Yes, restore it!') }}',
            cancelButtonText: '{{ translate('Cancel') }}'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('restore-filename').value = filename;
                document.getElementById('restore-backup-form').submit();
            }
        });
    }
</script>
@endpush