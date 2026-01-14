"use strict";

// DataTable instances for both tables
var dataTables = {};

// Initialize DataTables for both active and inactive student tables
var initDataTables = function () {
    const tableIds = ['kt_active_students_table', 'kt_inactive_students_table'];

    tableIds.forEach(function (tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        // Determine column count based on payment type
        const hasMonthlyColumn = paymentType === 'monthly';
        const actionColumnIndex = hasMonthlyColumn ? 8 : 7;

        const isActiveTable = tableId === 'kt_active_students_table';
        const emptyMessage = isActiveTable
            ? 'No active students in this special class.'
            : 'No inactive students in this special class.';

        dataTables[tableId] = $(table).DataTable({
            info: false,
            order: [],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            columnDefs: [
                // { orderable: false, targets: isAdminUser ? [0, actionColumnIndex] : [0] }
            ],
            language: {
                emptyTable: function () {
                    let html = `
                        <div class="d-flex flex-column align-items-center justify-content-center py-10">
                            <div class="empty-state-icon mb-4">
                                <i class="ki-outline ki-people fs-3tx text-gray-300"></i>
                            </div>
                            <h4 class="text-gray-800 fw-bold mb-3">${emptyMessage}</h4>`;

                    if (isActiveTable && isAdminUser && typeof secondaryClassIsActive !== 'undefined' && secondaryClassIsActive) {
                        html += `
                            <p class="text-muted fs-6 mb-6">Start enrolling students to this special class.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_enroll_student">
                                <i class="ki-outline ki-plus fs-3 me-1"></i>Enroll First Student
                            </button>`;
                    } else if (!isActiveTable) {
                        html += `<p class="text-muted fs-6 mb-0">All enrolled students are currently active.</p>`;
                    }

                    html += `</div>`;
                    return html;
                }
            },
            drawCallback: function () {
                initTooltips();
            }
        });

        // Search functionality for each table
        const searchInput = document.querySelector(`[data-table-filter="search"][data-table-id="${tableId}"]`);
        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                dataTables[tableId].search(e.target.value).draw();
            });
        }
    });

    // Branch tab filtering for each table
    document.querySelectorAll('[data-branch-filter]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            const branchId = this.getAttribute('data-branch-filter');
            const tableId = this.getAttribute('data-table-id');
            if (tableId && dataTables[tableId]) {
                filterByBranch(tableId, branchId);
            }
        });
    });

    // Apply initial filter for first branch tab (no "All Branches" now)
    tableIds.forEach(function (tableId) {
        const firstBranchTab = document.querySelector(`#branchTabs_${tableId} .nav-link.active[data-branch-filter]`);
        if (firstBranchTab) {
            const branchId = firstBranchTab.getAttribute('data-branch-filter');
            filterByBranch(tableId, branchId);
        }
    });
};

// Store current filters for each table
var tableFilters = {
    'kt_active_students_table': { branch: null, group: null },
    'kt_inactive_students_table': { branch: null, group: null }
};

// Apply combined filters (branch + group) for specific table
var applyTableFilters = function (tableId) {
    if (!dataTables[tableId]) return;

    // Clear previous custom filters for this table
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function (fn) {
        return fn.tableId !== tableId;
    });

    var filterFn = function (settings, data, dataIndex) {
        if (settings.nTable.id !== tableId) return true;

        const row = dataTables[tableId].row(dataIndex).node();
        const filters = tableFilters[tableId];

        // Branch filter
        let branchMatch = true;
        if (filters.branch && filters.branch !== 'all') {
            branchMatch = row.getAttribute('data-branch-id') === filters.branch;
        }

        // Group filter
        let groupMatch = true;
        if (filters.group) {
            const rowGroup = row.getAttribute('data-academic-group') || '';
            groupMatch = rowGroup === filters.group;
        }

        return branchMatch && groupMatch;
    };
    filterFn.tableId = tableId;
    $.fn.dataTable.ext.search.push(filterFn);

    dataTables[tableId].draw();
};

// Filter by branch for specific table
var filterByBranch = function (tableId, branchId) {
    if (!dataTables[tableId]) return;

    tableFilters[tableId].branch = branchId;
    applyTableFilters(tableId);
};

// Initialize Select2 for student enrollment with preloaded data
var initStudentSelect = function () {
    const select = document.getElementById('enroll_student_select');
    if (!select) return;

    // Store original options for filtering
    storeOriginalOptions();

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
        const status = selectedOption.data('status');

        $('#selected_student_name').text(studentName);
        $('#selected_student_branch').text(branchName || '-');
        $('#selected_student_batch').text(batchName || '-');

        // Show status badge
        let statusBadge = '';
        if (status === 'pending') {
            statusBadge = '<span class="badge badge-light-warning ms-2">Pending</span>';
        } else if (status === 'active') {
            statusBadge = '<span class="badge badge-light-success ms-2">Active</span>';
        } else {
            statusBadge = '<span class="badge badge-light-danger ms-2">Inactive</span>';
        }
        $('#selected_student_status').html(statusBadge);
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
            filterStudentOptionsByBranch(selectedBranch);
        });
    }
};

