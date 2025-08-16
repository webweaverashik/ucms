"use strict";

var KTSMSList = function () {
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
                        { orderable: false, targets: 6 }, // Disable ordering on column Actions                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Hook export buttons
      var exportButtons = () => {
            const documentTitle = 'SMS Campaigns Report';

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
            const filterSearch = document.querySelector('[data-sms-campaigns-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      // Filter Datatable
      var handleFilter = function () {
            // Select filter options
            const filterForm = document.querySelector('[data-sms-campaigns-table-filter="form"]');
            const filterButton = filterForm.querySelector('[data-sms-campaigns-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-sms-campaigns-table-filter="reset"]');
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


      // campaign approval AJAX
      var handleApproval = function () {
            document.addEventListener('click', function (e) {
                  const approveBtn = e.target.closest('.approve-campaign');
                  if (!approveBtn) return; // only continue if button clicked

                  e.preventDefault();

                  let campaignId = approveBtn.getAttribute('data-campaign-id');
                  console.log("Campaign ID:", campaignId);

                  Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you want to approve this campaign?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, approve!'
                  }).then((result) => {
                        if (result.isConfirmed) {
                              fetch(`/sms/send-campaign/${campaignId}/approve`, {
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
                                                      text: "Campaign approved successfully.",
                                                      icon: "success",
                                                }).then(() => {
                                                      location.reload();
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
      };


      // Delete campaign
      const handleDeletion = function () {
            document.addEventListener('click', function (e) {
                  const deleteBtn = e.target.closest('.delete-campaign');
                  if (!deleteBtn) return;

                  e.preventDefault();

                  let campaignId = deleteBtn.getAttribute('data-campaign-id');
                  console.log('Campaign ID:', campaignId);

                  let url = routeDeleteCampaign.replace(':id', campaignId);

                  Swal.fire({
                        title: 'Are you sure you want to delete?',
                        text: "Once deleted, this campaign will be removed.",
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
                                                      text: 'Campaign deleted successfully.',
                                                      icon: 'success',
                                                      confirmButtonText: 'Okay',
                                                }).then(() => {
                                                      location.reload();
                                                });
                                          } else {
                                                Swal.fire('Failed!', 'Campaign could not be deleted.', 'error');
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
                  table = document.getElementById('kt_sms_campaigns_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  exportButtons();
                  handleSearch();
                  handleFilter();
                  handleDeletion();
                  handleApproval();
            }
      }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTSMSList.init();
});