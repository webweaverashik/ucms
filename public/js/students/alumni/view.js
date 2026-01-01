"use strict";

var KTStudentsActions = function () {
    // Delete pending students
    const handleDeletion = function () {
        document.querySelectorAll('.delete-student').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                let studentId = this.getAttribute('data-student-id');
                let url = routeDeleteStudent.replace(':id', studentId);  // Replace ':id' with actual student ID

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
                                        window.location.href = '/students/alumni';
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
            handleDeletion();
            handleToggleActivationAJAX();
        }
    }
}();

var KTStudentsInvoicesView = function () {
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
                { orderable: false, targets: 7 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }


    var handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const target = e.target.closest('.delete-invoice');
            if (!target) return;

            e.preventDefault();

            const invoiceId = target.getAttribute('data-invoice-id');
            console.log('Invoice to be deleted: ', invoiceId);

            const url = routeDeleteInvoice.replace(':id', invoiceId);  // Replace ':id' with actual invoice ID

            Swal.fire({
                title: "Are you sure to delete this invoice?",
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
                                    text: "The invoice has been deleted successfully.",
                                    icon: "success",
                                }).then(() => {
                                    location.reload(); // Refresh to reflect changes
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.error || "Deletion failed.",
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


    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_student_view_invoices_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleDeletion();
        }
    }
}();

var KTEditInvoiceModal = function () {
    // Shared variables
    const element = document.getElementById('kt_modal_edit_invoice');

    // Early return if element doesn't exist
    if (!element) {
        console.error('Modal element not found');
        return {
            init: function () { }
        };
    }

    const form = element.querySelector('#kt_modal_edit_invoice_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);

    let invoiceId = null; // Declare globally

    // Init edit invoice modal
    var initEditInvoice = () => {
        // Cancel and close button handlers
        ['cancel', 'close'].forEach(action => {
            const btn = element.querySelector(`[data-kt-edit-invoice-modal-action="${action}"]`);
            if (btn) {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    if (form) form.reset();
                    modal.hide();
                });
            }
        });

        // Delegate edit button click using document
        document.addEventListener('click', function (e) {
            const button = e.target.closest("[data-bs-target='#kt_modal_edit_invoice']");
            if (!button) return;

            invoiceId = button.getAttribute("data-invoice-id");
            if (!invoiceId) return;

            console.log("Invoice ID:", invoiceId);
            if (form) form.reset();

            fetch(`/invoices/${invoiceId}/view-ajax`)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            // Show error from Laravel if available
                            throw new Error(errorData.message || 'Network response was not ok');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success || !data.data) throw new Error(data.message || 'Invalid response data');

                    const invoice = data.data;

                    // Set modal title
                    const titleEl = document.getElementById("kt_modal_edit_invoice_title");
                    if (titleEl) {
                        titleEl.textContent = `Update Invoice ${invoice.invoice_number}`;
                    }

                    // Show/hide #month_year_id_edit based on invoice_type
                    const monthYearWrapper = document.querySelector("#month_year_id_edit");
                    if (monthYearWrapper) {
                        monthYearWrapper.style.display = (invoice.invoice_type === 'tuition_fee') ? '' : 'none';
                    }

                    // Populate inputs
                    document.querySelector("input[name='invoice_amount_edit']").value = invoice.total_amount;

                    // Helper for select2 values
                    const setSelect2Value = (name, value) => {
                        const el = $(`select[name="${name}"]`);
                        if (el.length) {
                            el.val(value).trigger('change');
                        }
                    };

                    setSelect2Value("invoice_type_edit", invoice.invoice_type);

                    // Month-year handling
                    const monthYearSelect = $("select[name='invoice_month_year_edit']");
                    if (monthYearSelect.length) {
                        monthYearSelect.empty();
                        const [month, year] = (invoice.month_year || '').split('_');
                        const monthName = new Date(`${year}-${month}-01`).toLocaleString('default', { month: 'long' });

                        const formatted = `${monthName} ${year}`;
                        const option = new Option(formatted, invoice.month_year, true, true);
                        monthYearSelect.append(option).trigger('change');
                    }

                    modal.show();
                })
                .catch(error => {
                    console.error("Error:", error);
                    toastr.error(error.message || "Failed to load invoice details");
                });
        });
    };


    // Form validation
    var initValidation = function () {
        if (!form) return;

        var validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'invoice_amount_edit': {
                        validators: {
                            notEmpty: {
                                message: 'Amount is required'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                    })
                }
            }
        );

        const submitButton = element.querySelector('[data-kt-edit-invoice-modal-action="submit"]');

        if (submitButton && validator) {
            submitButton.addEventListener('click', function (e) {
                e.preventDefault(); // Prevent default button behavior

                validator.validate().then(function (status) {
                    if (status === 'Valid') {
                        // Show loading indicator
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        const formData = new FormData(form);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        formData.append('_method', 'PUT');

                        fetch(`/invoices/${invoiceId}`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => {
                                if (!response.ok) throw new Error('Network response was not ok');
                                return response.json();
                            })
                            .then(data => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (data.success) {
                                    toastr.success(data.message || 'Invoice updated successfully');
                                    modal.hide();
                                    window.location.reload();
                                } else {
                                    throw new Error(data.message || 'Invoice Update failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to update invoice');
                                console.error('Error:', error);
                            });
                    } else {
                        toastr.warning('Please fill all required fields correctly');
                    }
                });
            });
        }

    }

    return {
        init: function () {
            initEditInvoice();
            initValidation();
        }
    };
}();

