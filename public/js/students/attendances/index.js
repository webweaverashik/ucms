"use strict";

/**
 * KTStudentAttendance
 * Metronic 8 Style Module for Student Attendance Management
 * Mobile-Friendly Version with Academic Group Support
 *
 * @package App/Student/Attendance
 */

var KTStudentAttendance = (function () {
    // ============================================
    // Private Variables
    // ============================================

    var config = window.AttendanceConfig || {};
    var routes = config.routes || {};
    var csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
    var isAdmin = config.isAdmin ?? true;
    var userBranchId = config.userBranchId;
    var groupRequiredClasses = config.groupRequiredClasses || ['09', '10', '11', '12'];

    // DOM Elements
    var form;
    var branchSelect;
    var classSelect;
    var batchSelect;
    var academicGroupSelect;
    var batchLoader;
    var studentListLoader;
    var studentListContainer;
    var offDayWarning;
    var bulkButtons;
    var saveSection;
    var saveButton;
    var resetButton;
    var studentCountEl;
    var academicGroupWrapper;

    // ============================================
    // Private Functions
    // ============================================

    /**
     * Make AJAX Request using Fetch API
     */
    var makeRequest = function (url, options) {
        options = options || {};

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

    /**
     * Get Student Profile URL
     */
    var getStudentProfileUrl = function (studentId) {
        if (!routes.studentProfile) return '#';
        return routes.studentProfile.replace(':studentId', studentId);
    };

    /**
     * Check if class requires academic group selection
     */
    var classRequiresGroup = function (classNumeral) {
        return groupRequiredClasses.indexOf(classNumeral) !== -1;
    };

    /**
     * Get selected class numeral
     */
    var getSelectedClassNumeral = function () {
        var selectedOption = classSelect.options[classSelect.selectedIndex];
        return selectedOption ? selectedOption.dataset.classNumeral : null;
    };

    /**
     * Get selected academic group
     */
    var getSelectedAcademicGroup = function () {
        return academicGroupSelect ? academicGroupSelect.value : null;
    };

    /**
     * Handle Academic Group Visibility
     */
    var handleAcademicGroupVisibility = function () {
        var classNumeral = getSelectedClassNumeral();

        if (classNumeral && classRequiresGroup(classNumeral)) {
            toggleElement(academicGroupWrapper, true);
            // Reinitialize Select2 when shown
            reinitSelect2(academicGroupSelect);
        } else {
            toggleElement(academicGroupWrapper, false);
            // Clear selection when hidden
            if (academicGroupSelect) {
                academicGroupSelect.value = '';
                reinitSelect2(academicGroupSelect);
            }
        }

        // Clear student list when class changes
        clearStudentList();
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
                // First option is "All Batches" (optional selection)
                batchSelect.innerHTML = '<option value="">All Batches</option>';

                if (response.batches && response.batches.length > 0) {
                    response.batches.forEach(function (batch) {
                        var option = document.createElement('option');
                        option.value = batch.id;
                        option.textContent = batch.name;
                        option.dataset.dayOff = batch.day_off || '';
                        batchSelect.appendChild(option);
                    });
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
        var academicGroup = getSelectedAcademicGroup();

        // Validation - Only branch and class are required
        // Batch and Academic group are optional - if not selected, all students will be loaded
        if (!branchId || !classId) {
            showAlert('warning', 'Validation Error', 'Please select Branch and Class.');
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
            attendance_date: attendanceDate
        };

        // Add batch_id if a specific batch is selected (optional filter)
        if (batchId) {
            payload.batch_id = batchId;
        }

        // Add academic group if applicable
        if (academicGroup) {
            payload.academic_group = academicGroup;
        }

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
                    // Pass is_all_groups and is_all_batches flags to render function
                    renderStudentTable(response.students, response.is_all_groups || false, response.is_all_batches || false);
                    toggleElement(bulkButtons, true);
                    toggleElement(saveSection, true);
                    studentCountEl.textContent = response.count;

                    // Reset bulk action radios
                    resetBulkActionButtons();
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
     * Reset bulk action buttons
     */
    var resetBulkActionButtons = function () {
        document.querySelectorAll('input[name="mark_all"]').forEach(function (radio) {
            radio.checked = false;
        });
        // Also remove active classes from labels
        document.querySelectorAll('.quick-action-btn').forEach(function (btn) {
            btn.classList.remove('active');
        });
    };

    /**
     * Render Off Day Warning
     */
    var renderOffDayWarning = function (dayName) {
        offDayWarning.innerHTML =
            '<div class="alert alert-dismissible alert-warning d-flex align-items-center p-4 p-md-5 mb-4 mb-md-5 border border-warning border-dashed fade-in">' +
            '<i class="ki-outline ki-information-5 fs-2hx text-warning me-3 me-md-4"></i>' +
            '<div class="d-flex flex-column">' +
            '<h4 class="mb-1 text-warning fs-6 fs-md-5">Off Day Warning</h4>' +
            '<span class="fs-8 fs-md-7">' +
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
     * Get academic group badge HTML
     */
    var getGroupBadge = function (groupName) {
        if (!groupName) return '';

        var badgeClass = 'badge-light-secondary';
        var shortName = groupName;

        switch (groupName.toLowerCase()) {
            case 'science':
                badgeClass = 'badge-info';
                shortName = 'Science';
                break;
            case 'commerce':
                badgeClass = 'badge-success';
                shortName = 'Commerce';
                break;
            case 'arts':
                badgeClass = 'badge-warning';
                shortName = 'Arts';
                break;
        }

        return '<span class="badge ' + badgeClass + ' fs-9 ms-2">' + escapeHtml(shortName) + '</span>';
    };

    /**
     * Batch color palette - Custom pill badge colors (distinct from Bootstrap defaults used by groups)
     * Groups use: badge-light-success (green), badge-light-info (cyan), badge-light-warning (yellow)
     * Batches use custom pill colors defined below
     */
    var batchColorPalette = [
        'badge-pill-teal',       // Teal
        'badge-pill-lime',       // Lime
        'badge-pill-amber',      // Amber
        'badge-pill-cyan',       // Cyan
        'badge-pill-slate',       // Slate
        'badge-pill-purple',     // Purple
        'badge-pill-orange',     // Orange
        'badge-pill-pink',       // Pink
        'badge-pill-rose',       // Rose
        'badge-pill-indigo',     // Indigo
    ];

    /**
     * Cache for batch color assignments
     */
    var batchColorCache = {};

    /**
     * Get consistent color for a batch based on batch ID
     */
    var getBatchColorClass = function (batchId) {
        if (!batchId) return batchColorPalette[0];

        // Check cache first for consistent colors
        if (batchColorCache[batchId]) {
            return batchColorCache[batchId];
        }

        // Assign color based on batch ID (mod palette length)
        var colorIndex = parseInt(batchId) % batchColorPalette.length;
        batchColorCache[batchId] = batchColorPalette[colorIndex];

        return batchColorCache[batchId];
    };

    /**
     * Get batch badge HTML with dynamic color based on batch ID
     */
    var getBatchBadge = function (batchId, batchName) {
        if (!batchName) return '';
        var colorClass = getBatchColorClass(batchId);
        return '<span class="badge ' + colorClass + ' fs-9 ms-1">' + escapeHtml(batchName) + '</span>';
    };

    /**
     * Render Student Table (Mobile Optimized with Cards)
     */
    var renderStudentTable = function (students, isAllGroups, isAllBatches) {
        // Desktop Table View
        var desktopHtml = '<div class="d-none d-lg-block table-responsive fade-in">' +
            '<table class="table table-row-bordered table-row-gray-300 align-middle gs-0 gy-4" id="attendance_table">' +
            '<thead>' +
            '<tr class="fw-bold text-muted bg-light">' +
            '<th class="ps-4 w-50px rounded-start">#</th>' +
            '<th class="min-w-250px">Student Info</th>' +
            '<th class="text-center min-w-280px">Attendance Status</th>' +
            '<th class="min-w-120px">Remarks</th>' +
            '<th class="text-center min-w-90px">Updated</th>' +
            '<th class="min-w-120px pe-4 rounded-end">Taken By</th>' +
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
            var homeMobile = student.home_mobile || '';
            var academicGroup = student.academic_group || '';
            var batchId = student.batch_id || '';
            var batchName = student.batch_name || '';

            // Build group badge HTML (only show when isAllGroups is true)
            var groupBadgeHtml = isAllGroups ? getGroupBadge(academicGroup) : '';

            // Build batch badge HTML (only show when isAllBatches is true)
            var batchBadgeHtml = isAllBatches ? getBatchBadge(batchId, batchName) : '';

            desktopHtml += '<tr data-student-id="' + student.id + '" data-batch-id="' + batchId + '" class="attendance-row" data-has-attendance="' + (hasAttendance ? 'true' : 'false') + '">' +
                '<td class="ps-4 fw-bold text-gray-600">' + (index + 1) + '</td>' +
                '<td>' +
                '<div class="d-flex align-items-center">' +
                '<div class="symbol symbol-45px me-3">' +
                '<a href="' + getStudentProfileUrl(student.id) + '" target="_blank" class="symbol-label bg-light-primary text-primary fw-bold text-hover-white bg-hover-primary">' + escapeHtml(initials) + '</a>' +
                '</div>' +
                '<div class="d-flex flex-column">' +
                '<div class="d-flex align-items-center flex-wrap">' +
                '<a href="' + getStudentProfileUrl(student.id) + '" target="_blank" class="text-gray-800 text-hover-primary fw-bold fs-6">' + escapeHtml(student.name) + '</a>' +
                groupBadgeHtml +
                batchBadgeHtml +
                '</div>' +
                '<span class="text-muted fw-semibold fs-7">ID: ' + escapeHtml(student.student_unique_id) + '</span>' +
                (homeMobile ? '<span class="text-muted fw-semibold fs-7"><i class="ki-outline ki-phone fs-7 me-1"></i>' + escapeHtml(homeMobile) + '</span>' : '') +
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
                'placeholder="Remarks" value="' + remarks + '">' +
                '</td>' +
                '<td class="text-center">' +
                '<span class="updated-at-display badge badge-light-primary fs-8">' +
                '<i class="ki-outline ki-time fs-8 me-1"></i>' + escapeHtml(updatedAt) +
                '</span>' +
                '</td>' +
                '<td class="pe-4">' +
                '<div class="attendance-taker-display d-flex align-items-center">' +
                '<div class="symbol symbol-25px me-2">' +
                '<span class="symbol-label bg-light-info text-info fs-9 fw-bold">' +
                (attendanceTaker !== '-' ? escapeHtml(attendanceTaker.charAt(0).toUpperCase()) : '-') +
                '</span>' +
                '</div>' +
                '<span class="text-gray-700 fs-8 fw-semibold attendance-taker-name">' + escapeHtml(attendanceTaker) + '</span>' +
                '</div>' +
                '</td>' +
                '</tr>';
        });

        desktopHtml += '</tbody></table></div>';

        // Mobile Card View
        var mobileHtml = '<div class="d-lg-none fade-in" id="attendance_cards">';

        students.forEach(function (student, index) {
            var presentChecked = student.status === 'present' ? 'checked' : '';
            var lateChecked = student.status === 'late' ? 'checked' : '';
            var absentChecked = student.status === 'absent' ? 'checked' : '';

            var initials = student.name.charAt(0).toUpperCase();
            var remarks = escapeHtml(student.remarks || '');
            var updatedAt = student.updated_at || '-';
            var attendanceTaker = student.attendance_taker || '-';
            var hasAttendance = student.has_attendance;
            var homeMobile = student.home_mobile || '';
            var academicGroup = student.academic_group || '';
            var batchId = student.batch_id || '';
            var batchName = student.batch_name || '';

            // Build group badge HTML (only show when isAllGroups is true)
            var groupBadgeHtml = isAllGroups ? getGroupBadge(academicGroup) : '';

            // Build batch badge HTML (only show when isAllBatches is true)
            var batchBadgeHtml = isAllBatches ? getBatchBadge(batchId, batchName) : '';

            mobileHtml += '<div class="card card-bordered mb-3 attendance-card ' + (hasAttendance ? 'has-attendance' : '') + '" data-student-id="' + student.id + '" data-batch-id="' + batchId + '" data-has-attendance="' + (hasAttendance ? 'true' : 'false') + '">' +
                '<div class="card-body p-4">' +
                // Header with student info
                '<div class="d-flex align-items-start justify-content-between mb-3">' +
                '<div class="d-flex align-items-center">' +
                '<div class="symbol symbol-40px me-3">' +
                '<a href="' + getStudentProfileUrl(student.id) + '" target="_blank" class="symbol-label bg-light-primary text-primary fw-bold fs-6 text-hover-white bg-hover-primary">' + escapeHtml(initials) + '</a>' +
                '</div>' +
                '<div>' +
                '<div class="d-flex align-items-center flex-wrap">' +
                '<a href="' + getStudentProfileUrl(student.id) + '" target="_blank" class="fw-bold text-gray-800 text-hover-primary fs-6">' + escapeHtml(student.name) + '</a>' +
                groupBadgeHtml +
                batchBadgeHtml +
                '</div>' +
                '<div class="text-muted fs-8">ID: ' + escapeHtml(student.student_unique_id) + '</div>' +
                (homeMobile ? '<div class="text-muted fs-8 mt-1"><i class="ki-outline ki-phone fs-8 me-1"></i>' + escapeHtml(homeMobile) + '</div>' : '') +
                '</div>' +
                '</div>' +
                '<span class="badge badge-light-secondary fs-8">#' + (index + 1) + '</span>' +
                '</div>' +

                // Status buttons (larger for mobile touch)
                '<div class="d-flex gap-2 mb-3">' +
                '<label class="status-option-mobile present-option flex-fill">' +
                '<input class="status-radio" type="radio" value="present" name="status_mobile_' + student.id + '" ' + presentChecked + '>' +
                '<span class="status-label"><i class="ki-outline ki-check-circle fs-6"></i> Present</span>' +
                '</label>' +
                '<label class="status-option-mobile late-option flex-fill">' +
                '<input class="status-radio" type="radio" value="late" name="status_mobile_' + student.id + '" ' + lateChecked + '>' +
                '<span class="status-label"><i class="ki-outline ki-time fs-6"></i> Late</span>' +
                '</label>' +
                '<label class="status-option-mobile absent-option flex-fill">' +
                '<input class="status-radio" type="radio" value="absent" name="status_mobile_' + student.id + '" ' + absentChecked + '>' +
                '<span class="status-label"><i class="ki-outline ki-cross-circle fs-6"></i> Absent</span>' +
                '</label>' +
                '</div>' +

                // Remarks input
                '<div class="mb-2">' +
                '<input type="text" class="form-control form-control-solid form-control-sm remarks-input" placeholder="Add remarks (optional)" value="' + remarks + '">' +
                '</div>' +

                // Footer info
                '<div class="d-flex justify-content-between align-items-center text-muted fs-9">' +
                '<span><i class="ki-outline ki-time fs-9 me-1"></i>Updated: ' + escapeHtml(updatedAt) + '</span>' +
                '<span><i class="ki-outline ki-user fs-9 me-1"></i>' + escapeHtml(attendanceTaker) + '</span>' +
                '</div>' +
                '</div>' +
                '</div>';
        });

        mobileHtml += '</div>';

        studentListContainer.innerHTML = desktopHtml + mobileHtml;
    };

    /**
     * Refresh student list after save to get accurate timestamps
     */
    var refreshStudentList = function () {
        var branchId = branchSelect.value;
        var classId = classSelect.value;
        var batchId = batchSelect.value;
        var attendanceDate = document.getElementById('attendance_date').value;
        var academicGroup = getSelectedAcademicGroup();

        var payload = {
            branch_id: branchId,
            class_id: classId,
            attendance_date: attendanceDate
        };

        // Add batch_id if a specific batch is selected (optional filter)
        if (batchId) {
            payload.batch_id = batchId;
        }

        if (academicGroup) {
            payload.academic_group = academicGroup;
        }

        return makeRequest(routes.getStudents, {
            method: 'POST',
            body: payload
        })
            .then(function (response) {
                if (response.count > 0) {
                    // Pass is_all_groups and is_all_batches flags to render function
                    renderStudentTable(response.students, response.is_all_groups || false, response.is_all_batches || false);
                    studentCountEl.textContent = response.count;
                    // Reset bulk action buttons after refresh
                    resetBulkActionButtons();
                }
            })
            .catch(function (error) {
                console.error('Error refreshing student list:', error);
            });
    };

    /**
     * Collect attendance data from the visible view
     * This function reads the CURRENT state of radio buttons, not cached values
     * Also includes batch_id for each student (needed when "All Batches" is selected)
     */
    var collectAttendanceData = function () {
        var attendanceData = [];
        var hasValidationError = false;

        // Determine which view is currently visible
        var isDesktop = window.innerWidth >= 992; // lg breakpoint

        if (isDesktop) {
            // Collect from desktop table
            var desktopTable = document.getElementById('attendance_table');
            if (desktopTable) {
                var rows = desktopTable.querySelectorAll('tbody tr.attendance-row');
                rows.forEach(function (row) {
                    var studentId = row.getAttribute('data-student-id');
                    var batchId = row.getAttribute('data-batch-id');

                    // Find the CURRENTLY checked radio for this student
                    var checkedRadio = row.querySelector('input.status-radio:checked');
                    var remarksInput = row.querySelector('.remarks-input');

                    if (!checkedRadio) {
                        hasValidationError = true;
                        row.classList.add('bg-light-danger');
                    } else {
                        row.classList.remove('bg-light-danger');
                        var attendanceItem = {
                            student_id: studentId,
                            status: checkedRadio.value,
                            remarks: remarksInput ? remarksInput.value : ''
                        };
                        // Include batch_id for each student (needed for "All Batches" mode)
                        if (batchId) {
                            attendanceItem.batch_id = batchId;
                        }
                        attendanceData.push(attendanceItem);
                    }
                });
            }
        } else {
            // Collect from mobile cards
            var mobileCards = document.getElementById('attendance_cards');
            if (mobileCards) {
                var cards = mobileCards.querySelectorAll('.attendance-card');
                cards.forEach(function (card) {
                    var studentId = card.getAttribute('data-student-id');
                    var batchId = card.getAttribute('data-batch-id');

                    // Find the CURRENTLY checked radio for this student
                    var checkedRadio = card.querySelector('input.status-radio:checked');
                    var remarksInput = card.querySelector('.remarks-input');

                    if (!checkedRadio) {
                        hasValidationError = true;
                        card.classList.add('border-danger');
                    } else {
                        card.classList.remove('border-danger');
                        var attendanceItem = {
                            student_id: studentId,
                            status: checkedRadio.value,
                            remarks: remarksInput ? remarksInput.value : ''
                        };
                        // Include batch_id for each student (needed for "All Batches" mode)
                        if (batchId) {
                            attendanceItem.batch_id = batchId;
                        }
                        attendanceData.push(attendanceItem);
                    }
                });
            }
        }

        return {
            data: attendanceData,
            hasError: hasValidationError
        };
    };

    /**
     * Save Attendance
     */
    var saveAttendance = function () {
        var result = collectAttendanceData();

        if (result.hasError) {
            showAlert('error', 'Incomplete Attendance', 'Please select a status (Present, Late, or Absent) for all students highlighted in red.');
            return;
        }

        if (result.data.length === 0) {
            showAlert('warning', 'No Data', 'No attendance data to save. Please load students first.');
            return;
        }

        // Prepare payload
        var payload = {
            attendance_date: document.getElementById('attendance_date').value,
            branch_id: branchSelect.value,
            class_id: classSelect.value,
            attendances: result.data
        };

        // Add batch_id only if a specific batch is selected
        // Otherwise, each attendance record will have its own batch_id
        var batchId = batchSelect.value;
        if (batchId) {
            payload.batch_id = batchId;
        }

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

        // Hide and reset academic group dropdown
        toggleElement(academicGroupWrapper, false);
        if (academicGroupSelect) {
            academicGroupSelect.value = '';
            reinitSelect2(academicGroupSelect);
        }

        // Clear containers
        studentListContainer.innerHTML = '';
        offDayWarning.innerHTML = '';
        toggleElement(saveSection, false);
        toggleElement(bulkButtons, false);

        // Reset bulk action radios
        resetBulkActionButtons();
    };

    /**
     * Handle Bulk Action - Mark all students with specified status
     * This directly manipulates the radio button checked state
     */
    var handleBulkAction = function (status) {
        // Determine which view is currently visible
        var isDesktop = window.innerWidth >= 992; // lg breakpoint

        if (isDesktop) {
            // Update desktop table radios
            var desktopTable = document.getElementById('attendance_table');
            if (desktopTable) {
                var rows = desktopTable.querySelectorAll('tbody tr.attendance-row');
                rows.forEach(function (row) {
                    var targetRadio = row.querySelector('input.status-radio[value="' + status + '"]');
                    if (targetRadio) {
                        targetRadio.checked = true;
                    }
                    // Clear error highlighting
                    row.classList.remove('bg-light-danger');
                });
            }
        } else {
            // Update mobile card radios
            var mobileCards = document.getElementById('attendance_cards');
            if (mobileCards) {
                var cards = mobileCards.querySelectorAll('.attendance-card');
                cards.forEach(function (card) {
                    var targetRadio = card.querySelector('input.status-radio[value="' + status + '"]');
                    if (targetRadio) {
                        targetRadio.checked = true;
                    }
                    // Clear error highlighting
                    card.classList.remove('border-danger');
                });
            }
        }
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
     * Handle Class Change
     */
    var handleClassChange = function () {
        handleAcademicGroupVisibility();
    };

    /**
     * Handle Form Submit
     */
    var handleFormSubmit = function (e) {
        e.preventDefault();
        fetchStudents();
    };

    /**
     * Handle Bulk Action Click - Event delegation on the container
     */
    var handleBulkActionClick = function (e) {
        // Find the clicked quick-action-btn label
        var label = e.target.closest('.quick-action-btn');
        if (!label) return;

        // Prevent default to avoid double-firing
        e.preventDefault();

        // Get the radio input inside the label
        var radio = label.querySelector('input[type="radio"]');
        if (!radio) return;

        // Get the status value
        var status = radio.value;

        // Check the radio manually
        radio.checked = true;

        // Remove active class from all quick action buttons
        document.querySelectorAll('.quick-action-btn').forEach(function (btn) {
            btn.classList.remove('active');
        });

        // Add active class to the clicked button
        label.classList.add('active');

        // Apply the bulk action to all student attendance radios
        handleBulkAction(status);
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
        academicGroupWrapper = document.getElementById('academic_group_wrapper');
        academicGroupSelect = document.getElementById('academic_group');
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

        // Class change - Handle academic group visibility
        if (classSelect) {
            // For Select2
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                jQuery(classSelect).on('select2:select select2:clear', handleClassChange);
            }
            // Native fallback
            classSelect.addEventListener('change', handleClassChange);
        }

        // Form submit
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }

        // Reset button
        if (resetButton) {
            resetButton.addEventListener('click', resetForm);
        }

        // Bulk action buttons - Use click delegation on the container
        if (bulkButtons) {
            bulkButtons.addEventListener('click', handleBulkActionClick);
        }

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
        },

        // Public method to apply bulk action
        markAll: function (status) {
            handleBulkAction(status);
        }
    };
}());

// ============================================
// DOM Ready Initialization
// ============================================

KTUtil.onDOMContentLoaded(function () {
    KTStudentAttendance.init();
});