// Store original options for branch filtering
var originalStudentOptions = [];
var storeOriginalOptions = function () {
    const select = document.getElementById('enroll_student_select');
    if (!select || originalStudentOptions.length > 0) return;

    $(select).find('option').each(function () {
        if ($(this).val() !== '') {
            originalStudentOptions.push({
                value: $(this).val(),
                text: $(this).text(),
                branchId: $(this).data('branch-id'),
                studentId: $(this).data('student-id'),
                branchName: $(this).data('branch-name'),
                batchName: $(this).data('batch-name'),
                status: $(this).data('status'),
                isPending: $(this).data('is-pending')
            });
        }
    });
};

// Filter student options by branch - show only matching branch students
var filterStudentOptionsByBranch = function (branchId) {
    const select = document.getElementById('enroll_student_select');

    // Clear current selection
    $(select).val('').trigger('change');
    $('#selected_student_info').addClass('d-none');

    // Remove all options except the placeholder
    $(select).find('option:not(:first)').remove();

    // Add back only matching options
    originalStudentOptions.forEach(function (opt) {
        if (!branchId || opt.branchId == branchId) {
            const option = new Option(opt.text, opt.value, false, false);
            $(option).attr('data-branch-id', opt.branchId);
            $(option).attr('data-student-id', opt.studentId);
            $(option).attr('data-branch-name', opt.branchName);
            $(option).attr('data-batch-name', opt.batchName);
            $(option).attr('data-status', opt.status);
            $(option).attr('data-is-pending', opt.isPending);
            $(select).append(option);
        }
    });

    // Refresh Select2
    $(select).trigger('change');
};

// Format student option in dropdown
var formatStudentOption = function (data) {
    if (!data.element) return data.text;

    const element = $(data.element);
    const status = element.data('status') || 'inactive';
    const branchName = element.data('branch-name') || '-';
    const batchName = element.data('batch-name') || '-';

    // Determine status badge based on status value
    let statusClass, statusText;
    if (status === 'pending') {
        statusClass = 'badge-light-warning';
        statusText = 'Pending';
    } else if (status === 'active') {
        statusClass = 'badge-light-success';
        statusText = 'Active';
    } else {
        statusClass = 'badge-light-danger';
        statusText = 'Inactive';
    }

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

        // Reset branch filter and restore all student options
        if (document.getElementById('enroll_branch_filter')) {
            $('#enroll_branch_filter').val('');
            // Restore all options
            filterStudentOptionsByBranch('');
        }
    });
};