var KTStudentsTransactionsView = function () {
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
                { orderable: false, targets: 7 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }

    // Delete Transaction
    const handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-txn');
            if (!deleteBtn) return;

            e.preventDefault();

            let txnId = deleteBtn.getAttribute('data-txn-id');
            console.log('TXN ID:', txnId);

            let url = routeDeleteTxn.replace(':id', txnId);

            Swal.fire({
                title: 'Are you sure you want to delete?',
                text: "Once deleted, this transaction will be removed.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
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
                                    title: 'Success!',
                                    text: 'Transaction deleted successfully.',
                                    icon: 'success',
                                    confirmButtonText: 'Okay',
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Failed!', 'Transaction could not be deleted.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error("Fetch Error:", error);
                            Swal.fire('Failed!', 'An error occurred. Please contact support.', 'error');
                        });
                }
            });
        });
    };

    // Transaction approval AJAX
    const handleTransactionApproval = function () {
        document.querySelectorAll('.approve-txn').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                let txnId = this.getAttribute('data-txn-id');
                console.log("TXN ID: ", txnId);

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to approve this transaction?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, approve!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/transactions/${txnId}/approve`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: "Approved!",
                                        text: "Transaction approved successfully.",
                                        icon: "success",
                                    }).then(() => {
                                        location.reload(); // Reload to reflect changes
                                    });
                                } else {
                                    Swal.fire({
                                        title: "Error!",
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
        });
    };

    // Statement Download Handler
    const handleStatementDownload = function () {
        document.addEventListener('click', function (e) {
            const downloadBtn = e.target.closest('.download-statement');
            if (!downloadBtn) return;

            e.preventDefault();

            const studentId = downloadBtn.getAttribute('data-student-id');
            const year = downloadBtn.getAttribute('data-year');

            if (!studentId || !year) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Missing student or year information.',
                    icon: 'error',
                });
                return;
            }

            // Show loading state on button
            const originalIcon = downloadBtn.innerHTML;
            downloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            downloadBtn.style.pointerEvents = 'none';

            // Create FormData for POST request
            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('statement_year', year);

            fetch(routeDownloadStatement, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        // Try to parse error message from response
                        return response.text().then(text => {
                            throw new Error(text || 'Server error occurred');
                        });
                    }
                    return response.text();
                })
                .then(html => {
                    // Create a new window with the HTML content
                    const printWindow = window.open("", "_blank", "width=900,height=700,scrollbars=yes,resizable=yes");

                    if (printWindow) {
                        printWindow.document.open();
                        printWindow.document.write(html);
                        printWindow.document.close();

                        // Focus on the new window
                        printWindow.focus();
                    } else {
                        // Popup blocked
                        Swal.fire({
                            title: 'Popup Blocked!',
                            text: 'Please allow popups for this website to view the statement.',
                            icon: 'warning',
                        });
                    }

                    // Restore button state
                    downloadBtn.innerHTML = originalIcon;
                    downloadBtn.style.pointerEvents = 'auto';
                })
                .catch(error => {
                    console.error("Statement Download Error:", error);

                    // Check if the error message indicates no transactions
                    const errorMessage = error.message.toLowerCase();
                    if (errorMessage.includes('no transactions')) {
                        Swal.fire({
                            title: 'No Data Found',
                            text: 'No transactions found for the selected year.',
                            icon: 'info',
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to load statement. Please try again.',
                            icon: 'error',
                        });
                    }

                    // Restore button state
                    downloadBtn.innerHTML = originalIcon;
                    downloadBtn.style.pointerEvents = 'auto';
                });
        });
    };

    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_student_view_transactions_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleDeletion();
            handleTransactionApproval();
            handleStatementDownload();
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
        const filterSearch = document.querySelector('[data-kt-notes-distribution-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }


    // Filter Datatable
    var handleFilter = function () {
        // Select filter options
        const filterForm = document.querySelector('[data-kt-notes-distribution-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="reset"]');
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

    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_student_view_sheets_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleSearch();
            handleFilter();
        }
    }
}();

var KTStudentsActivity = function () {
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
                { orderable: false, targets: 2 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }


    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_students_acitivation_table');

            if (!table) {
                return;
            }

            initDatatable();
        }
    }
}();

// var KTStudentViewAttendance = function () {
//     // Shared variables
//     var calendar;
//     var calendarEl;

//     // --- 1. Calendar Logic ---
//     var initCalendar = function () {
//         calendarEl = document.getElementById('kt_attendance_calendar');

//         if (!calendarEl) {
//             return;
//         }

//         var eventsData = JSON.parse(calendarEl.getAttribute('data-events'));

//         calendar = new FullCalendar.Calendar(calendarEl, {
//             // Define header toolbar
//             headerToolbar: {
//                 left: 'prev,next today',
//                 center: 'title',
//                 right: 'dayGridMonth,listMonth'
//             },

//             // --- Custom View Settings ---
//             views: {
//                 listMonth: {
//                     buttonText: 'List',
//                     displayEventTime: false,   // Hides "All day" text
//                     listDayFormat: false,      // Hides default Date Header rows (optional, keeps list clean)
//                     listDaySideFormat: false   // Hides side headers
//                 }
//             },

//             initialView: 'dayGridMonth',
//             height: 'auto',
//             contentHeight: 650,
//             aspectRatio: 3,
//             initialDate: new Date(),
//             navLinks: true,
//             editable: false,
//             dayMaxEvents: true,
//             events: eventsData,

//             // Tooltip Logic (Works for both Grid and List views)
//             eventDidMount: function (info) {
//                 var remarks = info.event.extendedProps.description;
//                 if (remarks) {
//                     new bootstrap.Tooltip(info.el, {
//                         title: remarks,
//                         placement: 'top',
//                         trigger: 'hover',
//                         container: 'body'
//                     });
//                 }
//             },

//             // --- CUSTOM CONTENT RENDERING ---
//             eventContent: function (arg) {
//                 // A. Logic for LIST View (Show Date + Status)
//                 if (arg.view.type === 'listMonth') {
//                     // 1. Format Date manually to 07-Dec-2025
//                     var dateObj = arg.event.start;
//                     var day = dateObj.getDate().toString().padStart(2, '0');
//                     var month = dateObj.toLocaleString('en-US', { month: 'short' });
//                     var year = dateObj.getFullYear();
//                     var formattedDate = day + '-' + month + '-' + year;

//                     // 2. Return Custom HTML layout
//                     return {
//                         html: `
//                             <div class="d-flex align-items-center">
//                                 <span class="min-w-100px fw-bold text-gray-800 fs-6 me-4">${formattedDate}</span>
//                                 <span class="badge" style="background-color: ${arg.event.backgroundColor}; color: white; font-size: 0.9rem;">
//                                     ${arg.event.title}
//                                 </span>
//                             </div>
//                         `
//                     };
//                 }

//                 // B. Logic for GRID View (Standard Month View)
//                 return {
//                     html: '<div class="fc-content" style="color:white; padding:1px 2px;">' + arg.event.title + '</div>'
//                 };
//             }
//         });

//         calendar.render();
//     }

//     // --- 2. Pie Chart Logic ---
//     var initPieChart = function () {
//         var wrapper = document.getElementById('kt_attendance_pie_chart_wrapper');
//         var canvas = document.getElementById('kt_attendance_pie_chart');

//         if (!wrapper || !canvas) {
//             return;
//         }

//         // 1. Get Data
//         var eventsData = JSON.parse(wrapper.getAttribute('data-events'));

//         // 2. Filter Data for Current Month
//         var now = new Date();
//         var currentMonth = now.getMonth();
//         var currentYear = now.getFullYear();

//         var stats = { present: 0, absent: 0, late: 0, others: 0 };

//         eventsData.forEach(function (event) {
//             var eventDate = new Date(event.start);
//             if (eventDate.getMonth() === currentMonth && eventDate.getFullYear() === currentYear) {
//                 var status = event.title.toLowerCase();
//                 if (status === 'present') stats.present++;
//                 else if (status === 'absent') stats.absent++;
//                 else if (status === 'late') stats.late++;
//                 else stats.others++;
//             }
//         });

//         // 3. Define Chart Data
//         var data = {
//             labels: ['Present', 'Absent', 'Late'],
//             datasets: [{
//                 data: [stats.present, stats.absent, stats.late, stats.others],
//                 backgroundColor: ['#50cd89', '#f1416c', '#ffc700'],
//                 borderWidth: 0,
//                 hoverOffset: 4
//             }]
//         };

//         // 4. Render Chart
//         var ctx = canvas.getContext('2d');
//         new Chart(ctx, {
//             type: 'pie',
//             data: data,
//             // Register the DataLabels plugin here
//             plugins: [ChartDataLabels],
//             options: {
//                 responsive: true,
//                 maintainAspectRatio: false,
//                 plugins: {
//                     legend: {
//                         position: 'bottom',
//                         labels: { usePointStyle: true, padding: 20, font: { size: 13 } }
//                     },
//                     tooltip: {
//                         callbacks: {
//                             label: function (context) {
//                                 var value = context.raw || 0;
//                                 return context.label + ': ' + value + ' days';
//                             }
//                         }
//                     },
//                     // --- DATALABELS CONFIGURATION (Percentage on Chart) ---
//                     datalabels: {
//                         color: '#ffffff', // White text
//                         font: {
//                             weight: 'bold',
//                             size: 14
//                         },
//                         formatter: function (value, context) {
//                             // Hide label if value is 0
//                             if (value === 0) return null;

//                             // Calculate Total
//                             var dataset = context.chart.data.datasets[0].data;
//                             var total = dataset.reduce((acc, val) => acc + val, 0);

//                             // Calculate Percentage
//                             var percentage = Math.round((value / total) * 100) + '%';

//                             return percentage;
//                         }
//                     }
//                 }
//             }
//         });
//     }

//     // --- 3. Handle Tab Logic ---
//     var handleTabSwitch = function () {
//         var tabLink = document.querySelector('a[href="#kt_student_view_attendance_tab"]');
//         if (!tabLink) {
//             tabLink = document.querySelector('button[data-bs-target="#kt_student_view_attendance_tab"]');
//         }

//         if (tabLink) {
//             tabLink.addEventListener('shown.bs.tab', function (e) {
//                 if (calendar) {
//                     calendar.updateSize();
//                 }
//             });
//         }
//     }

//     return {
//         init: function () {
//             initCalendar();
//             initPieChart();
//             handleTabSwitch();
//         }
//     };
// }();


// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTStudentsActions.init();
    KTStudentsInvoicesView.init();
    KTEditInvoiceModal.init();
    KTStudentsTransactionsView.init();
    KTStudentsSheetsView.init();
    KTStudentsActivity.init();
    // KTStudentViewAttendance.init();
});