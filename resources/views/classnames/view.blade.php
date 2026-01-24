@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/classnames/view.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Class - ' . $classname->name)

@section('header-title')
    @include('classnames.partials.view.header')
@endsection

@section('content')
    @php
        $user = auth()->user();
        $canDeactivate = $user->can('students.deactivate');
        $canDownloadForm = $user->can('students.form.download');
        $canEdit = $user->can('students.edit');
        $canDelete = $user->can('students.delete');
        $manageSubjects = $user->can('subjects.manage');
        $createClass = $user->can('classes.create');

        $groupedSubjects = $classname->subjects->groupBy('academic_group');
        $totalSubjects = $classname->subjects->count();

        // Define badge colors for different branches
        $badgeColors = [
            'badge-light-primary',
            'badge-light-success',
            'badge-light-warning',
            'badge-light-danger',
            'badge-light-info',
        ];

        // Map branches to badge colors dynamically
        $branchColors = [];
        if (isset($branches)) {
            foreach ($branches as $index => $branch) {
                $branchColors[$branch->id] = $badgeColors[$index % count($badgeColors)];
            }
        }
    @endphp

    <!--begin::Layout-->
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        @include('classnames.partials.view.sidebar')
        <!--end::Sidebar-->

        <!--begin::Content-->
        <div class="flex-lg-row-fluid ms-lg-10" data-kt-swapper="false">
            <!--begin:::Tabs-->
            @include('classnames.partials.view.tabs-nav')
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">
                <!--begin:::Subjects Tab pane-->
                @include('classnames.partials.view.tabs.subjects')
                <!--end:::Subjects Tab pane-->

                <!--begin:::Students Tab pane-->
                @include('classnames.partials.view.tabs.students')
                <!--end:::Students Tab pane-->

                <!--begin:::Secondary Classes Tab pane-->
                @include('classnames.partials.view.tabs.secondary-classes')
                <!--end:::Secondary Classes Tab pane-->
            </div>
            <!--end:::Tab content-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->

    <!--begin::Modals-->
    @include('classnames.partials.view.modals.add-subject')
    @include('classnames.partials.view.modals.edit-class')
    @include('classnames.partials.view.modals.add-special-class')
    @include('classnames.partials.view.modals.edit-special-class')
    @include('classnames.partials.view.modals.toggle-activation')
    @include('classnames.partials.view.modals.bulk-toggle-activation')
    <!--end::Modals-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        // Route configurations
        const routeDeleteSubject = "{{ route('subjects.destroy', ':id') }}";
        const routeSecondaryClasses = "{{ route('secondary-classes.index') }}";
        const routeSecondaryClassShow = "{{ route('secondary-classes.show', ':id') }}";
        const routeSecondaryClassUpdate = "{{ route('secondary-classes.update', ':id') }}";
        const routeSecondaryClassDestroy = "{{ route('secondary-classes.destroy', ':id') }}";
        const isAdminUser = {{ $isAdmin ? 'true' : 'false' }};
        const routeToggleActive = "{{ route('students.toggleActive') }}";
        const routeBulkToggleActive = "{{ route('students.bulkToggleActive') }}";

        // AJAX DataTable configuration
        const classId = {{ $classname->id }};
        const routeStudentsAjax = "{{ route('classnames.students-ajax', $classname->id) }}";
        const routeClassStats = "{{ route('classnames.stats', $classname->id) }}";
        const routeSubjectsAjax = "{{ route('classnames.subjects-ajax', $classname->id) }}";
        const routeUpdateSubject = "{{ route('subjects.update', ':id') }}";
        const firstBranchId = {{ $isAdmin && $branches->count() > 0 ? $branches->first()->id : 'null' }};

        // Class and permission configuration
        const classIsActive = {{ $classname->isActive() ? 'true' : 'false' }};
        const canDeactivateStudents = {{ $canDeactivate ? 'true' : 'false' }};
        const showCheckboxColumn = {{ $canDeactivate && $classname->isActive() ? 'true' : 'false' }};
        const manageSubjects = {{ $manageSubjects ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('js/classnames/view.js') }}"></script>
    <script>
        $('select[data-control="select2"]').select2({
            width: 'resolve'
        });
    </script>
    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("class_link").classList.add("active");
    </script>
@endpush