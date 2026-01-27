"use strict";

// DataTable instances - keyed by tableId
var dataTables = {};

// Store current filters for each table
var tableFilters = {};

// Update batch options based on selected branch
var updateBatchOptions = function (tableId, branchId) {
    const batchSelect = document.querySelector(`.filter-batch-select[data-table-id="${tableId}"]`);
    if (!batchSelect) return;

    // Clear current options
    $(batchSelect).val('').trigger('change');
    batchSelect.innerHTML = '<option value="">All Batches</option>';

    // Filter batches by branch
    const filteredBatches = allBatches.filter(function (batch) {
        return !branchId || batch.branch_id === null || batch.branch_id == branchId;
    });

    // Add filtered options
    filteredBatches.forEach(function (batch) {
        const option = document.createElement('option');
        option.value = batch.id;
        option.textContent = batch.name;
        batchSelect.appendChild(option);
    });

    // Refresh Select2 if initialized
    if ($(batchSelect).hasClass('select2-hidden-accessible')) {
        $(batchSelect).trigger('change');
    }
};

// Initialize all DataTables
var initDataTables = function () {
    // Find all student tables on the page
    document.querySelectorAll('.students-datatable').forEach(function (table) {
        const tableId = table.id;
        const branchId = table.getAttribute('data-branch-id') || '';
        const statusType = table.getAttribute('data-status-type') || 'active';

        // Initialize filter state for this table
        tableFilters[tableId] = {
            branch: branchId,
            group: '',
            batch: '',
            status: statusType
        };

        initSingleDataTable(tableId, branchId, statusType);
    });

    // Initialize filter handlers
    initFilterHandlers();

    // Initialize branch tab counts
    updateAllBranchCounts();
};

