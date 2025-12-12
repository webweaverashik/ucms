"use strict";

var KTAllTransferHistoryList = function () {
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
                        // { orderable: false, targets: 5 }, // Disable ordering on column Actions             
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
            const filterSearch = document.querySelector('[data-student-transfer-history-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }


      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('student_transfer_history_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  exportButtons();
                  handleSearch();
            }
      }
}();


var KTNewTransfer = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_new_transfer');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_new_transfer_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);

      // Selectors (using jQuery for Select2 compatibility)
      const studentSelect = $('#student_select_input');
      const branchSelect = $('#student_branch_input');
      const batchSelect = $('#student_batch_input');

      // Initialize Select2 dropdowns
      const initSelect2 = () => {
            studentSelect.select2({
                  placeholder: "Select a student first",
                  allowClear: true,
                  dropdownParent: $('#kt_modal_new_transfer')
            });

            branchSelect.select2({
                  placeholder: "Select branch first",
                  allowClear: true,
                  dropdownParent: $('#kt_modal_new_transfer')
            });

            batchSelect.select2({
                  placeholder: "Select batch",
                  allowClear: true,
                  dropdownParent: $('#kt_modal_new_transfer')
            });
      };

      // Reset dependent dropdowns (branch & batch)
      const resetDependentDropdowns = () => {
            branchSelect.empty().append('<option></option>').trigger('change');
            batchSelect.empty().append('<option></option>').trigger('change');
            branchSelect.prop('disabled', true);
            batchSelect.prop('disabled', true);
      };

      // Handle student selection change
      const handleStudentChange = () => {
            studentSelect.on('change', function () {
                  const studentId = $(this).val();

                  // Reset dependent fields
                  resetDependentDropdowns();

                  if (!studentId) {
                        return;
                  }

                  // Enable branch select and load available branches
                  branchSelect.prop('disabled', false);

                  $.ajax({
                        url: window.availableBranchesRoute.replace(':student', studentId),
                        type: 'GET',
                        dataType: 'json',
                        success: function (branches) {
                              branchSelect.empty();
                              branchSelect.append('<option></option>');

                              branches.forEach(branch => {
                                    branchSelect.append(
                                          `<option value="${branch.id}">${branch.branch_name} (${branch.branch_prefix})</option>`
                                    );
                              });

                              branchSelect.trigger('change');
                        },
                        error: function (xhr) {
                              toastr.error('Failed to load available branches.');
                              console.error(xhr);
                              branchSelect.prop('disabled', true);
                        }
                  });
            });
      };

      // Handle branch selection change
      const handleBranchChange = () => {
            branchSelect.on('change', function () {
                  const branchId = $(this).val();

                  // Reset batch
                  batchSelect.empty().append('<option></option>').prop('disabled', true).trigger('change');

                  if (!branchId) {
                        return;
                  }

                  $.ajax({
                        url: window.batchesByBranchRoute.replace(':branch', branchId),
                        type: 'GET',
                        dataType: 'json',
                        success: function (batches) {
                              batchSelect.empty();
                              batchSelect.append('<option></option>');

                              batches.forEach(batch => {
                                    batchSelect.append(`<option value="${batch.id}">${batch.name}</option>`);
                              });

                              batchSelect.prop('disabled', false).trigger('change');
                        },
                        error: function (xhr) {
                              toastr.error('Failed to load batches for the selected branch.');
                              console.error(xhr);
                        }
                  });
            });
      };

      // Reset form and selects on close/cancel
      var initCloseModal = () => {
            const cancelButton = element.querySelector('[data-new-transfer-modal-action="cancel"]');
            const closeButton = element.querySelector('[data-new-transfer-modal-action="close"]');

            const resetAll = () => {
                  form.reset();
                  studentSelect.val(null).trigger('change');
                  resetDependentDropdowns();
            };

            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        resetAll();
                        modal.hide();
                  });
            }

            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        resetAll();
                        modal.hide();
                  });
            }
      };

      // Form validation & submit
      var initValidation = function () {
            if (!form) return;

            var validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'student_id': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Select the student'
                                          }
                                    }
                              },
                              'branch_id': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Select the branch'
                                          }
                                    }
                              },
                              'batch_id': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Select the batch'
                                          }
                                    }
                              },
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

            const submitButton = element.querySelector('[data-new-transfer-modal-action="submit"]');

            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();

                        validator.validate().then(function (status) {
                              if (status === 'Valid') {
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    const formData = new FormData(form);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                                    fetch(window.storeNewTransferRoute, {
                                          method: "POST",
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(async response => {
                                                const data = await response.json();

                                                if (!response.ok) {
                                                      let errorMsg = data.message || 'Something went wrong';
                                                      if (data.errors) {
                                                            errorMsg += '<br>' + [...new Set(Object.values(data.errors).flat())].join('<br>');
                                                      }
                                                      throw new Error(errorMsg);
                                                }
                                                return data;
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                toastr.success(data.message || 'Student transferred successfully');
                                                modal.hide();
                                                setTimeout(() => {
                                                      window.location.reload();
                                                }, 1500);
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to transfer student');
                                                console.error('Error:', error);
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields correctly');
                              }
                        });
                  });
            }
      };

      return {
            init: function () {
                  initSelect2();
                  handleStudentChange();
                  handleBranchChange();
                  initCloseModal();
                  initValidation();
            }
      };
}();


// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTAllTransferHistoryList.init();
      KTNewTransfer.init();
});