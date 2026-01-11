"use strict";

// DataTable instance
var dataTable;

// Initialize DataTable
var initDataTable = function () {
    const table = document.getElementById('kt_enrolled_students_table');
    
    if (!table) return;

    dataTable = $(table).DataTable({
        info: false,
        order: [],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        columnDefs: [
            { orderable: false, targets: isAdminUser ? [0, 8] : [0] }
        ],
        language: {
            emptyTable: "No students enrolled in this special class"
        },
        drawCallback: function() {
            initTooltips();
        }
    });

    // Search functionality
    const searchInput = document.querySelector('[data-enrolled-students-table-filter="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            dataTable.search(e.target.value).draw();
        });
    }

    // Filter functionality
    const filterButton = document.querySelector('[data-enrolled-students-table-filter="filter"]');
    if (filterButton) {
        filterButton.addEventListener('click', function () {
            applyFilters();
        });
    }

    // Reset filter
    const resetButton = document.querySelector('[data-enrolled-students-table-filter="reset"]');
    if (resetButton) {
        resetButton.addEventListener('click', function () {
            $('#filter_status').val('').trigger('change');
            if (document.getElementById('filter_branch')) {
                $('#filter_branch').val('').trigger('change');
            }
            dataTable.search('').columns().search('').draw();
        });
    }

    // Branch tab filtering
    document.querySelectorAll('[data-branch-filter]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            const branchId = this.getAttribute('data-branch-filter');
            filterByBranch(branchId);
        });
    });

    // Apply initial filter if there's an active tab
    const activeTab = document.querySelector('.nav-link.active[data-branch-filter]');
    if (activeTab) {
        const branchId = activeTab.getAttribute('data-branch-filter');
        filterByBranch(branchId);
    }
};

// Apply filters
var applyFilters = function () {
    const status = document.getElementById('filter_status')?.value || '';
    const branch = document.getElementById('filter_branch')?.value || '';

    // Custom filtering
    $.fn.dataTable.ext.search.pop(); // Remove previous filter
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const row = dataTable.row(dataIndex).node();
        const rowStatus = row.getAttribute('data-status');
        const rowBranch = row.getAttribute('data-branch-id');

        let statusMatch = !status || rowStatus === status;
        let branchMatch = !branch || rowBranch === branch;

        return statusMatch && branchMatch;
    });

    dataTable.draw();
};

// Filter by branch (for tab clicks)
var filterByBranch = function (branchId) {
    $.fn.dataTable.ext.search.pop();
    
    if (branchId !== 'all') {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const row = dataTable.row(dataIndex).node();
            return row.getAttribute('data-branch-id') === branchId;
        });
    }
    
    dataTable.draw();
};

// Initialize Select2 for student enrollment
var initStudentSelect = function () {
    const select = document.getElementById('enroll_student_select');
    if (!select) return;

    $(select).select2({
        dropdownParent: $('#kt_modal_enroll_student'),
        ajax: {
            url: routeAvailableStudents,
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    search: params.term,
                    branch_id: ''
                };
            },
            processResults: function (response) {
                if (!response.success) return { results: [] };
                
                return {
                    results: response.data.map(function (student) {
                        return {
                            id: student.id,
                            text: student.name + ' (' + student.student_unique_id + ')',
                            student: student
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: 'Type to search students...',
        allowClear: true,
        templateResult: formatStudentResult,
        templateSelection: formatStudentSelection
    });
};

// Format student result in dropdown
var formatStudentResult = function (data) {
    if (data.loading) return data.text;
    if (!data.student) return data.text;

    const student = data.student;
    const statusClass = student.is_active ? 'badge-light-success' : 'badge-light-danger';
    const statusText = student.is_active ? 'Active' : 'Inactive';

    return $(`
        <div class="d-flex align-items-center">
            <div class="d-flex flex-column">
                <span class="fw-bold">${student.name}</span>
                <span class="text-muted fs-7">${student.student_unique_id} | ${student.branch_name} | ${student.batch_name || '-'}</span>
            </div>
            <span class="badge ${statusClass} ms-auto">${statusText}</span>
        </div>
    `);
};

// Format student selection
var formatStudentSelection = function (data) {
    if (!data.student) return data.text;
    return data.student.name + ' (' + data.student.student_unique_id + ')';
};

// Handle enroll student form submission
var handleEnrollStudent = function () {
    const form = document.getElementById('kt_modal_enroll_student_form');
    if (!form) return;

    const submitButton = document.getElementById('kt_modal_enroll_student_submit');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const studentId = $('#enroll_student_select').val();
        const amount = document.getElementById('enroll_amount').value;

        if (!studentId) {
            Swal.fire({
                text: 'Please select a student.',
                icon: 'warning',
                confirmButtonText: 'Ok'
            });
            return;
        }

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        $.ajax({
            url: routeEnrollStudent,
            type: 'POST',
            data: {
                student_id: studentId,
                amount: amount,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'Ok'
                    }).then(function () {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        text: response.message || 'An error occurred.',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'An error occurred.';
                Swal.fire({
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });
            },
            complete: function () {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
            }
        });
    });

    // Reset form when modal is hidden
    $('#kt_modal_enroll_student').on('hidden.bs.modal', function () {
        form.reset();
        $('#enroll_student_select').val(null).trigger('change');
        document.getElementById('enroll_amount').value = defaultFeeAmount;
    });
};

// Handle edit enrollment
var handleEditEnrollment = function () {
    const form = document.getElementById('kt_modal_edit_enrollment_form');
    if (!form) return;

    const modal = new bootstrap.Modal(document.getElementById('kt_modal_edit_enrollment'));
    const submitButton = document.getElementById('kt_modal_edit_enrollment_submit');

    // Open edit modal - Event Delegation
    document.querySelector('#kt_enrolled_students_table').addEventListener('click', function (e) {
        const button = e.target.closest('.edit-enrollment');
        if (button) {
            e.preventDefault();
            const studentId = button.getAttribute('data-student-id');
            const studentName = button.getAttribute('data-student-name');
            const amount = button.getAttribute('data-amount');

            document.getElementById('edit_student_id').value = studentId;
            document.getElementById('edit_student_name_display').textContent = studentName;
            document.getElementById('edit_amount').value = amount;

            modal.show();
        }
    });

    // Submit form
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const studentId = document.getElementById('edit_student_id').value;
        const amount = document.getElementById('edit_amount').value;

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        $.ajax({
            url: routeUpdateStudent.replace(':studentId', studentId),
            type: 'PUT',
            data: {
                amount: amount,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'Ok'
                    }).then(function () {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        text: response.message || 'An error occurred.',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'An error occurred.';
                Swal.fire({
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });
            },
            complete: function () {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
            }
        });
    });
};