// Initialize a single DataTable
var initSingleDataTable = function (tableId, branchId, statusType) {
    const table = document.getElementById(tableId);
    if (!table) return;

    // Column configuration
    const isActiveTable = statusType === 'active';
    const emptyMessage = isActiveTable
        ? 'No active students in this special class.'
        : 'No inactive students in this special class.';

    // Column definitions
    var columns = [
        {
            data: 'index',
            orderable: false,
            searchable: false,
            className: 'pe-2',
            width: '30px'
        },
        {
            data: null,
            orderable: true,
            render: function (data, type, row) {
                const statusClass = row.is_active ? 'text-gray-800 text-hover-primary' : 'text-danger';
                const tooltip = row.is_active ? '' : 'title="Inactive Enrollment" data-bs-toggle="tooltip"';
                const studentUrl = routeStudentShow.replace(':studentId', row.student_id);
                return `
                    <div class="d-flex align-items-center">
                        <div class="d-flex flex-column text-start">
                            <a href="${studentUrl}" class="${statusClass} fw-bold mb-1" ${tooltip}>
                                ${row.name}
                            </a>
                            <span class="text-muted fs-7">${row.student_unique_id}</span>
                        </div>
                    </div>
                `;
            }
        },
        {
            data: 'academic_group',
            orderable: true,
            render: function (data) {
                if (data === 'Science') {
                    return '<span class="badge badge-light-info">Science</span>';
                } else if (data === 'Commerce') {
                    return '<span class="badge badge-light-success">Commerce</span>';
                }
                return '<span class="text-muted">-</span>';
            }
        },
        {
            data: 'batch_name',
            orderable: true,
            render: function (data) {
                return data || '<span class="text-muted">-</span>';
            }
        },
        {
            data: 'amount',
            orderable: true,
            render: function (data) {
                return `<span class="fw-bold text-primary">৳ ${Number(data).toLocaleString()}</span>`;
            }
        },
        {
            data: 'total_paid',
            orderable: false,
            render: function (data) {
                return `<span class="fw-bold text-success">৳ ${Number(data).toLocaleString()}</span>`;
            }
        },
        {
            data: 'enrolled_at',
            orderable: true
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            className: 'text-end',
            render: function (data, type, row) {
                if (!row.can_manage) {
                    return '<span class="text-muted">-</span>';
                }

                let actions = '<div class="d-flex justify-content-center gap-2">';

                // Edit button for monthly payment type
                if (row.payment_type === 'monthly') {
                    actions += `
                    <button type="button" class="btn btn-sm btn-icon btn-light-primary edit-enrollment"
                        data-student-id="${row.student_id}"
                        data-student-name="${row.name}"
                        data-amount="${row.amount}"
                        data-bs-toggle="tooltip" title="Edit Fee Amount">
                        <i class="ki-outline ki-pencil fs-5"></i>
                    </button>
                `;
                }

                // Toggle activation button
                if (row.is_active) {
                    actions += `
                    <button type="button" class="btn btn-sm btn-light-danger toggle-enrollment-activation"
                        data-student-id="${row.student_id}"
                        data-student-name="${row.name}"
                        data-is-active="1"
                        data-bs-toggle="tooltip" title="Deactivate Enrollment">
                        <i class="ki-outline ki-cross-circle fs-5 me-1"></i>
                        <span class="d-none d-md-inline">Deactivate</span>
                    </button>
                `;
                } else {
                    actions += `
                    <button type="button" class="btn btn-sm btn-light-success toggle-enrollment-activation"
                        data-student-id="${row.student_id}"
                        data-student-name="${row.name}"
                        data-is-active="0"
                        data-bs-toggle="tooltip" title="Activate Enrollment">
                        <i class="ki-outline ki-check-circle fs-5 me-1"></i>
                        <span class="d-none d-md-inline">Activate</span>
                    </button>
                `;
                }

                actions += '</div>';
                return actions;
            }
        }
    ];

    // Initialize DataTable with server-side processing
    dataTables[tableId] = $(table).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: routeEnrolledStudentsAjax,
            type: 'GET',
            data: function (d) {
                const filters = tableFilters[tableId];
                d.status_type = filters.status;
                d.branch_id = filters.branch;
                d.academic_group = filters.group;
                d.batch_id = filters.batch;
            }
        },
        columns: columns,
        order: [], // Default order by enrolled_at (column index 6) descending
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            emptyTable: function () {
                let html = `
                    <div class="d-flex flex-column align-items-center justify-content-center py-10">
                        <div class="empty-state-icon mb-4">
                            <i class="ki-outline ki-people fs-3tx text-gray-300"></i>
                        </div>
                        <h4 class="text-gray-800 fw-bold mb-3">${emptyMessage}</h4>`;

                if (isActiveTable && isAdminUser && secondaryClassIsActive) {
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
            },
            processing: '<div class="d-flex justify-content-center"><span class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></span></div>',
            zeroRecords: 'No matching students found'
        },
        drawCallback: function () {
            initTooltips();
        }
    });

    // Initialize search with debounce
    initTableSearch(tableId);

    // Initialize refresh button
    initRefreshButton(tableId);

    // Initialize Select2 for filter dropdowns
    initFilterSelect2(tableId);

    // Update branch counts after table is initialized
    const statusType2 = tableFilters[tableId].status;
    updateBranchCounts(statusType2);
};

// Initialize Select2 for filter dropdowns
var initFilterSelect2 = function (tableId) {
    const groupSelect = document.querySelector(`.filter-group-select[data-table-id="${tableId}"]`);
    const batchSelect = document.querySelector(`.filter-batch-select[data-table-id="${tableId}"]`);

    if (groupSelect && !$(groupSelect).hasClass('select2-hidden-accessible')) {
        $(groupSelect).select2({
            minimumResultsForSearch: -1,
            allowClear: true,
            placeholder: 'All Groups'
        });
    }

    if (batchSelect && !$(batchSelect).hasClass('select2-hidden-accessible')) {
        $(batchSelect).select2({
            minimumResultsForSearch: -1,
            allowClear: true,
            placeholder: 'All Batches'
        });
    }
};

