"use strict";

var KTStudentsPromote = function () {
    var tableBody = document.getElementById('student_list_body');
    var targetCard = document.getElementById('target_card');

    var loadBatches = function (branchSelectId, batchSelectId, isTarget = false) {
        var branchId = $(`#${branchSelectId}`).val();
        var batchSelect = $(`#${batchSelectId}`);

        if (!branchId) {
            batchSelect.empty().append(`<option value="">${isTarget ? 'Select Batch' : 'All Batches'}</option>`);
            batchSelect.trigger('change.select2');
            return;
        }

        batchSelect.prop('disabled', true).trigger('change.select2');

        $.ajax({
            url: `/branches/${branchId}/batches`,
            type: 'GET',
            success: function (data) {
                batchSelect.empty().append(`<option value="">${isTarget ? 'Select Batch' : 'All Batches'}</option>`);
                if (data && data.length > 0) {
                    data.forEach(function (batch) {
                        batchSelect.append(`<option value="${batch.id}">${batch.name}</option>`);
                    });
                }
                batchSelect.prop('disabled', false).trigger('change.select2');
            },
            error: function () {
                batchSelect.empty().append('<option value="">Error loading</option>');
                batchSelect.prop('disabled', false).trigger('change.select2');
            }
        });
    };

    var handleBatchLoading = function () {
        // For Admins: Load on change
        $('#source_branch').on('change', function () {
            var branchId = $(this).val();
            var batchSelect = $('#source_batch');
            
            if (branchId) {
                batchSelect.prop('disabled', false);
                loadBatches('source_branch', 'source_batch', false);
            } else {
                batchSelect.prop('disabled', true).empty().append('<option value="">All Batches</option>');
            }
            batchSelect.trigger('change.select2');
            
            // Also update target batches since branch is the same
            loadBatches('source_branch', 'target_batch', true);
        });

        // For Managers: If branch is pre-selected and batches are NOT already loaded, fetch them
        // Note: We preloaded them in Blade, so this is a fallback
        setTimeout(function() {
            if ($('#source_branch').val() && $('#source_batch option').length <= 1) {
                loadBatches('source_branch', 'source_batch', false);
                loadBatches('source_branch', 'target_batch', true);
            }
        }, 300);
    };

    var handleFilter = function () {
        $('#btn_filter').on('click', function (e) {
            e.preventDefault();
            var branch = $('#source_branch').val();
            var className = $('#source_class').val();
            var batch = $('#source_batch').val();
            var group = $('#source_group').val();

            if (!branch) {
                Swal.fire({ text: "Please select a branch.", icon: "warning", buttonsStyling: false, confirmButtonText: "Ok", customClass: { confirmButton: "btn btn-primary" } });
                return;
            }

            if (!className) {
                Swal.fire({ text: "Please select a class.", icon: "warning", buttonsStyling: false, confirmButtonText: "Ok", customClass: { confirmButton: "btn btn-primary" } });
                return;
            }

            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-20">
                        <div class="d-flex flex-column align-items-center">
                            <span class="spinner-border border-2 w-40px h-40px text-primary mb-4" role="status"></span>
                            <span class="text-gray-600 fw-semibold">Loading students...</span>
                        </div>
                    </td>
                </tr>
            `;
            targetCard.classList.add('d-none');

            $.ajax({
                url: '/students/promote/get-students',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    branch_id: branch,
                    class_id: className,
                    batch_id: batch,
                    academic_group: group
                },
                success: function (data) {
                    renderStudentList(data);
                },
                error: function () {
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-10">Failed to load students.</td></tr>';
                }
            });
        });
    };

    var renderStudentList = function (students) {
        tableBody.innerHTML = '';
        if (!students || students.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-10">No students found.</td></tr>';
            targetCard.classList.add('d-none');
            return;
        }

        students.forEach(function (s, index) {
            var badgeClass = s.status === 'active' ? 'badge-light-success' : 'badge-light-danger';
            var row = `
                <tr>
                    <td>
                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input student-checkbox" type="checkbox" value="${s.id}" />
                        </div>
                    </td>
                    <td>${index + 1}</td>
                    <td><span class="text-gray-800 fw-bold">${s.unique_id}</span></td>
                    <td><a href="/students/${s.id}" target="_blank" class="text-gray-800 text-hover-primary">${s.name}</a></td>
                    <td><a href="/classnames/${s.class_id}" target="_blank" class="text-gray-800 text-hover-primary">${s.class}</a></td>
                    <td>${s.batch}</td>
                    <td>${s.group}</td>
                    <td><span class="badge ${badgeClass} text-capitalize">${s.status}</span></td>
                </tr>
            `;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
        targetCard.classList.remove('d-none');
    };

    var handleClassFiltering = function () {
        $('#source_class').on('change', function () {
            var sourceNumeral = parseInt($(this).find(':selected').data('numeral')) || 0;
            var targetClassSelect = $('#target_class');
            var groupContainer = $('#academic_group_container');
            var groupSelect = $('#source_group');
            
            // Academic Group visibility for 09-12
            if (sourceNumeral >= 9 && sourceNumeral <= 12) {
                groupContainer.removeClass('d-none');
                groupSelect.prop('disabled', false).trigger('change.select2');
            } else {
                groupContainer.addClass('d-none');
                groupSelect.prop('disabled', true).val('').trigger('change.select2');
            }

            targetClassSelect.find('option').each(function () {
                var targetNumeral = parseInt($(this).data('numeral'));
                if (targetNumeral && targetNumeral < sourceNumeral) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });
            
            // Clear target class if it's now disabled
            if (targetClassSelect.find(':selected').prop('disabled')) {
                targetClassSelect.val('').trigger('change.select2');
            } else {
                targetClassSelect.trigger('change.select2');
            }
        });
    };

    var handlePromotion = function () {
        $('#promotion_form').on('submit', function (e) {
            e.preventDefault();
            var selectedIds = [];
            $('.student-checkbox:checked').each(function () { selectedIds.push($(this).val()); });

            if (selectedIds.length === 0) {
                Swal.fire({ text: "Select at least one student.", icon: "warning", buttonsStyling: false, confirmButtonText: "Ok", customClass: { confirmButton: "btn btn-primary" } });
                return;
            }

            if (!$('#target_class').val() || !$('#target_batch').val()) {
                Swal.fire({ text: "Please select target class and batch.", icon: "warning", buttonsStyling: false, confirmButtonText: "Ok", customClass: { confirmButton: "btn btn-primary" } });
                return;
            }

            var btn = $('#btn_submit_promote');
            btn.attr('data-kt-indicator', 'on').prop('disabled', true);

            $.ajax({
                url: '/students/promote/process',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    student_ids: selectedIds,
                    target_class_id: $('#target_class').val(),
                    target_batch_id: $('#target_batch').val()
                },
                success: function (res) {
                    btn.removeAttr('data-kt-indicator').prop('disabled', false);
                    Swal.fire({ text: res.message, icon: "success", buttonsStyling: false, confirmButtonText: "Ok", customClass: { confirmButton: "btn btn-primary" } }).then(() => { location.reload(); });
                },
                error: function (err) {
                    btn.removeAttr('data-kt-indicator').prop('disabled', false);
                    Swal.fire({ text: err.responseJSON.message || "Promotion failed.", icon: "error", buttonsStyling: false, confirmButtonText: "Ok", customClass: { confirmButton: "btn btn-primary" } });
                }
            });
        });
    };

    return {
        init: function () {
            handleBatchLoading();
            handleClassFiltering();
            handleFilter();
            handlePromotion();
            $('#check_all').on('change', function () { $('.student-checkbox').prop('checked', $(this).is(':checked')); });
        }
    };
}();

KTUtil.onDOMContentLoaded(function () {
    KTStudentsPromote.init();
});
