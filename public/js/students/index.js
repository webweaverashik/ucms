"use strict";

var KTStudentsList = function () {
    // Define shared variables
    var table;
    var datatable;

    // Private functions
    var initDatatable = function () {
        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatable = $(table).DataTable({
            "info": true,
            'order': [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 25,
            "lengthChange": true,
            "autoWidth": false,  // Disable auto width
            'columnDefs': [
                { orderable: false, targets: 9 }, // Disable ordering on column Guardian                
                { orderable: false, targets: 15 }, // Disable ordering on column Actions                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {
            // initToggleToolbar();
            // toggleToolbars();
        });
    }

    // Hook export buttons
    var exportButtons = () => {
        const documentTitle = 'Student Lists Report';

        var buttons = new $.fn.dataTable.Buttons(datatable, {
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
                    title: documentTitle, exportOptions: {
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
                        // Set page margins [left, top, right, bottom]
                        doc.pageMargins = [20, 20, 20, 40]; // reduce from default 40

                        // Optional: Set font size globally
                        doc.defaultStyle.fontSize = 10;

                        // Optional: Set header or footer
                        doc.footer = getPdfFooterWithPrintTime(); // your custom footer function
                    }
                }

            ]
        }).container().appendTo('#kt_hidden_export_buttons'); // or a hidden container

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

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-subscription-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    // Filter Datatable
    var handleFilter = function () {
        // Select filter options
        const filterForm = document.querySelector('[data-kt-subscription-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-subscription-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-subscription-table-filter="reset"]');
        const selectOptions = filterForm.querySelectorAll('select');

        // Filter datatable on submit
        filterButton.addEventListener('click', function () {
            var filterString = '';

            // Get filter values
            selectOptions.forEach((item, index) => {
                if (item.value && item.value !== '') {
                    if (index !== 0) {
                        filterString += ' ';
                    }

                    // Build filter value options
                    filterString += item.value;
                }
            });

            // Filter datatable --- official docs reference: https://datatables.net/reference/api/search()
            datatable.search(filterString).draw();
        });

        // Reset datatable
        resetButton.addEventListener('click', function () {
            // Reset filter form
            selectOptions.forEach((item, index) => {
                // Reset Select2 dropdown --- official docs reference: https://select2.org/programmatic-control/add-select-clear-items
                $(item).val(null).trigger('change');
            });

            // Filter datatable --- official docs reference: https://datatables.net/reference/api/search()
            datatable.search('').draw();
        });
    }

    // Delete students
    const handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-student');
            if (!deleteBtn) return;

            e.preventDefault();

            let studentId = deleteBtn.getAttribute('data-student-id');
            console.log('Student ID:', studentId);

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
                                    location.reload(); // Reload to reflect changes
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
            console.log('Student ID:', studentId);

            const studentName = toggleButton.getAttribute('data-student-name');
            const studentUniqueId = toggleButton.getAttribute('data-student-unique-id');
            const activeStatus = toggleButton.getAttribute('data-active-status');

            // Set hidden input values
            document.getElementById('student_id').value = studentId;
            document.getElementById('activation_status').value = (activeStatus === 'active') ? 'inactive' : 'active';

            // Update modal text
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
            table = document.getElementById('kt_students_table');

            if (!table) {
                return;
            }

            initDatatable();
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
    KTStudentsList.init();
});