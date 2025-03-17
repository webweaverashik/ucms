@push('page-css')
    <!--datatable css-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
    <!--datatable responsive css-->
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap.min.css" />

    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
@endpush


@extends('layouts.app')

@section('title', 'All Students')


@section('page-title')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">All Students</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="">Student Info</a>
                        </li>
                        <li class="breadcrumb-item active">
                            All Students
                        </li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
@endsection


@section('content')
    <div class="row">
        <div class="col-xxl-3 col-sm-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fw-medium text-muted mb-0">Total Tickets</p>
                            <h2 class="mt-4 ff-secondary fw-semibold"><span class="counter-value"
                                    data-target="547">0</span>k</h2>
                            <p class="mb-0 text-muted"><span class="badge bg-light text-success mb-0">
                                    <i class="ri-arrow-up-line align-middle"></i> 17.32 %
                                </span> vs. previous month</p>
                        </div>
                        <div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-4">
                                    <i class="ri-ticket-2-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div> <!-- end card-->
        </div>
        <!--end col-->
        <div class="col-xxl-3 col-sm-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fw-medium text-muted mb-0">Pending Tickets</p>
                            <h2 class="mt-4 ff-secondary fw-semibold"><span class="counter-value"
                                    data-target="124">0</span>k</h2>
                            <p class="mb-0 text-muted"><span class="badge bg-light text-danger mb-0">
                                    <i class="ri-arrow-down-line align-middle"></i> 0.96 %
                                </span> vs. previous month</p>
                        </div>
                        <div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-4">
                                    <i class="mdi mdi-timer-sand"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div>
        </div>
        <!--end col-->
        <div class="col-xxl-3 col-sm-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fw-medium text-muted mb-0">Closed Tickets</p>
                            <h2 class="mt-4 ff-secondary fw-semibold"><span class="counter-value"
                                    data-target="107">0</span>K</h2>
                            <p class="mb-0 text-muted"><span class="badge bg-light text-danger mb-0">
                                    <i class="ri-arrow-down-line align-middle"></i> 3.87 %
                                </span> vs. previous month</p>
                        </div>
                        <div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-4">
                                    <i class="ri-shopping-bag-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div>
        </div>
        <!--end col-->
        <div class="col-xxl-3 col-sm-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fw-medium text-muted mb-0">Deleted Tickets</p>
                            <h2 class="mt-4 ff-secondary fw-semibold"><span class="counter-value"
                                    data-target="15.95">0</span>%</h2>
                            <p class="mb-0 text-muted"><span class="badge bg-light text-success mb-0">
                                    <i class="ri-arrow-up-line align-middle"></i> 1.09 %
                                </span> vs. previous month</p>
                        </div>
                        <div>
                            <div class="avatar-sm flex-shrink-0">
                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-4">
                                    <i class="ri-delete-bin-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div><!-- end card body -->
            </div>
        </div>
        <!--end col-->
    </div>
    <!--end row-->


    <div class="row">
        <div class="col-xxl-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">All Students Summary</h5>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('students.create') }}" class="btn btn-primary add-btn"><i
                                        class="ri-add-line align-bottom me-1"></i> New Admission</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs nav-border-top nav-border-top-primary mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#nav-active-students" role="tab"
                                aria-selected="false">
                                <i class="mdi mdi-account-check me-1 align-bottom"></i> Active Students
                                <span class="badge bg-success align-middle ms-1">{{ count($active_students) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#nav-inactive-students" role="tab"
                                aria-selected="false">
                                <i class="mdi mdi-account-cancel me-1 align-bottom"></i> Inactive Students
                                <span class="badge bg-danger align-middle ms-1">{{ count($inactive_students) }}</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content text-muted">
                        <div class="tab-pane active" id="nav-active-students" role="tabpanel">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <table id="active-students-table"
                                        class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                {{-- <th scope="col" style="width: 10px;">
                                                    <div class="form-check">
                                                        <input class="form-check-input fs-15" type="checkbox"
                                                            id="checkAll" value="option">
                                                    </div>
                                                </th> --}}
                                                <th>SL</th>
                                                <th>Name & ID</th>
                                                <th>Branch</th>
                                                <th>Class</th>
                                                <th>Shift</th>
                                                <th>Institution</th>
                                                <th>Guardians</th>
                                                <th>Fee</th>
                                                <th>Due Date</th>
                                                <th>Phone No<br>(Home)</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($active_students as $student)
                                                <tr>
                                                    {{-- <th scope="row">
                                                    <div class="form-check">
                                                        <input class="form-check-input fs-15" type="checkbox"
                                                            name="checkAll" value="option1">
                                                    </div>
                                                </th> --}}
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0 me-3">
                                                                <div class="avatar-sm rounded p-1"><img
                                                                        src="{{ $student->photo_url ?? 'assets/images/users/dummy.png' }}"
                                                                        alt="" class="img-fluid d-block"></div>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h5 class="fs-14 mb-1"><a
                                                                        href="{{ route('students.show', $student->id) }}"
                                                                        class="text-body">{{ $student->full_name }}</a>
                                                                </h5>
                                                                <p class="text-muted mb-0">ID : <span
                                                                        class="fw-bold">{{ $student->student_unique_id }}</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        </a>
                                                    </td>
                                                    <td>{{ $student->branch->branch_name }}</td>
                                                    <td>{{ $student->class->name }}</td>
                                                    <td>{{ $student->shift->name }}</td>
                                                    <td>{{ $student->institution->name }} (EIIN:
                                                        {{ $student->institution->eiin_number }})</td>
                                                    <td>
                                                        @foreach ($student->guardians as $guardian)
                                                            <a
                                                                href="{{ route('guardians.show', $guardian->guardian_id) }}">
                                                                @if ($guardian->is_primary)
                                                                    <span class="badge badge-label bg-primary"
                                                                        title="Primary Guardian"><i
                                                                            class="mdi mdi-circle-medium"></i>{{ $guardian->guardian->name }}</span>
                                                                @else
                                                                    <span
                                                                        class="badge badge-label bg-info">{{ $guardian->guardian->name }}</span>
                                                                @endif
                                                            </a>
                                                            @if (!$loop->last)
                                                                <br>
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td>3000tk</td>
                                                    <td>1-7</td>
                                                    <td>
                                                        @foreach ($student->mobileNumbers->where('number_type', 'home') as $mobile)
                                                            {{ $mobile->mobile_number }}
                                                            @if (!$loop->last)
                                                                <br>
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td><span
                                                            class="badge bg-success">{{ ucfirst($student->studentActivation->active_status) }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown d-inline-block">
                                                            <button class="btn btn-soft-secondary btn-sm dropdown"
                                                                type="button" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <i class="ri-more-fill align-middle"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a href="{{ route('students.show', $student->id) }}"
                                                                        class="dropdown-item"><i
                                                                            class="ri-eye-fill align-bottom me-2 text-muted"></i>
                                                                        View</a></li>
                                                                <li><a href="{{ route('students.edit', $student->id) }}"
                                                                        class="dropdown-item edit-item-btn"><i
                                                                            class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                                        Edit</a></li>
                                                                <li>
                                                                    <a href="#"
                                                                        class="dropdown-item remove-item-btn">
                                                                        <i
                                                                            class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                                        Delete
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="nav-inactive-students" role="tabpanel">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <table id="inactive-students-table"
                                        class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                {{-- <th scope="col" style="width: 10px;">
                                                    <div class="form-check">
                                                        <input class="form-check-input fs-15" type="checkbox"
                                                            id="checkAll" value="option">
                                                    </div>
                                                </th> --}}
                                                <th>SL</th>
                                                <th>Name & ID</th>
                                                <th>Branch</th>
                                                <th>Class</th>
                                                <th>Shift</th>
                                                <th>Institution</th>
                                                <th>Guardians</th>
                                                <th>Fee</th>
                                                <th>Phone No<br>(Home)</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($inactive_students as $student)
                                                <tr>
                                                    {{-- <th scope="row">
                                                    <div class="form-check">
                                                        <input class="form-check-input fs-15" type="checkbox"
                                                            name="checkAll" value="option1">
                                                    </div>
                                                </th> --}}
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0 me-3">
                                                                <div class="avatar-sm rounded p-1"><img
                                                                        src="{{ $student->photo_url ?? 'assets/images/users/dummy.png' }}"
                                                                        alt="" class="img-fluid d-block">
                                                                </div>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h5 class="fs-14 mb-1"><a
                                                                        href="{{ route('students.show', $student->id) }}"
                                                                        class="text-body">{{ $student->full_name }}</a>
                                                                </h5>
                                                                <p class="text-muted mb-0">ID: <span
                                                                        class="fw-bold">{{ $student->student_unique_id }}</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        </a>
                                                    </td>
                                                    <td>{{ $student->branch->branch_name }}</td>
                                                    <td>{{ $student->class->name }}</td>
                                                    <td>{{ $student->shift->name }}</td>
                                                    <td>{{ $student->institution->name }} (EIIN:
                                                        {{ $student->institution->eiin_number }})</td>
                                                    <td>
                                                        @foreach ($student->guardians as $guardian)
                                                            <a
                                                                href="{{ route('guardians.show', $guardian->guardian_id) }}">
                                                                @if ($guardian->is_primary)
                                                                    <span class="badge badge-label bg-primary"
                                                                        title="Primary Guardian"><i
                                                                            class="mdi mdi-circle-medium"></i>{{ $guardian->guardian->name }}</span>
                                                                @else
                                                                    <span
                                                                        class="badge badge-label bg-info">{{ $guardian->guardian->name }}</span>
                                                                @endif
                                                            </a>
                                                            @if (!$loop->last)
                                                                <br>
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td>3000tk</td>
                                                    <td>
                                                        @foreach ($student->mobileNumbers->where('number_type', 'home') as $mobile)
                                                            {{ $mobile->mobile_number }}
                                                            @if (!$loop->last)
                                                                <br>
                                                            @endif
                                                        @endforeach
                                                    </td>
                                                    <td><span
                                                            class="badge bg-danger">{{ ucfirst($student->studentActivation->active_status) }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown d-inline-block">
                                                            <button class="btn btn-soft-secondary btn-sm dropdown"
                                                                type="button" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <i class="ri-more-fill align-middle"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a href="{{ route('students.show', $student->id) }}"
                                                                        class="dropdown-item"><i
                                                                            class="ri-eye-fill align-bottom me-2 text-muted"></i>
                                                                        View</a></li>
                                                                <li><a href="{{ route('students.edit', $student->id) }}"
                                                                        class="dropdown-item edit-item-btn"><i
                                                                            class="ri-pencil-fill align-bottom me-2 text-muted"></i>
                                                                        Edit</a></li>
                                                                <li>
                                                                    <a href="#"
                                                                        class="dropdown-item remove-item-btn">
                                                                        <i
                                                                            class="ri-delete-bin-fill align-bottom me-2 text-muted"></i>
                                                                        Delete
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- end card-body -->
            </div>
        </div>
        <!--end col-->
    </div>
@endsection



@push('page-js')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


    <!--datatable js-->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

    <!-- Sweet Alerts js -->
    <script src="assets/libs/sweetalert2/sweetalert2.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let table1 = new DataTable('#active-students-table', );
            let table2 = new DataTable('#inactive-students-table', );
        });
        document.getElementById("student_info_menu").classList.add("collapsed", "active");
        document.getElementById("student_info_menu").setAttribute("aria-expanded", "true");
        document.getElementById("sidebarStudentInfo").classList.add("show");
        document.querySelector('[data-key="All Students"]').classList.add("active");
    </script>
@endpush
