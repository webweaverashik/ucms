/**
 * Auto Invoice Generation JavaScript
 * Metronic 8.2.6 compatible
 */

"use strict";

var AutoInvoice = function () {
      // Private variables
      var btnGenerateCurrent;
      var btnGenerateDue;
      var currentBranchSelect;
      var dueBranchSelect;

      // Private functions
      var initSelect2 = function () {
            // Initialize Select2 for branch dropdowns
            if ($('#current_branch_select').length) {
                  $('#current_branch_select').select2({
                        minimumResultsForSearch: 5
                  });
            }

            if ($('#due_branch_select').length) {
                  $('#due_branch_select').select2({
                        minimumResultsForSearch: 5
                  });
            }
      };

      var getUrlWithBranch = function (baseUrl, branchId) {
            if (branchId) {
                  return baseUrl + '?branch_id=' + branchId;
            }
            return baseUrl;
      };

      var getBranchName = function (selectElement) {
            var selectedOption = selectElement.options[selectElement.selectedIndex];
            return selectedOption.text || 'All Branches';
      };

      var initCurrentInvoiceButton = function () {
            btnGenerateCurrent = document.getElementById('btn_generate_current');
            currentBranchSelect = document.getElementById('current_branch_select');

            if (!btnGenerateCurrent) {
                  return;
            }

            btnGenerateCurrent.addEventListener('click', function (e) {
                  e.preventDefault();

                  var baseUrl = this.getAttribute('data-base-url');
                  var branchId = currentBranchSelect ? currentBranchSelect.value : '';
                  var branchName = currentBranchSelect ? getBranchName(currentBranchSelect) : 'All Branches';
                  var url = getUrlWithBranch(baseUrl, branchId);

                  Swal.fire({
                        title: 'Generate Current Invoices?',
                        html: `
                    <div class="text-start">
                        <p class="text-gray-600 mb-4">This will generate tuition fee invoices for all active students with <strong>current</strong> payment style who don't have an invoice for this month.</p>
                        <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-3">
                            <i class="ki-duotone ki-information fs-2tx text-primary me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">Billing Period:</span>
                                <span class="text-gray-600">Current month invoices</span>
                            </div>
                        </div>
                        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4">
                            <i class="ki-duotone ki-bank fs-2tx text-info me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
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
                        customClass: {
                              confirmButton: 'btn btn-primary',
                              cancelButton: 'btn btn-light'
                        },
                        buttonsStyling: false,
                        reverseButtons: true
                  }).then(function (result) {
                        if (result.isConfirmed) {
                              // Show loading state
                              btnGenerateCurrent.setAttribute('data-kt-indicator', 'on');
                              btnGenerateCurrent.disabled = true;

                              // Show processing message
                              Swal.fire({
                                    title: 'Generating Current Invoices...',
                                    html: 'Please wait while invoices are being generated.',
                                    icon: 'info',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => {
                                          Swal.showLoading();
                                    }
                              });

                              // Navigate to the URL
                              window.location.href = url;
                        }
                  });
            });
      };

      var initDueInvoiceButton = function () {
            btnGenerateDue = document.getElementById('btn_generate_due');
            dueBranchSelect = document.getElementById('due_branch_select');

            if (!btnGenerateDue) {
                  return;
            }

            btnGenerateDue.addEventListener('click', function (e) {
                  e.preventDefault();

                  var baseUrl = this.getAttribute('data-base-url');
                  var branchId = dueBranchSelect ? dueBranchSelect.value : '';
                  var branchName = dueBranchSelect ? getBranchName(dueBranchSelect) : 'All Branches';
                  var url = getUrlWithBranch(baseUrl, branchId);

                  Swal.fire({
                        title: 'Generate Due Invoices?',
                        html: `
                    <div class="text-start">
                        <p class="text-gray-600 mb-4">This will generate tuition fee invoices for all active students with <strong>due</strong> payment style who don't have an invoice for last month.</p>
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mb-3">
                            <i class="ki-duotone ki-information fs-2tx text-warning me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-semibold text-gray-800">Billing Period:</span>
                                <span class="text-gray-600">Previous month invoices</span>
                            </div>
                        </div>
                        <div class="notice d-flex bg-light-info rounded border-info border border-dashed p-4">
                            <i class="ki-duotone ki-bank fs-2tx text-info me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
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
                        customClass: {
                              confirmButton: 'btn btn-warning',
                              cancelButton: 'btn btn-light'
                        },
                        buttonsStyling: false,
                        reverseButtons: true
                  }).then(function (result) {
                        if (result.isConfirmed) {
                              // Show loading state
                              btnGenerateDue.setAttribute('data-kt-indicator', 'on');
                              btnGenerateDue.disabled = true;

                              // Show processing message
                              Swal.fire({
                                    title: 'Generating Due Invoices...',
                                    html: 'Please wait while invoices are being generated.',
                                    icon: 'info',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => {
                                          Swal.showLoading();
                                    }
                              });

                              // Navigate to the URL
                              window.location.href = url;
                        }
                  });
            });
      };

      // Public methods
      return {
            init: function () {
                  initSelect2();
                  initCurrentInvoiceButton();
                  initDueInvoiceButton();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      AutoInvoice.init();
});