// Initialize filter handlers (Apply and Reset buttons)
var initFilterHandlers = function () {
    // Apply filter button click
    document.querySelectorAll('[data-table-filter="apply"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tableId = this.getAttribute('data-table-id');
            const filterContainer = this.closest('.menu-sub-dropdown');

            if (!filterContainer || !tableId) return;

            const groupSelect = filterContainer.querySelector('.filter-group-select');
            const batchSelect = filterContainer.querySelector('.filter-batch-select');

            // Update filter state
            if (groupSelect) {
                tableFilters[tableId].group = $(groupSelect).val() || '';
            }
            if (batchSelect) {
                tableFilters[tableId].batch = $(batchSelect).val() || '';
            }

            // Reload table with new filters
            if (dataTables[tableId]) {
                dataTables[tableId].ajax.reload();
            }
        });
    });

    // Reset filter button click
    document.querySelectorAll('[data-table-filter="reset"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tableId = this.getAttribute('data-table-id');
            const filterContainer = this.closest('.menu-sub-dropdown');

            if (!filterContainer || !tableId) return;

            const groupSelect = filterContainer.querySelector('.filter-group-select');
            const batchSelect = filterContainer.querySelector('.filter-batch-select');

            // Reset select values
            if (groupSelect) {
                $(groupSelect).val('').trigger('change');
            }
            if (batchSelect) {
                $(batchSelect).val('').trigger('change');
            }

            // Reset filter state
            tableFilters[tableId].group = '';
            tableFilters[tableId].batch = '';

            // Reload table
            if (dataTables[tableId]) {
                dataTables[tableId].ajax.reload();
            }
        });
    });
};

// Initialize search functionality for a table
var initTableSearch = function (tableId) {
    const searchInput = document.querySelector(`[data-table-filter="search"][data-table-id="${tableId}"]`);
    if (!searchInput) return;

    let searchTimeout;
    searchInput.addEventListener('input', function (e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
            if (dataTables[tableId]) {
                dataTables[tableId].search(e.target.value).draw();
            }
        }, 400);
    });
};

// Initialize refresh button for a table
var initRefreshButton = function (tableId) {
    const refreshBtn = document.querySelector(`.refresh-table-btn[data-table-id="${tableId}"]`);
    if (!refreshBtn) return;

    refreshBtn.addEventListener('click', function () {
        refreshTable(tableId);
    });
};

// Refresh a specific table
var refreshTable = function (tableId) {
    if (dataTables[tableId]) {
        dataTables[tableId].ajax.reload(null, false);
        // Update branch counts for this table's status type
        const statusType = tableFilters[tableId].status;
        updateBranchCounts(statusType);
    }
};

// Refresh all tables
var refreshAllTables = function () {
    Object.keys(dataTables).forEach(function (tableId) {
        dataTables[tableId].ajax.reload(null, false);
    });
    updateAllBranchCounts();
    updateStats();
};

// Update branch counts for all status types
var updateAllBranchCounts = function () {
    updateBranchCounts('active');
    updateBranchCounts('inactive');
};

// Update branch counts in tabs
var updateBranchCounts = function (statusType) {
    // First, reset all badges for this status type to 0
    document.querySelectorAll(`.branch-count-badge[data-status-type="${statusType}"]`).forEach(function (badge) {
        badge.textContent = '0';
        badge.classList.remove('badge-loading');
    });

    $.ajax({
        url: routeBranchCountsAjax,
        type: 'GET',
        data: { status_type: statusType },
        success: function (response) {
            if (response.success) {
                // Update all branch count badges for this status type
                document.querySelectorAll(`.branch-count-badge[data-status-type="${statusType}"]`).forEach(function (badge) {
                    const branchId = badge.getAttribute('data-branch-id');
                    const count = response.counts[branchId] || 0;
                    badge.textContent = count;
                    badge.classList.remove('badge-loading');
                });
            }
        },
        error: function () {
            // Remove loading state on error
            document.querySelectorAll(`.branch-count-badge[data-status-type="${statusType}"]`).forEach(function (badge) {
                badge.classList.remove('badge-loading');
            });
        }
    });
};

