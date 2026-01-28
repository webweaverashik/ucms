"use strict";

/**
 * KTStudentAttendance
 * Metronic 8 Style Module for Student Attendance Management
 *
 * @package App/Student/Attendance
 * @author Your Name
 */

var KTStudentAttendance = function () {
    // ============================================
    // Private Variables
    // ============================================
    var config = window.AttendanceConfig || {};
    var routes = config.routes || {};
    var csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
    var isAdmin = config.isAdmin ?? true;
    var userBranchId = config.userBranchId;

    // DOM Elements
    var form;
    var branchSelect;
    var classSelect;
    var batchSelect;
    var batchLoader;
    var studentListLoader;
    var studentListContainer;
    var offDayWarning;
    var bulkButtons;
    var saveSection;
    var saveButton;
    var resetButton;
    var studentCountEl;

    // ============================================
    // Private Functions
    // ============================================

    /**
     * Make AJAX Request using Fetch API
     */
    var makeRequest = function (url, options = {}) {
        var defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        var mergedOptions = Object.assign({}, defaultOptions, options);

        if (mergedOptions.body && typeof mergedOptions.body === 'object') {
            mergedOptions.body = JSON.stringify(mergedOptions.body);
        }

        return fetch(url, mergedOptions)
            .then(function (response) {
                if (!response.ok) {
                    return response.json().catch(function () {
                        return { message: 'An error occurred' };
                    }).then(function (error) {
                        throw new Error(error.message || 'HTTP error! status: ' + response.status);
                    });
                }
                return response.json();
            });
    };

    /**
     * Show SweetAlert Notification
     */
    var showAlert = function (type, title, text, options) {
        options = options || {};

        if (typeof Swal !== 'undefined') {
            return Swal.fire(Object.assign({
                icon: type,
                title: title,
                text: text,
                buttonsStyling: false,
                confirmButtonText: 'Ok',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            }, options));
        } else {
            alert(title + ': ' + text);
            return Promise.resolve();
        }
    };

    /**
     * Reinitialize Select2 for element
     */
    var reinitSelect2 = function (element) {
        if (!element) return;

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            var $el = jQuery(element);
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            $el.select2({
                placeholder: element.dataset.placeholder || 'Select an option',
                allowClear: true,
                minimumResultsForSearch: element.dataset.hideSearch === 'true' ? Infinity : 0
            });
        }
    };

    /**
     * Toggle Element Visibility
     */
    var toggleElement = function (element, show) {
        if (!element) return;

        if (show) {
            element.classList.remove('d-none');
        } else {
            element.classList.add('d-none');
        }
    };

    /**
     * Set Button Loading State
     */
    var setButtonLoading = function (button, loading, originalHtml) {
        if (!button) return;

        if (loading) {
            button.disabled = true;
            button.setAttribute('data-kt-indicator', 'on');
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        } else {
            button.disabled = false;
            button.removeAttribute('data-kt-indicator');
            button.innerHTML = originalHtml;
        }
    };

    /**
     * Escape HTML to prevent XSS
     */
    var escapeHtml = function (text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    // ============================================
    // Batch Loading
    // ============================================

    /**
     * Load Batches via AJAX based on Branch
     */
    var loadBatches = function (branchId) {
        // Reset batch dropdown
        batchSelect.innerHTML = '<option value="">Loading...</option>';
        batchSelect.disabled = true;
        toggleElement(batchLoader, true);

        if (!branchId) {
            batchSelect.innerHTML = '<option value="">Select branch first</option>';
            batchSelect.disabled = false;
            toggleElement(batchLoader, false);
            reinitSelect2(batchSelect);
            return Promise.resolve();
        }

        var url = routes.getBatches.replace(':branchId', branchId);

        return makeRequest(url)
            .then(function (response) {
                batchSelect.innerHTML = '<option value="">Select batch</option>';

                if (response.batches && response.batches.length > 0) {
                    response.batches.forEach(function (batch) {
                        var option = document.createElement('option');
                        option.value = batch.id;
                        option.textContent = batch.name;
                        option.dataset.dayOff = batch.day_off || '';
                        batchSelect.appendChild(option);
                    });
                } else {
                    batchSelect.innerHTML = '<option value="">No batches available</option>';
                }
            })
            .catch(function (error) {
                console.error('Error loading batches:', error);
                batchSelect.innerHTML = '<option value="">Error loading batches</option>';
                showAlert('error', 'Error', 'Failed to load batches. Please try again.');
            })
            .finally(function () {
                batchSelect.disabled = false;
                toggleElement(batchLoader, false);
                reinitSelect2(batchSelect);
            });
    };

    // ============================================
    // Student Operations
    // ============================================

    /**
     * Fetch Students based on filters
     */
    var fetchStudents = function () {
        var branchId = branchSelect.value;
        var classId = classSelect.value;
        var batchId = batchSelect.value;
        var attendanceDate = document.getElementById('attendance_date').value;

        // Validation
        if (!branchId || !classId || !batchId) {
            showAlert('warning', 'Validation Error', 'Please select Branch, Class, and Batch.');
            return;
        }

        // Show loader, hide content
        toggleElement(studentListLoader, true);
        studentListContainer.innerHTML = '';
        toggleElement(saveSection, false);
        toggleElement(bulkButtons, false);
        offDayWarning.innerHTML = '';

        var payload = {
            branch_id: branchId,
            class_id: classId,
            batch_id: batchId,
            attendance_date: attendanceDate
        };

        makeRequest(routes.getStudents, {
            method: 'POST',
            body: payload
        })
            .then(function (response) {
                toggleElement(studentListLoader, false);

                // Handle Off Day Warning
                if (response.is_off_day) {
                    renderOffDayWarning(response.off_day_name);
                }

                // Handle Student List
                if (response.count > 0) {
                    renderStudentTable(response.students);
                    toggleElement(bulkButtons, true);
                    toggleElement(saveSection, true);
                    studentCountEl.textContent = response.count;

                    // Reset bulk action radios
                    document.querySelectorAll('input[name="mark_all"]').forEach(function (radio) {
                        radio.checked = false;
                    });
                } else {
                    studentListContainer.innerHTML =
                        '<div class="alert alert-info d-flex align-items-center">' +
                        '<i class="ki-outline ki-information fs-2 text-info me-3"></i>' +
                        '<span>No students found for this criteria.</span>' +
                        '</div>';
                }
            })
            .catch(function (error) {
                toggleElement(studentListLoader, false);
                console.error('Error fetching students:', error);
                showAlert('error', 'Error', error.message || 'Something went wrong fetching students.');
            });
    };

    /**
     * Render Off Day Warning
     */
    var renderOffDayWarning = function (dayName) {
        offDayWarning.innerHTML =
            '<div class="alert alert-dismissible alert-warning d-flex align-items-center p-5 mb-5 border border-warning border-dashed fade-in">' +
            '<i class="ki-outline ki-information-5 fs-2hx text-warning me-4"></i>' +
            '<div class="d-flex flex-column">' +
            '<h4 class="mb-1 text-warning">Off Day Warning</h4>' +
            '<span class="fs-7">' +
            'Today (<strong>' + escapeHtml(dayName) + '</strong>) is the official off-day for this batch. ' +
            'However, you may still proceed to take attendance.' +
            '</span>' +
            '</div>' +
            '<button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">' +
            '<i class="ki-outline ki-cross fs-1 text-warning"></i>' +
            '</button>' +
            '</div>';
    };

    /**
     * Render Student Table
     */
    var renderStudentTable = function (students) {
        var html =
            '<div class="table-responsive fade-in">' +
            '<table class="table table-row-bordered table-row-gray-300 align-middle gs-0 gy-4" id="attendance_table">' +
            '<thead>' +
            '<tr class="fw-bold text-muted bg-light">' +
            '<th class="ps-4 w-50px rounded-start">#</th>' +
            '<th class="min-w-200px">Student Info</th>' +
            '<th class="text-center min-w-300px">Attendance Status</th>' +
            '<th class="min-w-150px">Remarks</th>' +
            '<th class="text-center min-w-100px">Updated At</th>' +
            '<th class="min-w-150px pe-4 rounded-end">Attendance Taker</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>';

        students.forEach(function (student, index) {
            var presentChecked = student.status === 'present' ? 'checked' : '';
            var lateChecked = student.status === 'late' ? 'checked' : '';
            var absentChecked = student.status === 'absent' ? 'checked' : '';
            var initials = student.name.charAt(0).toUpperCase();
            var remarks = escapeHtml(student.remarks || '');
            var updatedAt = student.updated_at || '-';
            var attendanceTaker = student.attendance_taker || '-';
            var hasAttendance = student.has_attendance;

            html +=
                '<tr data-student-id="' + student.id + '" class="attendance-row" data-has-attendance="' + (hasAttendance ? 'true' : 'false') + '">' +
                '<td class="ps-4 fw-bold text-gray-600">' + (index + 1) + '</td>' +
                '<td>' +
                '<div class="d-flex align-items-center">' +
                '<div class="symbol symbol-45px me-3">' +
                '<span class="symbol-label bg-light-primary text-primary fw-bold">' + escapeHtml(initials) + '</span>' +
                '</div>' +
                '<div class="d-flex flex-column">' +
                '<span class="text-gray-800 fw-bold fs-6">' + escapeHtml(student.name) + '</span>' +
                '<span class="text-muted fw-semibold fs-7">ID: ' + escapeHtml(student.student_unique_id) + '</span>' +
                '</div>' +
                '</div>' +
                '</td>' +
                '<td>' +
                '<div class="d-flex justify-content-center gap-2">' +
                '<label class="status-option present-option">' +
                '<input class="status-radio" type="radio" value="present" name="status_' + student.id + '" ' + presentChecked + '>' +
                '<span class="status-dot"></span>' +
                '<span>Present</span>' +
                '</label>' +
                '<label class="status-option late-option">' +
                '<input class="status-radio" type="radio" value="late" name="status_' + student.id + '" ' + lateChecked + '>' +
                '<span class="status-dot"></span>' +
                '<span>Late</span>' +
                '</label>' +
                '<label class="status-option absent-option">' +
                '<input class="status-radio" type="radio" value="absent" name="status_' + student.id + '" ' + absentChecked + '>' +
                '<span class="status-dot"></span>' +
                '<span>Absent</span>' +
                '</label>' +
                '</div>' +
                '</td>' +
                '<td>' +
                '<input type="text" class="form-control form-control-solid form-control-sm remarks-input" ' +
                'placeholder="Add remarks" value="' + remarks + '">' +
                '</td>' +
                '<td class="text-center">' +
                '<span class="updated-at-display badge badge-light-primary fs-7">' +
                '<i class="ki-outline ki-time fs-7 me-1"></i>' + escapeHtml(updatedAt) +
                '</span>' +
                '</td>' +
                '<td class="pe-4">' +
                '<div class="attendance-taker-display d-flex align-items-center">' +
                '<div class="symbol symbol-30px me-2">' +
                '<span class="symbol-label bg-light-info text-info fs-8 fw-bold">' +
                (attendanceTaker !== '-' ? escapeHtml(attendanceTaker.charAt(0).toUpperCase()) : '-') +
                '</span>' +
                '</div>' +
                '<span class="text-gray-700 fs-7 fw-semibold attendance-taker-name">' + escapeHtml(attendanceTaker) + '</span>' +
                '</div>' +
                '</td>' +
                '</tr>';
        });

        html += '</tbody></table></div>';

        studentListContainer.innerHTML = html;
    };

    /**
     * Refresh student list after save to get accurate timestamps
     */
    var refreshStudentList = function () {
        var branchId = branchSelect.value;
        var classId = classSelect.value;
        var batchId = batchSelect.value;
        var attendanceDate = document.getElementById('attendance_date').value;

        var payload = {
            branch_id: branchId,
            class_id: classId,
            batch_id: batchId,
            attendance_date: attendanceDate
        };

        return makeRequest(routes.getStudents, {
            method: 'POST',
            body: payload
        })
            .then(function (response) {
                if (response.count > 0) {
                    renderStudentTable(response.students);
                    studentCountEl.textContent = response.count;
                }
            })
            .catch(function (error) {
                console.error('Error refreshing student list:', error);
            });
    };

    /**
     * Save Attendance
     */
    var saveAttendance = function () {
        var attendanceData = [];
        var hasValidationError = false;

        var rows = document.querySelectorAll('#attendance_table tbody tr');

        // Collect and validate data
        rows.forEach(function (row) {
            var studentId = row.dataset.studentId;
            var statusInput = row.querySelector('input[name="status_' + studentId + '"]:checked');
            var remarksInput = row.querySelector('.remarks-input');

            if (!statusInput) {
                hasValidationError = true;
                row.classList.add('bg-light-danger');
            } else {
                row.classList.remove('bg-light-danger');
                attendanceData.push({
                    student_id: studentId,
                    status: statusInput.value,
                    remarks: remarksInput ? remarksInput.value : ''
                });
            }
        });

        if (hasValidationError) {
            showAlert('error', 'Incomplete Attendance', 'Please select a status (Present, Late, or Absent) for all students highlighted in red.');
            return;
        }

        // Prepare payload
        var payload = {
            attendance_date: document.getElementById('attendance_date').value,
            branch_id: branchSelect.value,
            class_id: classSelect.value,
            batch_id: batchSelect.value,
            attendances: attendanceData
        };

        // Set loading state
        var originalHtml = saveButton.innerHTML;
        setButtonLoading(saveButton, true, originalHtml);

        makeRequest(routes.storeBulk, {
            method: 'POST',
            body: payload
        })
            .then(function (response) {
                showAlert('success', 'Saved!', response.message || 'Attendance saved successfully!', {
                    timer: 2000,
                    showConfirmButton: false
                });

                // Refresh student list to get accurate individual timestamps
                refreshStudentList();
            })
            .catch(function (error) {
                console.error('Error saving attendance:', error);
                showAlert('error', 'Error', error.message || 'An error occurred while saving attendance.');
            })
            .finally(function () {
                setButtonLoading(saveButton, false, originalHtml);
            });
    };

    /**
     * Reset Form
     */
    var resetForm = function () {
        // Reset dropdowns
        if (isAdmin) {
            branchSelect.value = '';
            reinitSelect2(branchSelect);
        }

        classSelect.value = '';
        batchSelect.innerHTML = '<option value="">Select branch first</option>';

        reinitSelect2(classSelect);
        reinitSelect2(batchSelect);

        // Clear containers
        studentListContainer.innerHTML = '';
        offDayWarning.innerHTML = '';

        toggleElement(saveSection, false);
        toggleElement(bulkButtons, false);

        // Reset bulk action radios
        document.querySelectorAll('input[name="mark_all"]').forEach(function (radio) {
            radio.checked = false;
        });
    };

    /**
     * Handle Bulk Action
     */
    var handleBulkAction = function (status) {
        document.querySelectorAll('.status-radio[value="' + status + '"]').forEach(function (radio) {
            radio.checked = true;
        });

        // Clear any error highlighting
        document.querySelectorAll('.attendance-row').forEach(function (row) {
            row.classList.remove('bg-light-danger');
        });
    };

    /**
     * Clear Student List
     */
    var clearStudentList = function () {
        studentListContainer.innerHTML = '';
        toggleElement(saveSection, false);
        toggleElement(bulkButtons, false);
        offDayWarning.innerHTML = '';
    };

    // ============================================
    // Event Handlers
    // ============================================

    /**
     * Handle Branch Change
     */
    var handleBranchChange = function () {
        var branchId = branchSelect.value;
        loadBatches(branchId);
        clearStudentList();
    };

    /**
     * Handle Form Submit
     */
    var handleFormSubmit = function (e) {
        e.preventDefault();
        fetchStudents();
    };

    /**
     * Handle Bulk Action Change
     */
    var handleBulkActionChange = function (e) {
        handleBulkAction(e.target.value);
    };

    // ============================================
    // Initialization
    // ============================================

    /**
     * Initialize DOM Elements
     */
    var initElements = function () {
        form = document.getElementById('student_list_filter_form');
        branchSelect = document.getElementById('branch_id');
        classSelect = document.getElementById('class_id');
        batchSelect = document.getElementById('batch_id');
        batchLoader = document.getElementById('batch_loader');
        studentListLoader = document.getElementById('student_list_loader');
        studentListContainer = document.getElementById('student_list_container');
        offDayWarning = document.getElementById('off_day_warning');
        bulkButtons = document.getElementById('bulk_buttons');
        saveSection = document.getElementById('save_attendance_section');
        saveButton = document.getElementById('save_attendance_button');
        resetButton = document.getElementById('reset_button');
        studentCountEl = document.getElementById('student_count');
    };

    /**
     * Initialize Event Listeners
     */
    var initEventListeners = function () {
        // Branch change - Load batches via AJAX
        if (branchSelect) {
            // For Select2
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                jQuery(branchSelect).on('select2:select select2:clear', handleBranchChange);
            }
            // Native fallback
            branchSelect.addEventListener('change', handleBranchChange);
        }

        // Form submit
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }

        // Reset button
        if (resetButton) {
            resetButton.addEventListener('click', resetForm);
        }

        // Bulk action buttons
        document.querySelectorAll('input[name="mark_all"]').forEach(function (radio) {
            radio.addEventListener('change', handleBulkActionChange);
        });

        // Save attendance button
        if (saveButton) {
            saveButton.addEventListener('click', saveAttendance);
        }
    };

    /**
     * Initialize Module
     */
    var init = function () {
        initElements();
        initEventListeners();

        // For non-admin users, auto-load batches for their branch
        if (!isAdmin && userBranchId) {
            loadBatches(userBranchId);
        }
    };

    // ============================================
    // Public Methods
    // ============================================

    return {
        // Public initialization
        init: function () {
            init();
        },

        // Public method to reload batches
        reloadBatches: function (branchId) {
            return loadBatches(branchId);
        },

        // Public method to fetch students
        loadStudents: function () {
            fetchStudents();
        },

        // Public method to reset form
        reset: function () {
            resetForm();
        }
    };
}();

// ============================================
// DOM Ready Initialization
// ============================================
KTUtil.onDOMContentLoaded(function () {
    KTStudentAttendance.init();
});