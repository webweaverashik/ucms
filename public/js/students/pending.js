"use strict";

var KTPendingStudentsList = function () {
    // Define shared variables
    var datatables = {};
    var activeDatatable = null;
    var initializedTabs = {};

    // Get DataTable config
    var getDataTableConfig = function () {
        return {
            "info": true,
            'order': [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 25,
            "lengthChange": true,
            "autoWidth": false,
            'columnDefs': [
                { orderable: false, targets: 10 },
                { orderable: false, targets: 16 },
            ]
        };
    };

    // Initialize a single datatable
    var initSingleDatatable = function (tableId) {
        var table = document.getElementById(tableId);
        if (!table) {
            return null;
        }

        var config = getDataTableConfig();
        var datatable = $(table).DataTable(config);

        datatable.on('draw', function () {
            KTMenu.init();
        });

        return datatable;
    };

    // Initialize datatables for admin (multiple tabs)
    var initAdminDatatables = function () {
        if (branchIds && branchIds.length > 0) {
            var firstBranchId = branchIds[0];
            var firstTableId = 'kt_pending_students_table_branch_' + firstBranchId;
            var firstBranchTable = document.getElementById(firstTableId);

            if (firstBranchTable) {
                datatables[firstBranchId] = initSingleDatatable(firstTableId);
                activeDatatable = datatables[firstBranchId];
                initializedTabs[firstBranchId] = true;
            }
        }

        var tabLinks = document.querySelectorAll('#pendingBranchTabs a[data-bs-toggle="tab"]');
        tabLinks.forEach(function (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function (event) {
                var branchId = event.target.getAttribute('data-branch-id');
                var tableId = 'kt_pending_students_table_branch_' + branchId;

                if (!initializedTabs[branchId]) {
                    datatables[branchId] = initSingleDatatable(tableId);
                    initializedTabs[branchId] = true;
                }

                activeDatatable = datatables[branchId];

                if (activeDatatable) {
                    activeDatatable.columns.adjust().draw(false);
                }

                updateExportButtons();
            });
        });
    };

    // Initialize datatable for non-admin (single table)
    var initNonAdminDatatable = function () {
        var table = document.getElementById('kt_pending_students_table');
        if (!table) {
            return;
        }

        datatables['single'] = initSingleDatatable('kt_pending_students_table');
        activeDatatable = datatables['single'];
    };

    // Hook export buttons
    var exportButtons = function () {
        updateExportButtons();

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

        const documentTitle = 'Pending Students Report';

        var hiddenContainer = document.getElementById('kt_hidden_export_buttons');
        if (hiddenContainer) {
            hiddenContainer.innerHTML = '';
        }

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

        resetButton.addEventListener('click', function () {
            selectOptions.forEach((item) => {
                $(item).val(null).trigger('change');
            });

            if (activeDatatable) {
                activeDatatable.search('').draw();
            }
        });
    };

    // Delete pending students
    const handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-student');
            if (!deleteBtn) return;

            e.preventDefault();
            let studentId = deleteBtn.getAttribute('data-student-id');
            let url = routeDeleteStudent.replace(':id', studentId);

            Swal.fire({
                title: "Are you sure to delete this student?",
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
                                    text: "The student has been removed successfully.",
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

    // Send approval request
    const sendApprovalRequest = function (studentId, confirmDue = false) {
        const url = routeApproveStudent.replace(':id', studentId);

        return fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
            },
            body: JSON.stringify({
                active_status: "active",
                confirm_due: confirmDue
            }),
        }).then(response => response.json());
    };

    // Handle student approval
    const handleApproval = function () {
        document.addEventListener('click', function (e) {
            const approveBtn = e.target.closest('.approve-student');
            if (!approveBtn) return;

            e.preventDefault();
            const studentId = approveBtn.getAttribute('data-student-id');
            const studentName = approveBtn.getAttribute('data-student-name');

            Swal.fire({
                title: 'Approve Student?',
                text: `Do you want to approve "${studentName}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#50cd89',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, approve!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we approve the student.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    sendApprovalRequest(studentId, false)
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: "Approved!",
                                    text: "The student has been approved successfully.",
                                    icon: "success",
                                }).then(() => {
                                    location.reload();
                                });
                            } else if (data.requires_confirmation && isAdmin) {
                                // Admin: Show confirmation for due tuition fee
                                Swal.fire({
                                    title: 'Tuition Fee Due',
                                    text: 'Would you like to approve this student? This student tuition fee is still due.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#50cd89',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Yes, approve anyway!',
                                    cancelButtonText: 'Cancel'
                                }).then((confirmResult) => {
                                    if (confirmResult.isConfirmed) {
                                        // Show loading again
                                        Swal.fire({
                                            title: 'Processing...',
                                            text: 'Please wait while we approve the student.',
                                            allowOutsideClick: false,
                                            allowEscapeKey: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });

                                        // Send request with confirmation
                                        sendApprovalRequest(studentId, true)
                                            .then(data => {
                                                if (data.success) {
                                                    Swal.fire({
                                                        title: "Approved!",
                                                        text: "The student has been approved successfully.",
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
                            } else {
                                // Manager or other error
                                Swal.fire({
                                    title: "Cannot Approve",
                                    text: data.message,
                                    icon: "warning",
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

    return {
        // Public functions
        init: function () {
            if (typeof isAdmin !== 'undefined' && isAdmin) {
                initAdminDatatables();
            } else {
                initNonAdminDatatable();
            }

            exportButtons();
            handleSearch();
            handleDeletion();
            handleApproval();
            handleFilter();
        }
    }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTPendingStudentsList.init();
});