// Update stats via AJAX
var updateStats = function () {
    $.ajax({
        url: routeStatsAjax,
        type: 'GET',
        success: function (response) {
            if (response.success) {
                const stats = response.stats;

                // Update main stats
                $('#stat_total_students').text(stats.total_students);
                $('#stat_active_students').text(stats.active_students);
                $('#stat_inactive_students').text(stats.inactive_students);
                $('#stat_total_revenue').text('৳ ' + Number(stats.total_revenue).toLocaleString());

                if (paymentType === 'monthly') {
                    $('#stat_expected_monthly').text('৳ ' + Number(stats.expected_monthly_revenue).toLocaleString());
                }

                // Update branch stats in sidebar
                if (stats.branch_stats) {
                    Object.keys(stats.branch_stats).forEach(function (branchId) {
                        const branchStat = stats.branch_stats[branchId];
                        const branchItem = $(`.branch-stat-item[data-branch-id="${branchId}"]`);
                        if (branchItem.length) {
                            branchItem.find('.branch-total-count').text(branchStat.total);
                            branchItem.find('.branch-active-count').text(branchStat.active);
                            branchItem.find('.branch-inactive-count').text(branchStat.inactive);
                            branchItem.find('.branch-revenue').text(Number(branchStat.revenue).toLocaleString());
                        }
                    });
                }
            }
        }
    });
};

// Refresh available students for enrollment modal
var refreshAvailableStudents = function () {
    $.ajax({
        url: routeAvailableStudents,
        type: 'GET',
        success: function (response) {
            if (response.success) {
                const select = document.getElementById('enroll_student_select');
                if (!select) return;

                // Clear current options except placeholder
                $(select).find('option:not(:first)').remove();

                // Reset stored options
                originalStudentOptions = [];

                // Add new options
                response.data.forEach(function (student) {
                    const option = new Option(
                        `${student.name} (${student.student_unique_id})`,
                        student.id,
                        false,
                        false
                    );
                    $(option).attr('data-branch-id', student.branch_id);
                    $(option).attr('data-student-id', student.student_unique_id);
                    $(option).attr('data-branch-name', student.branch_name);
                    $(option).attr('data-batch-name', student.batch_name);
                    $(option).attr('data-status', student.status);
                    $(option).attr('data-is-pending', student.is_pending ? '1' : '0');
                    $(select).append(option);

                    // Store for filtering
                    originalStudentOptions.push({
                        value: student.id,
                        text: `${student.name} (${student.student_unique_id})`,
                        branchId: student.branch_id,
                        studentId: student.student_unique_id,
                        branchName: student.branch_name,
                        batchName: student.batch_name,
                        status: student.status,
                        isPending: student.is_pending
                    });
                });

                // Trigger change to refresh Select2
                $(select).trigger('change');
            }
        }
    });
};

// Initialize Select2 for student enrollment with preloaded data
var originalStudentOptions = [];

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
        $(branchFilter).select2({
            minimumResultsForSearch: -1,
            allowClear: true,
            placeholder: 'All Branches'
        }).on('change', function () {
            const selectedBranch = $(this).val();
            filterStudentOptionsByBranch(selectedBranch);
        });
    }
};

// Store original options for branch filtering
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

