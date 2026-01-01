"use strict";

var KTNotesDistributionList = function () {
    // Define shared variables
    var table;
    var datatable;

    // Private functions
    var initDatatable = function () {
        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatable = $(table).DataTable({
            "info": true,
            'order': [], // Order by distributed date descending
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

    // Handle Sheet Group change to load Topics via AJAX
    var handleSheetGroupChange = function () {
        const sheetGroupSelect = document.getElementById('filter_sheet_group');
        const topicSelect = document.getElementById('filter_topic');

        if (!sheetGroupSelect || !topicSelect) return;

        $(sheetGroupSelect).on('change', function () {
            const sheetId = $(this).val();

            // Reset topic select
            $(topicSelect).empty().append('<option></option>').prop('disabled', true).trigger('change');

            if (!sheetId) return;

            // Fetch topics via AJAX
            $.ajax({
                url: `/sheets/${sheetId}/topics-list`,
                method: 'GET',
                beforeSend: function () {
                    $(topicSelect).prop('disabled', true);
                },
                success: function (response) {
                    if (response.success && response.topics) {
                        response.topics.forEach(function (topic) {
                            const groupBadge = topic.academic_group !== 'General'
                                ? ` (${topic.academic_group})`
                                : '';
                            $(topicSelect).append(
                                `<option value="${topic.name}">${topic.name} - ${topic.subject}${groupBadge}</option>`
                            );
                        });
                        $(topicSelect).prop('disabled', false);
                    }
                },
                error: function () {
                    toastr.error('Failed to load topics');
                },
                complete: function () {
                    $(topicSelect).prop('disabled', false);
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
        const topicSelect = document.getElementById('filter_topic');
        const subjectSelect = document.getElementById('filter_subject');

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

                // Get topic filter value
                if (topicSelect && topicSelect.value) {
                    if (filterString) filterString += ' ';
                    filterString += topicSelect.value;
                }

                // Get subject filter value
                if (subjectSelect && subjectSelect.value) {
                    if (filterString) filterString += ' ';
                    filterString += subjectSelect.value;
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
                $(topicSelect).empty().append('<option></option>').prop('disabled', true).trigger('change');
                $(subjectSelect).val(null).trigger('change');

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
            handleFilter();
        }
    }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTNotesDistributionList.init();
});