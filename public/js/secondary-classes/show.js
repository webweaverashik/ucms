"use strict";

// DataTable instance
var dataTable;

// Initialize DataTable
var initDataTable = function () {
    const table = document.getElementById('kt_enrolled_students_table');
    
    if (!table) return;

    // Determine column count based on payment type
    const hasMonthlyColumn = paymentType === 'monthly';
    const actionColumnIndex = hasMonthlyColumn ? 9 : 8;

    dataTable = $(table).DataTable({
        info: false,
        order: [],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        columnDefs: [
            { orderable: false, targets: isAdminUser ? [0, actionColumnIndex] : [0] }
        ],
        language: {
            emptyTable: function() {
                let html = `
                    <div class="d-flex flex-column align-items-center justify-content-center py-10">
                        <div class="empty-state-icon mb-4">
                            <i class="ki-outline ki-people fs-3tx text-gray-300"></i>
                        </div>
                        <h4 class="text-gray-800 fw-bold mb-3">No Students Enrolled</h4>
                        <p class="text-muted fs-6 mb-6">
                            Start enrolling students to this special class.
                        </p>`;
                
                if (isAdminUser && typeof secondaryClassIsActive !== 'undefined' && secondaryClassIsActive) {
                    html += `
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_enroll_student">
                            <i class="ki-outline ki-plus fs-3 me-1"></i>Enroll First Student
                        </button>`;
                }
                
                html += `</div>`;
                return html;
            }
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
            // Remove custom filter
            $.fn.dataTable.ext.search = [];
            dataTable.draw();
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

    // Clear previous filters
    $.fn.dataTable.ext.search = [];

    // Custom filtering
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const row = dataTable.row(dataIndex).node();
        const rowStatus = row.getAttribute('data-enrollment-status');
        const rowBranch = row.getAttribute('data-branch-id');

        let statusMatch = !status || rowStatus === status;
        let branchMatch = !branch || rowBranch === branch;

        return statusMatch && branchMatch;
    });

    dataTable.draw();
};

// Filter by branch (for tab clicks)
var filterByBranch = function (branchId) {
    $.fn.dataTable.ext.search = [];
    
    if (branchId !== 'all') {
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            const row = dataTable.row(dataIndex).node();
            return row.getAttribute('data-branch-id') === branchId;
        });
    }
    
    dataTable.draw();
};

// Initialize Select2 for student enrollment with preloaded data
var initStudentSelect = function () {
    const select = document.getElementById('enroll_student_select');
    if (!select) return;

    // Initialize Select2 with preloaded options
    $(select).select2({
        dropdownParent: $('#kt_modal_enroll_student'),
        placeholder: 'Select a student...',
        allowClear: true,
        templateResult: formatStudentOption,
        templateSelection: formatStudentSelection
    });

    // Handle student selection
    $(select).on('select2:select', function (e) {
        const selectedOption = $(this).find(':selected');
        const studentName = selectedOption.text();
        const branchName = selectedOption.data('branch-name');
        const batchName = selectedOption.data('batch-name');

        $('#selected_student_name').text(studentName);
        $('#selected_student_branch').text(branchName || '-');
        $('#selected_student_batch').text(batchName || '-');
        $('#selected_student_info').removeClass('d-none');
    });

    $(select).on('select2:clear', function () {
        $('#selected_student_info').addClass('d-none');
    });

    // Branch filter for enrollment modal
    const branchFilter = document.getElementById('enroll_branch_filter');
    if (branchFilter) {
        $(branchFilter).on('change', function () {
            const selectedBranch = $(this).val();
            filterStudentOptions(selectedBranch);
        });
    }
};

// Filter student options by branch
var filterStudentOptions = function (branchId) {
    const select = document.getElementById('enroll_student_select');
    const options = $(select).find('option');
    
    options.each(function () {
        const optionBranchId = $(this).data('branch-id');
        if (!branchId || optionBranchId == branchId || $(this).val() === '') {
            $(this).prop('disabled', false);
        } else {
            $(this).prop('disabled', true);
        }
    });

    // Clear current selection if it doesn't match filter
    const currentSelection = $(select).val();
    if (currentSelection) {
        const selectedOption = $(select).find('option[value="' + currentSelection + '"]');
        if (selectedOption.data('branch-id') != branchId && branchId) {
            $(select).val('').trigger('change');
            $('#selected_student_info').addClass('d-none');
        }
    }

    // Refresh Select2
    $(select).select2('destroy');
    initStudentSelect();
};

// Format student option in dropdown
var formatStudentOption = function (data) {
    if (!data.element) return data.text;
    
    const element = $(data.element);
    const isActive = element.data('is-active') === 1;
    const branchName = element.data('branch-name') || '-';
    const batchName = element.data('batch-name') || '-';
    const studentId = element.data('student-id') || '';
    const statusClass = isActive ? 'badge-light-success' : 'badge-light-danger';
    const statusText = isActive ? 'Active' : 'Inactive';

    return $(`
        <div class="d-flex align-items-center">
            <div class="d-flex flex-column">
                <span class="fw-bold">${data.text}</span>
                <span class="text-muted fs-7">${branchName} | ${batchName}</span>
            </div>
            <span class="badge ${statusClass} ms-auto">${statusText}</span>
        </div>
    `);
};

