@extends('layouts.app')

@section('title', 'Database Backup')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Database Backup
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Settings</li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Backup</li>
        </ul>
    </div>
@endsection

@section('content')
    @include('settings.partials.hero')

    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>UCMS Backup - Database</h2>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" id="btn-create-backup">
                    <i class="ki-outline ki-plus fs-2"></i>
                    Create Backup
                </button>
            </div>
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Stats-->
            <div class="row g-5 mb-8">
                <div class="col-md-4">
                    <div class="border border-gray-300 border-dashed rounded py-3 px-4">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-file-added fs-2 text-primary me-2"></i>
                            <div class="fs-4 fw-bold" id="stats-total">{{ count($backups ?? []) }}</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">Total Backups</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border border-gray-300 border-dashed rounded py-3 px-4">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-folder fs-2 text-info me-2"></i>
                            <div class="fs-4 fw-bold" id="stats-size">{{ $totalSize ?? '0 B' }}</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">Total Size</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border border-gray-300 border-dashed rounded py-3 px-4">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-time fs-2 text-success me-2"></i>
                            <div class="fs-4 fw-bold" id="stats-last">{{ $lastBackup ?? 'Never' }}</div>
                        </div>
                        <div class="fw-semibold fs-6 text-gray-500">Last Backup</div>
                    </div>
                </div>
            </div>
            <!--end::Stats-->

            <!--begin::Table-->
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="ps-4 rounded-start">#</th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th class="pe-4 text-end rounded-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="backup-table-body">
                        @forelse($backups ?? [] as $index => $backup)
                            <tr data-filename="{{ $backup['filename'] }}">
                                <td class="ps-4">{{ $index + 1 }}</td>
                                <td>
                                    <i class="ki-outline ki-file-added fs-4 text-primary me-2"></i>
                                    {{ $backup['filename'] }}
                                </td>
                                <td>{{ $backup['size_formatted'] }}</td>
                                <td>{{ $backup['date_formatted'] }}</td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('backup.download', $backup['filename']) }}"
                                        class="btn btn-sm btn-light-success me-2" title="Download">
                                        <i class="ki-outline ki-file-down fs-4"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-light-danger btn-delete-backup"
                                        data-filename="{{ $backup['filename'] }}" title="Delete">
                                        <i class="ki-outline ki-trash fs-4"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="no-backups-row">
                                <td colspan="5" class="text-center py-10">
                                    <i class="ki-outline ki-file-deleted fs-3x text-gray-400 mb-5"></i>
                                    <p class="text-gray-500 fs-5 mb-0">No backups found</p>
                                    <p class="text-gray-400 fs-7">Click "Create Backup" to generate your first backup</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
@endsection

@push('page-js')
    <script>
        const backupRoutes = {
            create: "{{ route('backup.create') }}",
            download: "{{ route('backup.download', ':filename') }}",
            destroy: "{{ route('backup.destroy', ':filename') }}"
        };
        const csrfToken = "{{ csrf_token() }}";
    </script>
    <script src="{{ asset('js/settings/backup.js') }}"></script>
    <script>
        document.getElementById("settings_link")?.classList.add("active");
        document.getElementById("settings_backup_link")?.classList.add("active");
    </script>
@endpush
