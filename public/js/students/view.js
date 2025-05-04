"use strict";

var KTStudentsView = function () {
    // Define shared variables
    var table;
    var datatable;
    // var toolbarBase;
    // var toolbarSelected;
    // var selectedCount;

    // Private functions
    var initDatatable = function () {
        // Set date data order
        // const tableRows = table.querySelectorAll('tbody tr');

        // tableRows.forEach(row => {
        //     const dateRow = row.querySelectorAll('td');
        //     const realDate = moment(dateRow[10].innerHTML, "DD MMM YYYY, LT").format(); // select date from 4th column in table
        //     dateRow[10].setAttribute('data-order', realDate);
        // });

        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatable = $(table).DataTable({
            "info": true,
            'order': [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 25,
            "lengthChange": true,
            "autoWidth": false,  // Disable auto width
            'columnDefs': [
                { orderable: false, targets: 6 }, // Disable ordering on column Guardian                
                { orderable: false, targets: 12 }, // Disable ordering on column Actions                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {
            // initToggleToolbar();
            // toggleToolbars();
        });
    }

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

    // Delete pending students
    const handleDeletion = function () {
        document.querySelectorAll('.delete-student').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                let studentId = this.getAttribute('data-student-id');
                let url = routeDeleteStudent.replace(':id', studentId);  // Replace ':id' with actual student ID

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
                                        window.location.href = '/students';
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
        });
    };

    // Toggle activation modal AJAX
    const handleToggleActivationAJAX = function () {
        const toggleButtons = document.querySelectorAll('[data-bs-target="#kt_toggle_activation_student_modal"]');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const studentId = this.getAttribute('data-student-id');
                const studentName = this.getAttribute('data-student-name');
                const studentUniqueId = this.getAttribute('data-student-unique-id');
                const activeStatus = this.getAttribute('data-active-status');

                // Set hidden field values
                document.getElementById('student_id').value = studentId;
                document.getElementById('activation_status').value = (activeStatus === 'active') ? 'inactive' : 'active';


                // Update modal title and label
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
        });
    }

    return {
        // Public functions  
        init: function () {
            // table = document.getElementById('kt_students_table');

            // if (!table) {
            //     return;
            // }

            // initDatatable();
            // initToggleToolbar();
            // handleSearch();
            handleDeletion();
            handleToggleActivationAJAX();
            // handleFilter();
        }
    }
}();


var KTStudentsTuitionFeeView = function () {
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
            "pageLength": 10,
            "lengthChange": true,
            "autoWidth": false,  // Disable auto width
            'columnDefs': [
                { orderable: false, targets: 4 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-subscription-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_student_view_transactions_table');

            if (!table) {
                return;
            }

            initDatatable();
            // handleSearch();
        }
    }
}();

var KTStudentsSheetsView = function () {
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
            "pageLength": 10,
            "lengthChange": true,
            "autoWidth": false,  // Disable auto width
            'columnDefs': [
                { orderable: false, targets: 4 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-subscription-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_student_view_sheets_table');

            if (!table) {
                return;
            }

            initDatatable();
            // handleSearch();
        }
    }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTStudentsView.init();
    KTStudentsTuitionFeeView.init();
    KTStudentsSheetsView.init();
});