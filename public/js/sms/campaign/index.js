"use strict";

var KTSMSCampaignList = function () {
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

      // View Recipients (delegated so it survives DataTables redraws)
      const handleViewRecipients = function () {
            document.addEventListener("click", function (e) {
                  const button = e.target.closest(".view-receipients");
                  if (!button) return;

                  e.preventDefault();

                  const title = button.getAttribute("data-campaign-title") || "Campaign";
                  const raw = button.getAttribute("data-recipients") || "[]";

                  // Parse recipients from JSON first; fall back to CSV/newline
                  let recipients = [];
                  try {
                        if (raw.trim().startsWith("[")) {
                              recipients = JSON.parse(raw) || [];
                        } else {
                              recipients = raw.split(/[,\n]/).map(s => s.trim()).filter(Boolean);
                        }
                  } catch {
                        recipients = raw.split(/[,\n]/).map(s => s.replace(/[\[\]"]/g, "").trim()).filter(Boolean);
                  }

                  const recipientsContent = document.getElementById("recipientsContent");
                  const modalLabel = document.getElementById("viewRecipientsModalLabel");
                  const modalEl = document.getElementById("viewRecipientsModal");
                  const modal = new bootstrap.Modal(modalEl);

                  modalLabel.textContent = `Recipients of ${title} (${recipients.length})`;

                  if (recipients.length > 0) {
                        // Build 4-column grid
                        let html = '<div class="row g-2">';
                        recipients.forEach(recipient => {
                              html += `<div class="col-3 fs-6">${recipient}</div>`;
                        });
                        html += '</div>';
                        recipientsContent.innerHTML = html;
                  } else {
                        recipientsContent.innerHTML = `<span class="text-muted">No recipients found.</span>`;
                  }

                  modal.show();
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
                  handleViewRecipients();
            }
      }
}();

var KTEditCampaignModal = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_edit_campaign');

      if (!element) {
            console.error('Modal element not found');
            return { init: function () { } };
      }

      const form = element.querySelector('#kt_modal_edit_campaign_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);
      const titleEl = element.querySelector("#kt_modal_edit_campaign_title");

      let campaignId = null;

      // --- Init edit campaign ---
      var initEditCampaign = () => {
            // Cancel button
            const cancelButton = element.querySelector('[data-kt-campaign-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        if (titleEl) titleEl.textContent = "Update Campaign"; // reset header
                        modal.hide();
                  });
            }

            // Close button
            const closeButton = element.querySelector('[data-kt-campaign-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        if (titleEl) titleEl.textContent = "Update Campaign"; // reset header
                        modal.hide();
                  });
            }

            // Edit button click (only .edit-campaign)
            document.addEventListener("click", function (e) {
                  const button = e.target.closest(".edit-campaign");
                  if (!button) return;

                  e.preventDefault();
                  campaignId = button.getAttribute("data-campaign-id");
                  if (!campaignId) return;

                  // Clear form
                  if (form) form.reset();
                  if (titleEl) titleEl.textContent = "Update Campaign"; // reset first

                  // Fetch campaign details
                  fetch(`/sms/send-campaign/${campaignId}`)
                        .then(response => response.json())
                        .then(data => {
                              if (data.success && data.data) {
                                    const campaign = data.data;

                                    // ✅ Update modal header
                                    if (titleEl) {
                                          titleEl.textContent = `Update Campaign: ${campaign.campaign_title}`;
                                    }

                                    // Prefill language radio
                                    if (campaign.message_type === "UNICODE") {
                                          element.querySelector("#unicode_message_type_input").checked = true;
                                    } else {
                                          element.querySelector("#text_message_type_input").checked = true;
                                    }

                                    // Prefill message body
                                    const messageInput = element.querySelector("textarea[name='message_body']");
                                    if (messageInput) messageInput.value = campaign.message_body || "";

                                    // Show modal
                                    modal.show();
                              } else {
                                    throw new Error(data.message || 'Invalid response data');
                              }
                        })
                        .catch(error => {
                              console.error("Error:", error);
                              toastr.error(error.message || "Failed to load campaign details");
                        });
            });
      };

      // --- Init form submission ---
      var initSubmit = () => {
            const submitButton = element.querySelector('[data-kt-campaign-modal-action="submit"]');
            if (!submitButton) return;

            submitButton.addEventListener('click', function (e) {
                  e.preventDefault();
                  if (!campaignId) {
                        toastr.error("No campaign selected");
                        return;
                  }

                  const formData = new FormData(form);
                  formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                  formData.append('_method', 'PUT'); // Laravel expects PUT

                  submitButton.setAttribute('data-kt-indicator', 'on');
                  submitButton.disabled = true;

                  fetch(`/sms/send-campaign/${campaignId}`, {
                        method: 'POST', // Laravel PUT via POST + _method
                        body: formData,
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  })
                        .then(response => response.json())
                        .then(data => {
                              submitButton.removeAttribute('data-kt-indicator');
                              submitButton.disabled = false;

                              if (data.success) {
                                    toastr.success(data.message || 'Campaign updated successfully');
                                    modal.hide();
                                    if (titleEl) titleEl.textContent = "Update Campaign"; // reset header
                                    setTimeout(() => window.location.reload(), 1500);
                              } else {
                                    throw new Error(data.message || 'Update failed');
                              }
                        })
                        .catch(error => {
                              submitButton.removeAttribute('data-kt-indicator');
                              submitButton.disabled = false;
                              toastr.error(error.message || 'Failed to update campaign');
                              console.error('Error:', error);
                        });
            });
      };

      return {
            init: function () {
                  initEditCampaign();
                  initSubmit();
            }
      };
}();


// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTSMSCampaignList.init();
      KTEditCampaignModal.init();
});