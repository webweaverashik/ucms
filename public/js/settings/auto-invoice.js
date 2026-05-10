/**
 * Auto Invoice Generation JavaScript
 * Metronic 8.2.6 compatible
 */
"use strict";

var AutoInvoice = function () {

      // ─── Private variables ────────────────────────────────────────────────────
      var btnGenerateCurrent;
      var btnGenerateDue;
      var btnGenerateSheet;
      var currentBranchSelect;
      var dueBranchSelect;
      var sheetBranchSelect;

      // ─── Helpers ──────────────────────────────────────────────────────────────
      var initSelect2 = function () {
            if ($('#current_branch_select').length) {
                  $('#current_branch_select').select2({ minimumResultsForSearch: 5 });
            }
            if ($('#due_branch_select').length) {
                  $('#due_branch_select').select2({ minimumResultsForSearch: 5 });
            }
            if ($('#sheet_branch_select').length) {
                  $('#sheet_branch_select').select2({ minimumResultsForSearch: 5 });
            }
      };

      var getUrlWithBranch = function (baseUrl, branchId) {
            return branchId ? baseUrl + '?branch_id=' + branchId : baseUrl;
      };

      var getBranchName = function (selectElement) {
            if (!selectElement) return 'All Branches';
            var opt = selectElement.options[selectElement.selectedIndex];
            return (opt && opt.text) ? opt.text : 'All Branches';
      };

      // ─── Current Invoice ──────────────────────────────────────────────────────
      var initCurrentInvoiceButton = function () {
            btnGenerateCurrent = document.getElementById('btn_generate_current');
            currentBranchSelect = document.getElementById('current_branch_select');

            if (!btnGenerateCurrent) return;

            btnGenerateCurrent.addEventListener('click', function (e) {
                  e.preventDefault();

                  var baseUrl = this.getAttribute('data-base-url');
                  var branchId = currentBranchSelect ? currentBranchSelect.value : '';
                  var branchName = getBranchName(currentBranchSelect);
                  var url = getUrlWithBranch(baseUrl, branchId);

                  Swal.fire({
                        title: 'Generate Current Invoices?',
                        html: `
                    <div class="text-start">
                        <p class="text-gray-600 mb-4">This will generate tuition fee invoices for all active students with <strong>current</strong> payment style who don't have an invoice for this month.</p>
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-3">
                            <i class="ki-duotone ki-information fs-2tx text-primary me-3">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">Billing Period:</span>
                                <span class="text-gray-600">Current month invoices</span>
                            </div>
                        </div>
                        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4">
                            <i class="ki-duotone ki-bank fs-2tx text-info me-3">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">Selected Branch:</span>
                                <span class="text-gray-600">${branchName}</span>
                            </div>
                        </div>
                    </div>
                `,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: '<i class="ki-outline ki-check fs-4 me-1"></i> Yes, Generate',
                        cancelButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Cancel',
                        customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' },
                        buttonsStyling: false,
                        reverseButtons: true
                  }).then(function (result) {
                        if (result.isConfirmed) {
                              btnGenerateCurrent.setAttribute('data-kt-indicator', 'on');
                              btnGenerateCurrent.disabled = true;

                              Swal.fire({
                                    title: 'Generating Current Invoices...',
                                    html: 'Please wait while invoices are being generated.',
                                    icon: 'info',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => { Swal.showLoading(); }
                              });

                              window.location.href = url;
                        }
                  });
            });
      };

      // ─── Due Invoice ──────────────────────────────────────────────────────────
      var initDueInvoiceButton = function () {
            btnGenerateDue = document.getElementById('btn_generate_due');
            dueBranchSelect = document.getElementById('due_branch_select');

            if (!btnGenerateDue) return;

            btnGenerateDue.addEventListener('click', function (e) {
                  e.preventDefault();

                  var baseUrl = this.getAttribute('data-base-url');
                  var branchId = dueBranchSelect ? dueBranchSelect.value : '';
                  var branchName = getBranchName(dueBranchSelect);
                  var url = getUrlWithBranch(baseUrl, branchId);

                  Swal.fire({
                        title: 'Generate Due Invoices?',
                        html: `
                    <div class="text-start">
                        <p class="text-gray-600 mb-4">This will generate tuition fee invoices for all active students with <strong>due</strong> payment style who don't have an invoice for last month.</p>
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-3">
                            <i class="ki-duotone ki-information fs-2tx text-warning me-3">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">Billing Period:</span>
                                <span class="text-gray-600">Previous month invoices</span>
                            </div>
                        </div>
                        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4">
                            <i class="ki-duotone ki-bank fs-2tx text-info me-3">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">Selected Branch:</span>
                                <span class="text-gray-600">${branchName}</span>
                            </div>
                        </div>
                    </div>
                `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<i class="ki-outline ki-check fs-4 me-1"></i> Yes, Generate',
                        cancelButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Cancel',
                        customClass: { confirmButton: 'btn btn-warning', cancelButton: 'btn btn-light' },
                        buttonsStyling: false,
                        reverseButtons: true
                  }).then(function (result) {
                        if (result.isConfirmed) {
                              btnGenerateDue.setAttribute('data-kt-indicator', 'on');
                              btnGenerateDue.disabled = true;

                              Swal.fire({
                                    title: 'Generating Due Invoices...',
                                    html: 'Please wait while invoices are being generated.',
                                    icon: 'info',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => { Swal.showLoading(); }
                              });

                              window.location.href = url;
                        }
                  });
            });
      };

      // ─── Sheet Fee Invoice (AJAX) ─────────────────────────────────────────────
      var initSheetInvoiceButton = function () {
            btnGenerateSheet = document.getElementById('btn_generate_sheet');
            sheetBranchSelect = document.getElementById('sheet_branch_select');

            if (!btnGenerateSheet) return;

            btnGenerateSheet.addEventListener('click', function () {
                  var actionUrl = this.getAttribute('data-action-url');
                  var csrfToken = this.getAttribute('data-csrf');
                  var branchId = sheetBranchSelect ? sheetBranchSelect.value : '';
                  var branchName = getBranchName(sheetBranchSelect);

                  Swal.fire({
                        title: 'Generate Sheet Fee Invoices?',
                        html: `
                    <div class="text-start">
                        <p class="text-gray-600 mb-4">
                            This will generate <strong>Sheet Fee</strong> invoices for all active students
                            whose class has an assigned sheet and who have not yet been invoiced this year.
                        </p>
                        <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-4 mb-3">
                            <i class="ki-duotone ki-document fs-2tx text-success me-3">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">Invoice Type:</span>
                                <span class="text-gray-600">Sheet Fee — once per year per student</span>
                            </div>
                        </div>
                        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4">
                            <i class="ki-duotone ki-bank fs-2tx text-info me-3">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">Selected Branch:</span>
                                <span class="text-gray-600">${branchName}</span>
                            </div>
                        </div>
                    </div>
                `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="ki-outline ki-check fs-4 me-1"></i> Yes, Generate',
                        cancelButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Cancel',
                        customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-light' },
                        buttonsStyling: false,
                        reverseButtons: true
                  }).then(function (confirmed) {
                        if (!confirmed.isConfirmed) return;

                        // Show loading state on button
                        btnGenerateSheet.setAttribute('data-kt-indicator', 'on');
                        btnGenerateSheet.disabled = true;

                        // Show processing dialog
                        Swal.fire({
                              title: 'Generating Sheet Fee Invoices...',
                              html: 'Please wait while invoices are being processed.',
                              icon: 'info',
                              allowOutsideClick: false,
                              allowEscapeKey: false,
                              showConfirmButton: false,
                              didOpen: () => { Swal.showLoading(); }
                        });

                        // Build form data
                        var formData = new FormData();
                        formData.append('_token', csrfToken);
                        if (branchId) {
                              formData.append('branch_id', branchId);
                        }

                        // AJAX POST request
                        fetch(actionUrl, {
                              method: 'POST',
                              headers: { 'X-Requested-With': 'XMLHttpRequest' },
                              body: formData
                        })
                              .then(function (response) {
                                    return response.json().then(function (data) {
                                          return { status: response.status, body: data };
                                    });
                              })
                              .then(function ({ status, body }) {
                                    // Reset button state
                                    btnGenerateSheet.removeAttribute('data-kt-indicator');
                                    btnGenerateSheet.disabled = false;

                                    if (body.success) {
                                          var result = body.result;
                                          var detailHtml = `
                            <div class="text-start mt-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-light-success fs-7 me-2">${result.generated}</span>
                                    <span class="text-gray-700">Invoice(s) generated</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-light-warning fs-7 me-2">${result.existing}</span>
                                    <span class="text-gray-700">Already invoiced (skipped)</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-light-dark fs-7 me-2">${result.no_sheet}</span>
                                    <span class="text-gray-700">No sheet / zero price (skipped)</span>
                                </div>
                            </div>
                        `;

                                          Swal.fire({
                                                title: 'Sheet Fee Invoices Generated',
                                                html: '<p class="text-gray-600">' + body.message + '</p>' + detailHtml,
                                                icon: 'success',
                                                confirmButtonText: '<i class="ki-outline ki-check fs-4 me-1"></i> Done',
                                                customClass: { confirmButton: 'btn btn-success' },
                                                buttonsStyling: false
                                          });
                                    } else {
                                          Swal.fire({
                                                title: 'Generation Failed',
                                                text: body.message || 'An unexpected error occurred. Please try again.',
                                                icon: 'error',
                                                confirmButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Close',
                                                customClass: { confirmButton: 'btn btn-danger' },
                                                buttonsStyling: false
                                          });
                                    }
                              })
                              .catch(function (err) {
                                    btnGenerateSheet.removeAttribute('data-kt-indicator');
                                    btnGenerateSheet.disabled = false;

                                    Swal.fire({
                                          title: 'Network Error',
                                          text: 'Could not reach the server. Please check your connection and try again.',
                                          icon: 'error',
                                          confirmButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Close',
                                          customClass: { confirmButton: 'btn btn-danger' },
                                          buttonsStyling: false
                                    });
                              });
                  });
            });
      };

      // ─── Public API ───────────────────────────────────────────────────────────
      return {
            init: function () {
                  initSelect2();
                  initCurrentInvoiceButton();
                  initDueInvoiceButton();
                  initSheetInvoiceButton();
            }
      };

}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      AutoInvoice.init();
});