// Handle withdraw student
var handleWithdrawStudent = function () {
    const form = document.getElementById('kt_modal_withdraw_student_form');
    if (!form) return;

    const modal = new bootstrap.Modal(document.getElementById('kt_modal_withdraw_student'));
    const submitButton = document.getElementById('kt_modal_withdraw_student_submit');
    const unpaidWarning = document.getElementById('unpaid_invoices_warning');
    const unpaidMessage = document.getElementById('unpaid_invoices_message');
    const forceWithdrawCheckbox = document.getElementById('confirm_force_withdraw');
    const forceWithdrawInput = document.getElementById('force_withdraw');

    // Open withdraw modal - Event Delegation
    document.querySelector('#kt_enrolled_students_table').addEventListener('click', function (e) {
        const button = e.target.closest('.withdraw-student');
        if (button) {
            e.preventDefault();
            const studentId = button.getAttribute('data-student-id');
            const studentName = button.getAttribute('data-student-name');

            document.getElementById('withdraw_student_id').value = studentId;
            document.getElementById('withdraw_student_name_display').textContent = studentName;
            
            // Reset unpaid warning state
            unpaidWarning.classList.add('d-none');
            forceWithdrawCheckbox.checked = false;
            forceWithdrawInput.value = 'false';
            submitButton.disabled = false;

            // Check for unpaid invoices
            $.ajax({
                url: routeCheckUnpaid.replace(':studentId', studentId),
                type: 'GET',
                success: function (response) {
                    if (response.success && response.has_unpaid) {
                        unpaidWarning.classList.remove('d-none');
                        unpaidMessage.innerHTML = `This student has <strong>${response.unpaid_count}</strong> unpaid Special Class Fee invoice(s) totaling <strong>৳${response.unpaid_amount.toLocaleString()}</strong>.`;
                        submitButton.disabled = true;
                    }
                }
            });

            modal.show();
        }
    });

    // Handle force withdraw checkbox
    if (forceWithdrawCheckbox) {
        forceWithdrawCheckbox.addEventListener('change', function () {
            forceWithdrawInput.value = this.checked ? 'true' : 'false';
            submitButton.disabled = !this.checked;
        });
    }

    // Submit form
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const studentId = document.getElementById('withdraw_student_id').value;
        const forceWithdraw = document.getElementById('force_withdraw').value;

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        $.ajax({
            url: routeWithdrawStudent.replace(':studentId', studentId),
            type: 'DELETE',
            data: {
                force_withdraw: forceWithdraw,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'Ok'
                    }).then(function () {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        text: response.message || 'An error occurred.',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                }
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                if (response && response.has_unpaid) {
                    unpaidWarning.classList.remove('d-none');
                    unpaidMessage.innerHTML = response.message;
                    submitButton.disabled = true;
                } else {
                    const message = response?.message || 'An error occurred.';
                    Swal.fire({
                        text: message,
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                }
            },
            complete: function () {
                submitButton.removeAttribute('data-kt-indicator');
                if (!unpaidWarning.classList.contains('d-none') && !forceWithdrawCheckbox.checked) {
                    submitButton.disabled = true;
                } else {
                    submitButton.disabled = false;
                }
            }
        });
    });

    // Reset form when modal is hidden
    $('#kt_modal_withdraw_student').on('hidden.bs.modal', function () {
        unpaidWarning.classList.add('d-none');
        forceWithdrawCheckbox.checked = false;
        forceWithdrawInput.value = 'false';
    });
};

// Handle edit secondary class
var handleEditSecondaryClass = function () {
    const form = document.getElementById('kt_modal_edit_secondary_class_form');
    if (!form) return;

    const submitButton = document.getElementById('kt_modal_edit_secondary_class_submit');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        $.ajax({
            url: routeUpdateSecondaryClass,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'Ok'
                    }).then(function () {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        text: response.message || 'An error occurred.',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'An error occurred.';
                Swal.fire({
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });
            },
            complete: function () {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;
            }
        });
    });
};

// Initialize tooltips
var initTooltips = function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
};

// Initialize Select2 for filters
var initFilterSelects = function () {
    $('select[data-kt-select2="true"]').select2({
        minimumResultsForSearch: -1
    });
};

// DOM Ready
document.addEventListener('DOMContentLoaded', function () {
    initDataTable();
    initStudentSelect();
    initFilterSelects();
    initTooltips();
    
    if (isAdminUser) {
        handleEnrollStudent();
        handleEditEnrollment();
        handleWithdrawStudent();
        handleEditSecondaryClass();
    }
});