// Handle toggle enrollment activation
var handleToggleActivation = function () {
    const form = document.getElementById('kt_modal_toggle_activation_form');
    if (!form) return;

    const modal = new bootstrap.Modal(document.getElementById('kt_modal_toggle_activation'));
    const submitButton = document.getElementById('kt_modal_toggle_activation_submit');
    const modalHeader = document.getElementById('toggle_modal_header');
    const modalTitle = document.getElementById('toggle_activation_modal_title');
    const submitLabel = document.getElementById('toggle_submit_label');
    const deactivateWarning = document.getElementById('toggle_deactivate_warning');
    const activateInfo = document.getElementById('toggle_activate_info');
    const unpaidWarning = document.getElementById('toggle_unpaid_warning');
    const unpaidMessage = document.getElementById('toggle_unpaid_message');

    // Open toggle modal - Event Delegation for both tables
    document.querySelectorAll('.student-table').forEach(function (table) {
        table.addEventListener('click', function (e) {
            const button = e.target.closest('.toggle-enrollment-activation');
            if (button) {
                e.preventDefault();

                const studentId = button.getAttribute('data-student-id');
                const studentName = button.getAttribute('data-student-name');
                const isActive = button.getAttribute('data-is-active') === '1';

                // Set form values
                document.getElementById('toggle_student_id').value = studentId;
                document.getElementById('toggle_is_active').value = isActive ? '1' : '0';
                document.getElementById('toggle_student_name_display').textContent = studentName;
                document.getElementById('toggle_student_name_display_activate').textContent = studentName;

                // Reset state
                unpaidWarning.classList.add('d-none');
                submitButton.disabled = false;

                if (isActive) {
                    // Deactivating - show danger styling
                    modalTitle.textContent = 'Deactivate Enrollment';
                    modalTitle.classList.remove('text-success');
                    modalTitle.classList.add('text-danger');
                    submitLabel.textContent = 'Deactivate';
                    submitButton.classList.remove('btn-success');
                    submitButton.classList.add('btn-danger');
                    deactivateWarning.classList.remove('d-none');
                    activateInfo.classList.add('d-none');

                    // Check for unpaid invoices
                    button.disabled = true;
                    const originalContent = button.innerHTML;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                    $.ajax({
                        url: routeCheckUnpaid.replace(':studentId', studentId),
                        type: 'GET',
                        success: function (response) {
                            button.disabled = false;
                            button.innerHTML = originalContent;

                            if (response.success && response.has_unpaid) {
                                // Show unpaid warning
                                deactivateWarning.classList.add('d-none');
                                unpaidWarning.classList.remove('d-none');
                                unpaidMessage.innerHTML = `This student has <strong>${response.unpaid_count}</strong> unpaid Special Class Fee invoice(s) totaling <strong>৳${response.unpaid_amount.toLocaleString()}</strong>. Please clear all dues before deactivation.`;
                                submitButton.disabled = true;
                            }
                            modal.show();
                        },
                        error: function () {
                            button.disabled = false;
                            button.innerHTML = originalContent;
                            Swal.fire({
                                text: 'Failed to check unpaid invoices.',
                                icon: 'error',
                                confirmButtonText: 'Ok'
                            });
                        }
                    });
                } else {
                    // Activating - show success styling
                    modalTitle.textContent = 'Activate Enrollment';
                    modalTitle.classList.remove('text-danger');
                    modalTitle.classList.add('text-success');
                    submitLabel.textContent = 'Activate';
                    submitButton.classList.remove('btn-danger');
                    submitButton.classList.add('btn-success');
                    deactivateWarning.classList.add('d-none');
                    activateInfo.classList.remove('d-none');
                    modal.show();
                }
            }
        });
    });

    // Submit form
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const studentId = document.getElementById('toggle_student_id').value;

        submitButton.setAttribute('data-kt-indicator', 'on');
        submitButton.disabled = true;

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
                }
            },
            error: function (xhr) {
                const response = xhr.responseJSON;
                if (response && response.has_unpaid) {
                    // Show unpaid warning in modal
                    deactivateWarning.classList.add('d-none');
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
    $('#kt_modal_toggle_activation').on('hidden.bs.modal', function () {
        unpaidWarning.classList.add('d-none');
        deactivateWarning.classList.remove('d-none');
        activateInfo.classList.add('d-none');
        submitButton.disabled = false;
    });
};

// Handle edit enrollment
var handleEditEnrollment = function () {
    const form = document.getElementById('kt_modal_edit_enrollment_form');
    if (!form) return;

    const modal = new bootstrap.Modal(document.getElementById('kt_modal_edit_enrollment'));
    const submitButton = document.getElementById('kt_modal_edit_enrollment_submit');

    // Open edit modal - Event Delegation for both tables
    document.querySelectorAll('.student-table').forEach(function (table) {
        table.addEventListener('click', function (e) {
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

// Initialize group filter handlers
var initGroupFilters = function () {
    // Apply filter button click
    document.querySelectorAll('[data-table-filter="apply"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tableId = this.getAttribute('data-table-id');
            const filterContainer = this.closest('.menu-sub-dropdown');
            const groupSelect = filterContainer.querySelector('.filter-group-select');

            if (groupSelect && tableId) {
                const selectedGroup = $(groupSelect).val();
                tableFilters[tableId].group = selectedGroup || null;
                applyTableFilters(tableId);
            }
        });
    });

    // Reset filter button click
    document.querySelectorAll('[data-table-filter="reset"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tableId = this.getAttribute('data-table-id');
            const filterContainer = this.closest('.menu-sub-dropdown');
            const groupSelect = filterContainer.querySelector('.filter-group-select');

            if (groupSelect) {
                $(groupSelect).val('').trigger('change');
            }

            if (tableId) {
                tableFilters[tableId].group = null;
                applyTableFilters(tableId);
            }
        });
    });
};

// Reinitialize DataTable when tab is shown
var initTabEvents = function () {
    const tabEl = document.querySelectorAll('#studentStatusTabs .nav-link');
    tabEl.forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (event) {
            // Adjust column widths when tab becomes visible
            Object.keys(dataTables).forEach(function (tableId) {
                dataTables[tableId].columns.adjust();
            });
        });
    });
};

// DOM Ready
document.addEventListener('DOMContentLoaded', function () {
    initDataTables();
    initStudentSelect();
    initFilterSelects();
    initGroupFilters();
    initTooltips();
    initTabEvents();

    if (isAdminUser) {
        handleEnrollStudent();
        handleToggleActivation();
        handleEditEnrollment();
        handleEditSecondaryClass();
    }
});