// Filter student options by branch
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

    // Determine status badge
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
    const modalElement = document.getElementById('kt_modal_enroll_student');

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
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;

                if (response.success) {
                    // Get modal instance and hide it
                    let modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (!modalInstance) {
                        modalInstance = new bootstrap.Modal(modalElement);
                    }
                    modalInstance.hide();

                    // Reset form after modal starts hiding
                    setTimeout(function () {
                        form.reset();
                        $('#enroll_student_select').val('').trigger('change');
                        $('#selected_student_info').addClass('d-none');
                        if (document.getElementById('enroll_amount')) {
                            document.getElementById('enroll_amount').value = defaultFeeAmount;
                        }
                        if (document.getElementById('enroll_branch_filter')) {
                            $('#enroll_branch_filter').val('').trigger('change');
                            filterStudentOptionsByBranch('');
                        }
                    }, 50);

                    // Show success message
                    toastr.success(response.message);

                    // Refresh data via AJAX
                    refreshAllTables();
                    refreshAvailableStudents();
                } else {
                    Swal.fire({
                        text: response.message || 'An error occurred.',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                }
            },
            error: function (xhr) {
                submitButton.removeAttribute('data-kt-indicator');
                submitButton.disabled = false;

                const message = xhr.responseJSON?.message || 'An error occurred.';
                Swal.fire({
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });
            }
        });
    });

    // Reset form when modal is hidden
    $('#kt_modal_enroll_student').on('hidden.bs.modal', function () {
        submitButton.removeAttribute('data-kt-indicator');
        submitButton.disabled = false;
        form.reset();
        $('#enroll_student_select').val('').trigger('change');
        $('#selected_student_info').addClass('d-none');
        if (document.getElementById('enroll_amount')) {
            document.getElementById('enroll_amount').value = defaultFeeAmount;
        }
        if (document.getElementById('enroll_branch_filter')) {
            $('#enroll_branch_filter').val('').trigger('change');
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
    const modalTitle = document.getElementById('toggle_activation_modal_title');
    const submitLabel = document.getElementById('toggle_submit_label');
    const deactivateWarning = document.getElementById('toggle_deactivate_warning');
    const activateInfo = document.getElementById('toggle_activate_info');
    const unpaidWarning = document.getElementById('toggle_unpaid_warning');
    const unpaidMessage = document.getElementById('toggle_unpaid_message');

    // Open toggle modal - Event Delegation for all tables
    $(document).on('click', '.toggle-enrollment-activation', function (e) {
        e.preventDefault();
        const button = $(this);

        const studentId = button.attr('data-student-id');
        const studentName = button.attr('data-student-name');
        const isActive = button.attr('data-is-active') === '1';

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
            button.prop('disabled', true);
            const originalContent = button.html();
            button.html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: routeCheckUnpaid.replace(':studentId', studentId),
                type: 'GET',
                success: function (response) {
                    button.prop('disabled', false);
                    button.html(originalContent);

                    if (response.success && response.has_unpaid) {
                        // Show unpaid warning
                        deactivateWarning.classList.add('d-none');
                        unpaidWarning.classList.remove('d-none');
                        unpaidMessage.innerHTML = `This student has <strong>${response.unpaid_count}</strong> unpaid Special Class Fee invoice(s) totaling <strong>৳ ${response.unpaid_amount.toLocaleString()}</strong>. Please clear all dues before deactivation.`;
                        submitButton.disabled = true;
                    }

                    modal.show();
                },
                error: function () {
                    button.prop('disabled', false);
                    button.html(originalContent);
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
                    toastr.success(response.message);
                    modal.hide();

                    // Refresh all tables and update counts
                    refreshAllTables();
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

    // Open edit modal - Event Delegation for all tables
    $(document).on('click', '.edit-enrollment', function (e) {
        e.preventDefault();
        const button = $(this);

        const studentId = button.attr('data-student-id');
        const studentName = button.attr('data-student-name');
        const amount = button.attr('data-amount');

        document.getElementById('edit_student_id').value = studentId;
        document.getElementById('edit_student_name_display').textContent = studentName;
        document.getElementById('edit_amount').value = amount;

        modal.show();
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
                    toastr.success(response.message);
                    modal.hide();
                    refreshAllTables();
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
    const modal = bootstrap.Modal.getInstance(document.getElementById('kt_modal_edit_secondary_class')) ||
        new bootstrap.Modal(document.getElementById('kt_modal_edit_secondary_class'));

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
                    toastr.success(response.message);
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
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
        var existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (existingTooltip) {
            existingTooltip.dispose();
        }
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
};

// Handle tab visibility for DataTable column adjustment
var initTabEvents = function () {
    // Main status tabs (Active/Inactive)
    document.querySelectorAll('#studentStatusTabs .nav-link').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (event) {
            // Adjust all visible table columns
            Object.keys(dataTables).forEach(function (tableId) {
                if (dataTables[tableId]) {
                    dataTables[tableId].columns.adjust();
                }
            });
        });
    });

    // Branch tabs within each status tab
    document.querySelectorAll('#activeBranchTabs .nav-link, #inactiveBranchTabs .nav-link').forEach(function (tab) {
        tab.addEventListener('shown.bs.tab', function (event) {
            Object.keys(dataTables).forEach(function (tableId) {
                if (dataTables[tableId]) {
                    dataTables[tableId].columns.adjust();
                }
            });
        });
    });
};

// DOM Ready
document.addEventListener('DOMContentLoaded', function () {
    initDataTables();
    initStudentSelect();
    initTooltips();
    initTabEvents();

    if (isAdminUser) {
        handleEnrollStudent();
        handleToggleActivation();
        handleEditEnrollment();
        handleEditSecondaryClass();
    }
});