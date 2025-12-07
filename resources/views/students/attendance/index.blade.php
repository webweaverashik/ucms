@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'Student Attendance')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Today ({{ date('d-m-Y') }}) Attendance
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">
                    Attendance </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Students </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title w-100">
                <form id="student_list_filter_form" class="row g-3 align-items-end w-100">
                    <input type="hidden" value="{{ date('d-m-Y') }}" name="attendance_date">
                    <!-- Branch Selection -->
                    <div class="col-md-3 @if (!auth()->user()->hasRole('admin')) d-none @endif">
                        <label for="student_branch_group" class="form-label fw-semibold required">Branch</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <select id="student_branch_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="branch_id"
                                data-control="select2" data-placeholder="Select branch" data-hide-search="true">
                                <option></option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @if ($loop->first) selected @endif>
                                        {{ $branch->branch_name }}
                                        ({{ $branch->branch_prefix }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Class Selection -->
                    <div class="col-md-3">
                        <label for="student_class_group" class="form-label fw-semibold required">Class</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <select id="student_class_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="class_id"
                                data-control="select2" data-placeholder="Select class" data-hide-search="false">
                                <option></option>
                                @foreach ($classnames as $classname)
                                    <option value="{{ $classname->id }}" @if ($loop->first) selected @endif>
                                        {{ $classname->name }}
                                        ({{ $classname->class_numeral }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Batch Selection -->
                    <div class="col-md-3">
                        <label for="student_batch_group" class="form-label fw-semibold required">Batch</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <select id="student_batch_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="batch_id"
                                data-control="select2" data-placeholder="Select batch" data-hide-search="true">
                                <option></option>
                                @foreach ($batches as $batch)
                                    <option value="{{ $batch->id }}" @if ($loop->first) selected @endif>
                                        {{ $batch->name }} ({{ $batch->branch->branch_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary" id="submit_button">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!--end::Card header-->

        <!--begin::Notes Distribution Panel-->
        <div class="card-body py-10">
            <div id="student_list_loader" class="text-center my-3 d-none">
                <strong>Loading...</strong>
            </div>

            <div id="off_day_warning"></div>

            <div class="d-flex align-items-center gap-5 d-none" id="bulk_buttons">
                <!-- All Present Radio -->
                <label class="form-check form-check-custom form-check-solid form-check-success align-items-center">
                    <input class="form-check-input" type="radio" name="mark_all" id="mark_all_present" value="present" />
                    <span class="form-check-label fw-bold text-success">All Present</span>
                </label>

                <!-- All Late Radio -->
                <label class="form-check form-check-custom form-check-solid form-check-warning align-items-center">
                    <input class="form-check-input" type="radio" name="mark_all" id="mark_all_late" value="late" />
                    <span class="form-check-label fw-bold text-warning">All Late</span>
                </label>

            </div>


            <div id="student_list_container"></div>

            <div class="mt-5 d-none" id="save_attendance_section">
                <button class="btn btn-primary w-100" id="save_attendance_button">Save Attendance</button>
            </div>

        </div>
        <!--end::Notes Distribution Panel-->
    </div>
    <!--end::Card-->
@endsection


@push('vendor-js')
@endpush


@push('page-js')
    {{-- <script>
        window.UCMS = {
            routes: {
                batchesByBranch: "{{ route('attendances.batches') }}",
                loadStudents: "{{ route('attendances.students') }}",
                storeAttendance: "{{ route('attendances.store') }}",
            }
        };
    </script> --}}

    {{-- <script src="{{ asset('js/attendances/index.js') }}"></script> --}}

    <script>
        $(document).ready(function() {

            // CSRF Token Setup for AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // 1. & 6. Submit Filter Form
            $('#student_list_filter_form').on('submit', function(e) {
                e.preventDefault();

                // Validate Filters
                let branch = $('select[name="branch_id"]').val();
                let classId = $('select[name="class_id"]').val();
                let batch = $('select[name="batch_id"]').val();

                if (!branch || !classId || !batch) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Please select Branch, Class, and Batch.',
                    });
                    return;
                }

                // Show Loader, Hide content
                $('#student_list_loader').removeClass('d-none');
                $('#student_list_container').html('');
                $('#save_attendance_section').addClass('d-none');
                $('#bulk_buttons').addClass('d-none');

                const formData = $(this).serialize();

                // Fetch Students
                $.ajax({
                    url: "{{ route('attendances.get_students') }}",
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        $('#student_list_loader').addClass('d-none');

                        // Reset specific containers
                        $('#off_day_warning').html('');

                        // 1. Handle Off Day Warning
                        if (response.is_off_day) {
                            let alertHtml = `
                <div class="alert alert-warning d-flex align-items-center p-5 mb-5">
                    <i class="ki-outline ki-information-5 fs-2hx text-warning me-4"></i>
                    <div class="d-flex flex-column">
                        <h4 class="mb-1 text-warning">Off Day Warning</h4>
                        <span>
                            Today (<strong>${response.off_day_name}</strong>) is marked as the official off-day for this batch. 
                            However, you may still proceed to take attendance.
                        </span>
                    </div>
                </div>
            `;
                            $('#off_day_warning').html(alertHtml);
                        }

                        // 2. Handle Student List
                        if (response.count > 0) {
                            renderStudentTable(response.students);
                            $('#bulk_buttons').removeClass('d-none');
                            $('#save_attendance_section').removeClass('d-none');
                        } else {
                            $('#student_list_container').html(
                                '<div class="alert alert-info">No students found for this criteria.</div>'
                            );
                            $('#save_attendance_section').addClass(
                                'd-none'); // Hide save button if no students
                            $('#bulk_buttons').addClass('d-none');
                        }
                    },
                    error: function(xhr) {
                        $('#student_list_loader').addClass('d-none');
                        Swal.fire('Error', 'Something went wrong fetching students.', 'error');
                    }
                });
            });

            // 2. Render Table Function
            function renderStudentTable(students) {
                let html = `
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-300 align-middle gs-0 gy-3" id="attendance_table">
                <thead>
                    <tr class="fw-bold text-muted">
                        <th class="w-25px">#</th> <!-- New Serial Header -->
                        <th>Student Info</th>
                        <th class="text-center">Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
    `;

                // Added 'index' to the forEach callback
                students.forEach((student, index) => {
                    // Determine Checked Status
                    let presentCheck = student.status === 'present' ? 'checked' : '';
                    let lateCheck = student.status === 'late' ? 'checked' : '';
                    let absentCheck = student.status === 'absent' ? 'checked' : '';

                    html += `
            <tr data-student-id="${student.id}">
                
                <!-- New Serial Number Column -->
                <td class="fw-bold text-gray-600">${index + 1}</td>

                <td>
                    <div class="d-flex align-items-center">
                        <div class="d-flex justify-content-start flex-column">
                            <span class="text-gray-800 fw-bold mb-1 fs-6">${student.name}</span>
                            <span class="text-muted fw-semibold d-block fs-7">ID: ${student.student_unique_id}</span>
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-5">
                        <div class="form-check form-check-custom form-check-success form-check-solid">
                            <input class="form-check-input status-radio" type="radio" value="present" name="status_${student.id}" id="status_present_${student.id}" ${presentCheck} />
                            <label class="form-check-label" for="status_present_${student.id}">Present</label>
                        </div>
                        <div class="form-check form-check-custom form-check-warning form-check-solid">
                            <input class="form-check-input status-radio" type="radio" value="late" name="status_${student.id}" id="status_late_${student.id}" ${lateCheck} />
                            <label class="form-check-label" for="status_late_${student.id}">Late</label>
                        </div>
                        <div class="form-check form-check-custom form-check-danger form-check-solid">
                            <input class="form-check-input status-radio" type="radio" value="absent" name="status_${student.id}" id="status_absent_${student.id}" ${absentCheck} />
                            <label class="form-check-label" for="status_absent_${student.id}">Absent</label>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-solid form-control-sm remarks-input" 
                        placeholder="Add a remarks" value="${student.remarks || ''}" />
                </td>
            </tr>
        `;
                });

                html += `</tbody></table></div>`;
                $('#student_list_container').html(html);
            }

            // 5. Bulk Buttons Logic
            $('input[name="mark_all"]').on('change', function() {
                let val = $(this).val(); // 'present' or 'late'

                if (val === 'present') {
                    // Select all radios with value 'present'
                    $('.status-radio[value="present"]').prop('checked', true);
                } else if (val === 'late') {
                    // Select all radios with value 'late'
                    $('.status-radio[value="late"]').prop('checked', true);
                }
            });

            // 3. & 4. Save Attendance Logic
            $('#save_attendance_button').on('click', function() {
                let attendanceData = [];
                let validationError = false;

                // Loop through table rows
                $('#attendance_table tbody tr').each(function() {
                    let studentId = $(this).data('student-id');
                    let status = $(this).find(`input[name="status_${studentId}"]:checked`).val();
                    let remarks = $(this).find('.remarks-input').val();

                    // 4. Validate row
                    if (!status) {
                        validationError = true;
                        $(this).addClass('bg-light-danger'); // Highlight error row
                    } else {
                        $(this).removeClass('bg-light-danger');
                        attendanceData.push({
                            student_id: studentId,
                            status: status,
                            remarks: remarks
                        });
                    }
                });

                if (validationError) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Incomplete Attendance',
                        text: 'Please select a status (Present, Late, or Absent) for all students.',
                    });
                    return;
                }

                // Prepare Payload
                let payload = {
                    attendance_date: $('input[name="attendance_date"]').val(),
                    branch_id: $('select[name="branch_id"]').val(),
                    class_id: $('select[name="class_id"]').val(),
                    batch_id: $('select[name="batch_id"]').val(),
                    attendances: attendanceData
                };

                // Disable button to prevent double submit
                let btn = $(this);
                btn.prop('disabled', true).text('Saving...');

                // 6. & 7. AJAX Save/Update
                $.ajax({
                    url: "{{ route('attendances.store_bulk') }}",
                    type: "POST",
                    data: payload,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        btn.prop('disabled', false).text('Save Attendance');
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).text('Save Attendance');
                        let errorMsg = 'An error occurred.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMsg, 'error');
                    }
                });
            });

        });
    </script>

    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("attendance_link").classList.add("active");
    </script>
@endpush
