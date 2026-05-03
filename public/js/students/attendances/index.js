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

    // Data State
    var currentStudents = [];
    var currentSort = { field: 'name', order: 'asc' };
    var lastRenderConfig = { isAllGroups: false, isAllBatches: false };

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
    var sortButtonsContainer;

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
            reinitSelect2(academicGroupSelect);
        } else {
            toggleElement(academicGroupWrapper, false);
            if (academicGroupSelect) {
                academicGroupSelect.value = '';
                reinitSelect2(academicGroupSelect);
            }
        }
        clearStudentList();
    };

    // ============================================
    // Batch Loading
    // ============================================

    /**
     * Load Batches via AJAX based on Branch
     */
    var loadBatches = function (branchId) {
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
    // Student Operations & Sorting
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

        if (!branchId || !classId) {
            showAlert('warning', 'Validation Error', 'Please select Branch and Class.');
            return;
        }

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

        if (batchId) payload.batch_id = batchId;
        if (academicGroup) payload.academic_group = academicGroup;

        makeRequest(routes.getStudents, {
            method: 'POST',
            body: payload
        })
            .then(function (response) {
                toggleElement(studentListLoader, false);

                if (response.is_off_day) {
                    renderOffDayWarning(response.off_day_name);
                }

                if (response.count > 0) {
                    // Update state
                    currentStudents = response.students;
                    lastRenderConfig = {
                        isAllGroups: response.is_all_groups || false,
                        isAllBatches: response.is_all_batches || false
                    };

                    // Reset Sort State to default (Name Asc)
                    currentSort = { field: 'name', order: 'asc' };
                    updateSortButtonsUI();

                    renderStudentTable(currentStudents, lastRenderConfig.isAllGroups, lastRenderConfig.isAllBatches);

                    toggleElement(bulkButtons, true);
                    toggleElement(saveSection, true);
                    studentCountEl.textContent = response.count;
                    resetBulkActionButtons();
                } else {
                    currentStudents = [];
                    studentListContainer.innerHTML = '<div class="alert alert-info d-flex align-items-center">' +
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
     * Sort Students based on field
     */
    var sortStudents = function (field) {
        if (!currentStudents || currentStudents.length === 0) return;

        // Toggle order if same field, else default to asc
        if (currentSort.field === field) {
            currentSort.order = currentSort.order === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.field = field;
            currentSort.order = 'asc';
        }

        // Apply sorting
        currentStudents.sort(function (a, b) {
            var valA = (a[field] || '').toString().toLowerCase();
            var valB = (b[field] || '').toString().toLowerCase();

            if (valA < valB) return currentSort.order === 'asc' ? -1 : 1;
            if (valA > valB) return currentSort.order === 'asc' ? 1 : -1;
            return 0;
        });

        // Update UI
        updateSortButtonsUI();
        renderStudentTable(currentStudents, lastRenderConfig.isAllGroups, lastRenderConfig.isAllBatches);
    };

    /**
     * Update Sorting Buttons UI
     */
    var updateSortButtonsUI = function () {
        if (!sortButtonsContainer) return;

        sortButtonsContainer.querySelectorAll('.sort-btn').forEach(function (btn) {
            var field = btn.dataset.sortField;
            var icon = btn.querySelector('i');

            if (field === currentSort.field) {
                btn.classList.add('active');
                icon.classList.remove('d-none');

                if (currentSort.order === 'desc') {
                    btn.classList.add('desc');
                } else {
                    btn.classList.remove('desc');
                }
            } else {
                btn.classList.remove('active', 'desc');
                icon.classList.add('d-none');
            }
        });
    };

    /**
     * Reset bulk action buttons
     */
    var resetBulkActionButtons = function () {
        document.querySelectorAll('input[name="mark_all"]').forEach(function (radio) {
            radio.checked = false;
        });
        document.querySelectorAll('.quick-action-btn').forEach(function (btn) {
            btn.classList.remove('active');
        });
    };

    /**
     * Render Off Day Warning
     */
    var renderOffDayWarning = function (dayName) {
        offDayWarning.innerHTML = '<div class="alert alert-dismissible alert-warning d-flex align-items-center p-4 p-md-5 mb-4 mb-md-5 border border-warning border-dashed fade-in">' +
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
            case 'science': badgeClass = 'badge-info'; break;
            case 'commerce': badgeClass = 'badge-success'; break;
            case 'arts': badgeClass = 'badge-warning'; break;
        }
        return '<span class="badge ' + badgeClass + ' fs-9 ms-2">' + escapeHtml(shortName) + '</span>';
    };

    /**
     * Batch Color Palette
     */
    var batchColorPalette = ['badge-pill-teal', 'badge-pill-lime', 'badge-pill-amber', 'badge-pill-cyan', 'badge-pill-slate', 'badge-pill-purple', 'badge-pill-orange', 'badge-pill-pink', 'badge-pill-rose', 'badge-pill-indigo'];
    var batchColorCache = {};

    var getBatchColorClass = function (batchId) {
        if (!batchId) return batchColorPalette[0];
        if (batchColorCache[batchId]) return batchColorCache[batchId];
        var colorIndex = parseInt(batchId) % batchColorPalette.length;
        batchColorCache[batchId] = batchColorPalette[colorIndex];
        return batchColorCache[batchId];
    };

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

            var groupBadgeHtml = isAllGroups ? getGroupBadge(academicGroup) : '';
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
                groupBadgeHtml + batchBadgeHtml +
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
                '<i class="ki-outline ki-time fs-8 me-1"></i>' + escapeHtml(updatedAt) + '</span>' +
                '</td>' +
                '<td class="pe-4">' +
                '<div class="attendance-taker-display d-flex align-items-center">' +
                '<div class="symbol symbol-25px me-2">' +
                '<span class="symbol-label bg-light-info text-info fs-9 fw-bold">' + (attendanceTaker !== '-' ? escapeHtml(attendanceTaker.charAt(0).toUpperCase()) : '-') + '</span>' +
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

            var groupBadgeHtml = isAllGroups ? getGroupBadge(academicGroup) : '';
            var batchBadgeHtml = isAllBatches ? getBatchBadge(batchId, batchName) : '';

            mobileHtml += '<div class="card card-bordered mb-3 attendance-card ' + (hasAttendance ? 'has-attendance' : '') + '" data-student-id="' + student.id + '" data-batch-id="' + batchId + '" data-has-attendance="' + (hasAttendance ? 'true' : 'false') + '">' +
                '<div class="card-body p-4">' +
                '<div class="d-flex align-items-start justify-content-between mb-3">' +
                '<div class="d-flex align-items-center">' +
                '<div class="symbol symbol-40px me-3">' +
                '<a href="' + getStudentProfileUrl(student.id) + '" target="_blank" class="symbol-label bg-light-primary text-primary fw-bold fs-6 text-hover-white bg-hover-primary">' + escapeHtml(initials) + '</a>' +
                '</div>' +
                '<div>' +
                '<div class="d-flex align-items-center flex-wrap">' +
                '<a href="' + getStudentProfileUrl(student.id) + '" target="_blank" class="fw-bold text-gray-800 text-hover-primary fs-6">' + escapeHtml(student.name) + '</a>' +
                groupBadgeHtml + batchBadgeHtml +
                '</div>' +
                '<div class="text-muted fs-8">ID: ' + escapeHtml(student.student_unique_id) + '</div>' +
                (homeMobile ? '<div class="text-muted fs-8 mt-1"><i class="ki-outline ki-phone fs-8 me-1"></i>' + escapeHtml(homeMobile) + '</div>' : '') +
                '</div>' +
                '</div>' +
                '<span class="badge badge-light-secondary fs-8">#' + (index + 1) + '</span>' +
                '</div>' +
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
                '<div class="mb-2">' +
                '<input type="text" class="form-control form-control-solid form-control-sm remarks-input" placeholder="Add remarks (optional)" value="' + remarks + '">' +
                '</div>' +
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
     * Collect attendance data from the visible view
     */
    var collectAttendanceData = function () {
        var attendanceData = [];
        var hasValidationError = false;
        var isDesktop = window.innerWidth >= 992;

        if (isDesktop) {
            var desktopTable = document.getElementById('attendance_table');
            if (desktopTable) {
                var rows = desktopTable.querySelectorAll('tbody tr.attendance-row');
                rows.forEach(function (row) {
                    var studentId = row.getAttribute('data-student-id');
                    var batchId = row.getAttribute('data-batch-id');
                    var checkedRadio = row.querySelector('input.status-radio:checked');
                    var remarksInput = row.querySelector('.remarks-input');

                    if (!checkedRadio) {
                        hasValidationError = true;
                        row.classList.add('bg-light-danger');
                    } else {
                        row.classList.remove('bg-light-danger');
                        var item = { student_id: studentId, status: checkedRadio.value, remarks: remarksInput ? remarksInput.value : '' };
                        if (batchId) item.batch_id = batchId;
                        attendanceData.push(item);
                    }
                });
            }
        } else {
            var mobileCards = document.getElementById('attendance_cards');
            if (mobileCards) {
                var cards = mobileCards.querySelectorAll('.attendance-card');
                cards.forEach(function (card) {
                    var studentId = card.getAttribute('data-student-id');
                    var batchId = card.getAttribute('data-batch-id');
                    var checkedRadio = card.querySelector('input.status-radio:checked');
                    var remarksInput = card.querySelector('.remarks-input');

                    if (!checkedRadio) {
                        hasValidationError = true;
                        card.classList.add('border-danger');
                    } else {
                        card.classList.remove('border-danger');
                        var item = { student_id: studentId, status: checkedRadio.value, remarks: remarksInput ? remarksInput.value : '' };
                        if (batchId) item.batch_id = batchId;
                        attendanceData.push(item);
                    }
                });
            }
        }
        return { data: attendanceData, hasError: hasValidationError };
    };

    /**
     * Save Attendance
     */
    var saveAttendance = function () {
        var result = collectAttendanceData();

        if (result.hasError) {
            showAlert('error', 'Incomplete Attendance', 'Please select a status for all students highlighted in red.');
            return;
        }

        if (result.data.length === 0) {
            showAlert('warning', 'No Data', 'No attendance data to save.');
            return;
        }

        var payload = {
            attendance_date: document.getElementById('attendance_date').value,
            branch_id: branchSelect.value,
            class_id: classSelect.value,
            attendances: result.data
        };

        var batchId = batchSelect.value;
        if (batchId) payload.batch_id = batchId;

        var originalHtml = saveButton.innerHTML;
        setButtonLoading(saveButton, true, originalHtml);

        makeRequest(routes.storeBulk, {
            method: 'POST',
            body: payload
        })
            .then(function (response) {
                showAlert('success', 'Saved!', response.message || 'Attendance saved successfully!', { timer: 2000, showConfirmButton: false });

                // After save, we fetch again to get accurate server timestamps,
                // but we could also just refresh the local UI if we wanted.
                fetchStudents();
            })
            .catch(function (error) {
                console.error('Error saving attendance:', error);
                showAlert('error', 'Error', error.message || 'An error occurred.');
            })
            .finally(function () {
                setButtonLoading(saveButton, false, originalHtml);
            });
    };

    /**
     * Reset Form
     */
    var resetForm = function () {
        if (isAdmin) {
            branchSelect.value = '';
            reinitSelect2(branchSelect);
        }
        classSelect.value = '';
        batchSelect.innerHTML = '<option value="">Select branch first</option>';
        reinitSelect2(classSelect);
        reinitSelect2(batchSelect);

        toggleElement(academicGroupWrapper, false);
        if (academicGroupSelect) {
            academicGroupSelect.value = '';
            reinitSelect2(academicGroupSelect);
        }

        studentListContainer.innerHTML = '';
        offDayWarning.innerHTML = '';
        toggleElement(saveSection, false);
        toggleElement(bulkButtons, false);
        resetBulkActionButtons();
        currentStudents = [];
    };

    /**
     * Handle Bulk Action
     */
    var handleBulkAction = function (status) {
        var isDesktop = window.innerWidth >= 992;
        if (isDesktop) {
            var desktopTable = document.getElementById('attendance_table');
            if (desktopTable) {
                desktopTable.querySelectorAll('tbody tr.attendance-row').forEach(function (row) {
                    var radio = row.querySelector('input.status-radio[value="' + status + '"]');
                    if (radio) radio.checked = true;
                    row.classList.remove('bg-light-danger');
                });
            }
        } else {
            var mobileCards = document.getElementById('attendance_cards');
            if (mobileCards) {
                mobileCards.querySelectorAll('.attendance-card').forEach(function (card) {
                    var radio = card.querySelector('input.status-radio[value="' + status + '"]');
                    if (radio) radio.checked = true;
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
        currentStudents = [];
    };

    // ============================================
    // Event Handlers
    // ============================================

    var handleBranchChange = function () {
        loadBatches(branchSelect.value);
        clearStudentList();
    };

    var handleClassChange = function () {
        handleAcademicGroupVisibility();
    };

    var handleFormSubmit = function (e) {
        e.preventDefault();
        fetchStudents();
    };

    var handleBulkActionClick = function (e) {
        var label = e.target.closest('.quick-action-btn');
        if (!label) return;
        e.preventDefault();
        var radio = label.querySelector('input[type="radio"]');
        if (!radio) return;
        var status = radio.value;
        radio.checked = true;
        document.querySelectorAll('.quick-action-btn').forEach(function (btn) { btn.classList.remove('active'); });
        label.classList.add('active');
        handleBulkAction(status);
    };

    var handleSortClick = function (e) {
        var btn = e.target.closest('.sort-btn');
        if (!btn) return;
        var field = btn.dataset.sortField;
        sortStudents(field);
    };

    // ============================================
    // Initialization
    // ============================================

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
        sortButtonsContainer = document.getElementById('sort_buttons_container');
    };

    var initEventListeners = function () {
        if (branchSelect) {
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                jQuery(branchSelect).on('select2:select select2:clear', handleBranchChange);
            }
            branchSelect.addEventListener('change', handleBranchChange);
        }
        if (classSelect) {
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                jQuery(classSelect).on('select2:select select2:clear', handleClassChange);
            }
            classSelect.addEventListener('change', handleClassChange);
        }
        if (form) form.addEventListener('submit', handleFormSubmit);
        if (resetButton) resetButton.addEventListener('click', resetForm);
        if (bulkButtons) bulkButtons.addEventListener('click', handleBulkActionClick);
        if (saveButton) saveButton.addEventListener('click', saveAttendance);
        if (sortButtonsContainer) sortButtonsContainer.addEventListener('click', handleSortClick);
    };

    var init = function () {
        initElements();
        initEventListeners();
        if (!isAdmin && userBranchId) {
            loadBatches(userBranchId);
        }
    };

    return {
        init: function () { init(); }
    };
}());

KTUtil.onDOMContentLoaded(function () {
    KTStudentAttendance.init();
});