// Format student selection
var formatStudentSelection = function (data) {
    return data.text;
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
        $('#enroll_student_select').val('').trigger('change');
        $('#selected_student_info').addClass('d-none');
        document.getElementById('enroll_amount').value = defaultFeeAmount;
        if (document.getElementById('enroll_branch_filter')) {
            $('#enroll_branch_filter').val('').trigger('change');
        }
    });
};

// Handle toggle enrollment activation
var handleToggleActivation = function () {
    document.querySelector('#kt_enrolled_students_table').addEventListener('click', function (e) {
        const button = e.target.closest('.toggle-enrollment-activation');
        if (button) {
            e.preventDefault();
            const studentId = button.getAttribute('data-student-id');
            const studentName = button.getAttribute('data-student-name');
            const isActive = button.getAttribute('data-is-active') === '1';
            const actionText = isActive ? 'deactivate' : 'activate';

            Swal.fire({
                title: `${isActive ? 'Deactivate' : 'Activate'} Enrollment?`,
                text: `Are you sure you want to ${actionText} the enrollment for ${studentName}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: `Yes, ${actionText}`,
                cancelButtonText: 'Cancel',
                confirmButtonColor: isActive ? '#f1416c' : '#50cd89'
            }).then(function (result) {
                if (result.isConfirmed) {
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                    $.ajax({
                        url: routeToggleActivation.replace(':studentId', studentId),
                        type: 'POST',
                        data: {
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
                                button.disabled = false;
                                button.innerHTML = `<i class="ki-outline ${isActive ? 'ki-cross-circle' : 'ki-check-circle'} fs-5"></i>`;
                            }
                        },
                        error: function (xhr) {
                            const message = xhr.responseJSON?.message || 'An error occurred.';
                            Swal.fire({
                                text: message,
                                icon: 'error',
                                confirmButtonText: 'Ok'
                            });
                            button.disabled = false;
                            button.innerHTML = `<i class="ki-outline ${isActive ? 'ki-cross-circle' : 'ki-check-circle'} fs-5"></i>`;
                        }
                    });
                }
            });
        }
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

// Handle withdraw student (no force withdraw)
var handleWithdrawStudent = function () {
    const form = document.getElementById('kt_modal_withdraw_student_form');
    if (!form) return;

    const modal = new bootstrap.Modal(document.getElementById('kt_modal_withdraw_student'));
    const submitButton = document.getElementById('kt_modal_withdraw_student_submit');
    const unpaidWarning = document.getElementById('unpaid_invoices_warning');
    const unpaidMessage = document.getElementById('unpaid_invoices_message');

    // Open withdraw modal - Event Delegation
    document.querySelector('#kt_enrolled_students_table').addEventListener('click', function (e) {
        const button = e.target.closest('.withdraw-student');
        if (button) {
            e.preventDefault();
            const studentId = button.getAttribute('data-student-id');
            const studentName = button.getAttribute('data-student-name');

            document.getElementById('withdraw_student_id').value = studentId;
            document.getElementById('withdraw_student_name_display').textContent = studentName;
            
            // Reset state
            unpaidWarning.classList.add('d-none');
            submitButton.disabled = false;

            // Check for unpaid invoices
            $.ajax({
                url: routeCheckUnpaid.replace(':studentId', studentId),
                type: 'GET',
                success: function (response) {
                    if (response.success && response.has_unpaid) {
                        unpaidWarning.classList.remove('d-none');
                        unpaidMessage.innerHTML = `This student has <strong>${response.unpaid_count}</strong> unpaid Special Class Fee invoice(s) totaling <strong>৳${response.unpaid_amount.toLocaleString()}</strong>. Please clear all dues before withdrawal.`;
                        submitButton.disabled = true;
                    }
                }
            });

            modal.show();
        }
    });

    // Submit form
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const studentId = document.getElementById('withdraw_student_id').value;

        // Don't allow submission if there are unpaid invoices
        if (!unpaidWarning.classList.contains('d-none')) {
            Swal.fire({
                text: 'Please clear all unpaid invoices before withdrawal.',
                icon: 'warning',
                confirmButtonText: 'Ok'
            });
            return;
        }

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

        $.ajax({
            url: routeWithdrawStudent.replace(':studentId', studentId),
            type: 'DELETE',
            data: {
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
                if (!unpaidWarning.classList.contains('d-none')) {
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
        submitButton.disabled = false;
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
        // Dispose existing tooltip first
        var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
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
        handleToggleActivation();
        handleEditEnrollment();
        handleWithdrawStudent();
        handleEditSecondaryClass();
    }
});