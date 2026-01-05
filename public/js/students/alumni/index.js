"use strict";

var KTAlumniStudentsList = function () {
    // Define shared variables
    var datatables = {};
    var activeDatatable = null;
    var initializedTabs = {};

    // Get DataTable config based on whether branch column is shown
    var getDataTableConfig = function (showBranchColumn) {
        var config = {
            "info": true,
            'order': [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 25,
            "lengthChange": true,
            "autoWidth": false,
            'columnDefs': [
                { orderable: false, targets: 9 }, // Disable ordering on column Guardian
            ]
        };

        // Adjust action column index based on branch column visibility
        if (showBranchColumn) {
            config.columnDefs.push({ orderable: false, targets: 15 }); // Actions column with branch
        } else {
            config.columnDefs.push({ orderable: false, targets: 14 }); // Actions column without branch
        }

        return config;
    };

    // Initialize a single datatable
    var initSingleDatatable = function (tableId, showBranchColumn) {
        var table = document.getElementById(tableId);
        if (!table) {
            return null;
        }

        var config = getDataTableConfig(showBranchColumn);
        var datatable = $(table).DataTable(config);

        // Re-init functions on every table re-draw
        datatable.on('draw', function () {
            KTMenu.init();
        });

        return datatable;
    };

    // Initialize datatables for admin (multiple tabs)
    var initAdminDatatables = function () {
        // Initialize the first branch tab (it's active by default)
        if (branchIds && branchIds.length > 0) {
            var firstBranchId = branchIds[0];
            var firstTableId = 'kt_alumni_students_table_branch_' + firstBranchId;
            var firstBranchTable = document.getElementById(firstTableId);

            if (firstBranchTable) {
                datatables[firstBranchId] = initSingleDatatable(firstTableId, false);
                activeDatatable = datatables[firstBranchId];
                initializedTabs[firstBranchId] = true;
            }
        }

        // Setup tab change event listener for lazy loading
        var tabLinks = document.querySelectorAll('#alumniBranchTabs a[data-bs-toggle="tab"]');
        tabLinks.forEach(function (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function (event) {
                var branchId = event.target.getAttribute('data-branch-id');
                var tableId = 'kt_alumni_students_table_branch_' + branchId;

                // Initialize datatable for this tab if not already done
                if (!initializedTabs[branchId]) {
                    datatables[branchId] = initSingleDatatable(tableId, false);
                    initializedTabs[branchId] = true;
                }

                // Set active datatable
                activeDatatable = datatables[branchId];

                // Adjust columns for responsive display
                if (activeDatatable) {
                    activeDatatable.columns.adjust().draw(false);
                }

                // Reinitialize export buttons for the active table
                updateExportButtons();
            });
        });
    };

    // Initialize datatable for non-admin (single table)
    var initNonAdminDatatable = function () {
        var table = document.getElementById('kt_alumni_students_table');
        if (!table) {
            return;
        }

        datatables['single'] = initSingleDatatable('kt_alumni_students_table', false);
        activeDatatable = datatables['single'];
    };

    // Hook export buttons
    var exportButtons = function () {
        updateExportButtons();

        // Hook dropdown export actions
        const exportItems = document.querySelectorAll('#kt_table_report_dropdown_menu [data-row-export]');
        exportItems.forEach(exportItem => {
            exportItem.addEventListener('click', function (e) {
                e.preventDefault();
                const exportValue = this.getAttribute('data-row-export');
                const target = document.querySelector('.buttons-' + exportValue);
                if (target) {
                    target.click();
                } else {
                    console.warn('Export button not found:', exportValue);
                }
            });
        });
    };

    // Update export buttons for the active datatable
    var updateExportButtons = function () {
        if (!activeDatatable) return;

        const documentTitle = 'Alumni Students Report';

        // Clear existing buttons
        var hiddenContainer = document.getElementById('kt_hidden_export_buttons');
        if (hiddenContainer) {
            hiddenContainer.innerHTML = '';
        }

        // Create new buttons for the active datatable
        new $.fn.dataTable.Buttons(activeDatatable, {
            buttons: [
                {
                    extend: 'copyHtml5',
                    className: 'buttons-copy',
                    title: documentTitle,
                    exportOptions: {
                        columns: ':visible:not(.not-export)'
                    }
                },
                {
                    extend: 'excelHtml5',
                    className: 'buttons-excel',
                    title: documentTitle,
                    exportOptions: {
                        columns: ':visible:not(.not-export)'
                    }
                },
                {
                    extend: 'csvHtml5',
                    className: 'buttons-csv',
                    title: documentTitle,
                    exportOptions: {
                        columns: ':visible:not(.not-export)'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    className: 'buttons-pdf',
                    title: documentTitle,
                    exportOptions: {
                        columns: ':visible:not(.not-export)',
                        modifier: {
                            page: 'all',
                            search: 'applied'
                        }
                    },
                    customize: function (doc) {
                        doc.pageMargins = [20, 20, 20, 40];
                        doc.defaultStyle.fontSize = 10;
                        if (typeof getPdfFooterWithPrintTime === 'function') {
                            doc.footer = getPdfFooterWithPrintTime();
                        }
                    }
                }
            ]
        }).container().appendTo('#kt_hidden_export_buttons');
    };

    // Search Datatable
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-subscription-table-filter="search"]');
        if (!filterSearch) return;

        filterSearch.addEventListener('keyup', function (e) {
            if (activeDatatable) {
                activeDatatable.search(e.target.value).draw();
            }
        });
    };

    // Filter Datatable
    var handleFilter = function () {
        const filterForm = document.querySelector('[data-kt-subscription-table-filter="form"]');
        if (!filterForm) return;

        const filterButton = filterForm.querySelector('[data-kt-subscription-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-subscription-table-filter="reset"]');
        const selectOptions = filterForm.querySelectorAll('select');

        // Filter datatable on submit
        filterButton.addEventListener('click', function () {
            var filterString = '';

            selectOptions.forEach((item, index) => {
                if (item.value && item.value !== '') {
                    if (index !== 0) {
                        filterString += ' ';
                    }
                    filterString += item.value;
                }
            });

            if (activeDatatable) {
                activeDatatable.search(filterString).draw();
            }
        });

        // Reset datatable
        resetButton.addEventListener('click', function () {
            selectOptions.forEach((item) => {
                $(item).val(null).trigger('change');
            });

            if (activeDatatable) {
                activeDatatable.search('').draw();
            }
        });
    };

    // Delete students
    const handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-student');
            if (!deleteBtn) return;

            e.preventDefault();

            let studentId = deleteBtn.getAttribute('data-student-id');
            let url = routeDeleteStudent.replace(':id', studentId);

            Swal.fire({
                title: "Are you sure to delete this Alumni student?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete!",
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        },
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Alumni student deleted successfully.",
                                    icon: "success",
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.message,
                                    icon: "error",
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Fetch Error:", error);
                            Swal.fire({
                                title: "Error!",
                                text: "Something went wrong. Please try again.",
                                icon: "error",
                            });
                        });
                }
            });
        });
    };

    // Toggle activation modal AJAX
    const handleToggleActivationAJAX = function () {
        document.addEventListener('click', function (e) {
            const toggleButton = e.target.closest('[data-bs-target="#kt_toggle_activation_student_modal"]');
            if (!toggleButton) return;

            e.preventDefault();

            const studentId = toggleButton.getAttribute('data-student-id');
            const studentName = toggleButton.getAttribute('data-student-name');
            const studentUniqueId = toggleButton.getAttribute('data-student-unique-id');
            const activeStatus = toggleButton.getAttribute('data-active-status');

            document.getElementById('student_id').value = studentId;
            document.getElementById('activation_status').value = (activeStatus === 'active') ? 'inactive' : 'active';

            const modalTitle = document.getElementById('toggle-activation-modal-title');
            const reasonLabel = document.getElementById('reason_label');

            if (activeStatus === 'active') {
                modalTitle.textContent = `Deactivate Student - ${studentName} (${studentUniqueId})`;
                reasonLabel.textContent = 'Deactivation Reason';
            } else {
                modalTitle.textContent = `Activate Student - ${studentName} (${studentUniqueId})`;
                reasonLabel.textContent = 'Activation Reason';
            }
        });
    };

    return {
        // Public functions  
        init: function () {
            // Check if admin or non-admin based on the presence of tabs
            if (typeof isAdmin !== 'undefined' && isAdmin) {
                initAdminDatatables();
            } else {
                initNonAdminDatatable();
            }

            exportButtons();
            handleSearch();
            handleDeletion();
            handleFilter();
            handleToggleActivationAJAX();
        }
    }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTAlumniStudentsList.init();
});