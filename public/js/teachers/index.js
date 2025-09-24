"use strict";

var KTAllTeachersList =  function () {
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
                        { orderable: false, targets: 5 }, // Disable ordering on column Actions                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Hook export buttons
      var exportButtons = () => {
            const documentTitle = 'Teachers List';

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
            const filterSearch = document.querySelector('[data-teachers-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      // Filter Datatable
      // var handleFilter = function () {
      //       // Select filter options
      //       const filterForm = document.querySelector('[data-teachers-table-filter="form"]');
      //       const filterButton = filterForm.querySelector('[data-teachers-table-filter="filter"]');
      //       const resetButton = filterForm.querySelector('[data-teachers-table-filter="reset"]');
      //       const selectOptions = filterForm.querySelectorAll('select');

      //       // Filter datatable on submit
      //       filterButton.addEventListener('click', function () {
      //             var filterString = '';

      //             // Get filter values
      //             selectOptions.forEach((item, index) => {
      //                   if (item.value && item.value !== '') {
      //                         if (index !== 0) {
      //                               filterString += ' ';
      //                         }

      //                         // Build filter value options
      //                         filterString += item.value;
      //                   }
      //             });

      //             // Filter datatable --- official docs reference: https://datatables.net/reference/api/search()
      //             datatable.search(filterString).draw();
      //       });

      //       // Reset datatable
      //       resetButton.addEventListener('click', function () {
      //             // Reset filter form
      //             selectOptions.forEach((item, index) => {
      //                   // Reset Select2 dropdown --- official docs reference: https://select2.org/programmatic-control/add-select-clear-items
      //                   $(item).val(null).trigger('change');
      //             });

      //             // Filter datatable --- official docs reference: https://datatables.net/reference/api/search()
      //             datatable.search('').draw();
      //       });
      // }


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

      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_teachers_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  exportButtons();
                  handleSearch();
                  // handleFilter();
                  handleDeletion();
            }
      }
}();


var KTAddTeacher = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_add_teacher');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_add_teacher_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);


      var initCloseModal = () => {

            // Reset Select2 inputs

            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-add-teacher-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-add-teacher-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();

                        modal.hide();
                  });
            }
      }

      return {
            init: function () {
                  // initAddTransaction();
                  initCloseModal();
            }
      };
}();


// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTAllTeachersList.init();
      KTAddTeacher.init();
});