"use strict";

/**
 * Notes Distribution Index JavaScript
 * Handles DataTable and AJAX filters for Sheet Group, Subject, and Topic
 */
var KTNotesDistributionList = function () {
    // Define shared variables
    var table;
    var datatable;

    // Private functions
    var initDatatable = function () {
        // Init datatable
        datatable = $(table).DataTable({
            "info": true,
            'order': [[6, 'desc']], // Order by distributed date descending
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 25,
            "lengthChange": true,
            "autoWidth": false,
            'columnDefs': [
                { orderable: false, targets: 0 }, // SL column
            ]
        });

        // Re-init functions on every table re-draw
        datatable.on('draw', function () {
            // Update SL numbers after draw
            datatable.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1;
            });
        });
    }

    // Search Datatable
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-notes-distribution-table-filter="search"]');
        if (filterSearch) {
            filterSearch.addEventListener('keyup', function (e) {
                datatable.search(e.target.value).draw();
            });
        }
    }

    // Handle Sheet Group change to load Subjects via AJAX
    var handleSheetGroupChange = function () {
        const sheetGroupSelect = document.getElementById('filter_sheet_group');
        const subjectSelect = document.getElementById('filter_subject');
        const topicSelect = document.getElementById('filter_topic');

        if (!sheetGroupSelect || !subjectSelect || !topicSelect) return;

        $(sheetGroupSelect).on('change', function () {
            const sheetId = $(this).val();

            // Reset subject and topic selects
            $(subjectSelect).empty().append('<option></option>').prop('disabled', true).trigger('change');
            $(topicSelect).empty().append('<option></option>').prop('disabled', true).trigger('change');

            if (!sheetId) return;

            // Fetch subjects via AJAX
            $.ajax({
                url: `/sheets/${sheetId}/subjects-list`,
                method: 'GET',
                beforeSend: function () {
                    $(subjectSelect).prop('disabled', true);
                },
                success: function (response) {
                    if (response.success && response.subjects) {
                        response.subjects.forEach(function (subject) {
                            const groupBadge = subject.academic_group !== 'General'
                                ? ` (${subject.academic_group})`
                                : '';
                            $(subjectSelect).append(
                                `<option value="${subject.name}" data-subject-id="${subject.id}">${subject.name}${groupBadge}</option>`
                            );
                        });
                        $(subjectSelect).prop('disabled', false);
                    }
                },
                error: function () {
                    toastr.error('Failed to load subjects');
                },
                complete: function () {
                    // Keep disabled if no sheet selected
                    if (!sheetId) {
                        $(subjectSelect).prop('disabled', true);
                    }
                }
            });
        });
    }

    // Handle Subject change to load Topics via AJAX
    var handleSubjectChange = function () {
        const sheetGroupSelect = document.getElementById('filter_sheet_group');
        const subjectSelect = document.getElementById('filter_subject');
        const topicSelect = document.getElementById('filter_topic');

        if (!subjectSelect || !topicSelect) return;

        $(subjectSelect).on('change', function () {
            const sheetId = $(sheetGroupSelect).val();
            const subjectId = $(this).find(':selected').data('subject-id');

            // Reset topic select
            $(topicSelect).empty().append('<option></option>').prop('disabled', true).trigger('change');

            if (!sheetId || !subjectId) return;

            // Fetch topics via AJAX
            $.ajax({
                url: `/sheets/${sheetId}/subjects/${subjectId}/topics`,
                method: 'GET',
                beforeSend: function () {
                    $(topicSelect).prop('disabled', true);
                },
                success: function (response) {
                    if (response.success && response.topics) {
                        response.topics.forEach(function (topic) {
                            $(topicSelect).append(
                                `<option value="${topic.name}">${topic.name}</option>`
                            );
                        });
                        $(topicSelect).prop('disabled', false);
                    }
                },
                error: function () {
                    toastr.error('Failed to load topics');
                },
                complete: function () {
                    if (!subjectId) {
                        $(topicSelect).prop('disabled', true);
                    }
                }
            });
        });
    }

    // Filter Datatable
    var handleFilter = function () {
        const filterForm = document.querySelector('[data-kt-notes-distribution-table-filter="form"]');
        if (!filterForm) return;

        const filterButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="reset"]');

        const sheetGroupSelect = document.getElementById('filter_sheet_group');
        const subjectSelect = document.getElementById('filter_subject');
        const topicSelect = document.getElementById('filter_topic');

        // Filter datatable on submit
        if (filterButton) {
            filterButton.addEventListener('click', function () {
                var filterString = '';

                // Get sheet group filter value (use the display text for filtering)
                if (sheetGroupSelect && sheetGroupSelect.value) {
                    const selectedOption = sheetGroupSelect.options[sheetGroupSelect.selectedIndex];
                    const filterValue = selectedOption.getAttribute('data-filter-value');
                    if (filterValue) {
                        filterString += filterValue;
                    }
                }

                // Get subject filter value
                if (subjectSelect && subjectSelect.value) {
                    if (filterString) filterString += ' ';
                    filterString += subjectSelect.value;
                }

                // Get topic filter value
                if (topicSelect && topicSelect.value) {
                    if (filterString) filterString += ' ';
                    filterString += topicSelect.value;
                }

                // Apply filter
                datatable.search(filterString).draw();
            });
        }

        // Reset datatable
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                // Reset all select2 dropdowns
                $(sheetGroupSelect).val(null).trigger('change');
                $(subjectSelect).empty().append('<option></option>').prop('disabled', true).trigger('change');
                $(topicSelect).empty().append('<option></option>').prop('disabled', true).trigger('change');

                // Clear datatable search
                datatable.search('').draw();
            });
        }
    }

    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_notes_distribution_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleSearch();
            handleSheetGroupChange();
            handleSubjectChange();
            handleFilter();
        }
    }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTNotesDistributionList.init();
});