/**
 * Auto Invoice Generation JavaScript
 * Metronic 8.2.6 compatible
 */
"use strict";

var AutoInvoice = function () {

    // ─── Private variables ────────────────────────────────────────────────────
    var btnGenerateCurrent, btnGenerateDue, btnGenerateSheet;
    var currentBranchSelect, dueBranchSelect, sheetBranchSelect;

    // ─── Utilities ────────────────────────────────────────────────────────────
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

    var getBranchName = function (el) {
        if (!el) return 'All Branches';
        var opt = el.options[el.selectedIndex];
        return (opt && opt.text) ? opt.text : 'All Branches';
    };

    var getUrlWithBranch = function (base, branchId) {
        return branchId ? base + '?branch_id=' + branchId : base;
    };

    var makeFormData = function (csrfToken, branchId) {
        var fd = new FormData();
        fd.append('_token', csrfToken);
        if (branchId) fd.append('branch_id', branchId);
        return fd;
    };

    // ─── Preview HTML builder ─────────────────────────────────────────────────
    /**
     * Builds the inner HTML for the dry-run SweetAlert.
     *
     * @param {object} preview   - JSON from the controller preview endpoint
     * @param {object} skipLabels - { key: 'Human label' } map for this invoice type
     */
    var buildPreviewHtml = function (preview, skipLabels) {
        // Only render rows that actually have a non-zero count
        var skipRows = Object.entries(skipLabels)
            .filter(function (entry) { return (preview.skipped[entry[0]] || 0) > 0; })
            .map(function (entry) {
                return `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-dashed border-gray-200">
                        <span class="text-gray-600 fs-7">${entry[1]}</span>
                        <span class="badge badge-light-danger fw-bold">${preview.skipped[entry[0]]}</span>
                    </div>`;
            }).join('');

        var skipSection = skipRows
            ? `<div class="separator my-4"></div>
               <p class="text-muted fw-bold fs-8 text-uppercase ls-1 mb-3">Skip Breakdown</p>
               ${skipRows}`
            : '';

        return `
            <div class="text-start">
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-3 mb-5">
                    <i class="ki-duotone ki-calendar fs-2tx text-primary me-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    <div class="d-flex flex-column justify-content-center">
                        <span class="fw-semibold text-gray-700 fs-8 text-uppercase">Billing Period</span>
                        <span class="text-primary fw-bolder fs-6">${preview.billing_period}</span>
                    </div>
                </div>

                <div class="row g-3 mb-1">
                    <div class="col-4 text-center">
                        <div class="bg-light rounded p-3 h-100">
                            <div class="fs-1 fw-bolder text-gray-800">${preview.total}</div>
                            <div class="text-muted fs-8 fw-semibold mt-1">Total</div>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="bg-light-success rounded p-3 h-100">
                            <div class="fs-1 fw-bolder text-success">${preview.to_generate}</div>
                            <div class="text-muted fs-8 fw-semibold mt-1">To Generate</div>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="bg-light-danger rounded p-3 h-100">
                            <div class="fs-1 fw-bolder text-danger">${preview.total_skipped}</div>
                            <div class="text-muted fs-8 fw-semibold mt-1">Skipped</div>
                        </div>
                    </div>
                </div>

                ${skipSection}
            </div>`;
    };

    // ─── Core: fetch preview → show SweetAlert → call onConfirm ──────────────
    /**
     * @param {object} options
     *   previewUrl   {string}   POST endpoint for dry-run
     *   csrfToken    {string}
     *   branchId     {string}
     *   title        {string}   SweetAlert title
     *   icon         {string}   SweetAlert icon
     *   confirmClass {string}   Bootstrap btn class for confirm button
     *   skipLabels   {object}   { key: label } pairs for skip breakdown
     *   onConfirm    {function} Called when user confirms — receives preview data
     */
    var runWithPreview = function (options) {
        // Step 1: show analysing spinner
        Swal.fire({
            title: 'Analysing...',
            html: 'Calculating invoice details, please wait.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
        });

        // Step 2: POST to preview endpoint
        fetch(options.previewUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: makeFormData(options.csrfToken, options.branchId)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) {
                Swal.fire({
                    title: 'Preview Failed',
                    text: data.message || 'Could not load dry-run preview.',
                    icon: 'error',
                    confirmButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Close',
                    customClass: { confirmButton: 'btn btn-danger' },
                    buttonsStyling: false
                });
                return;
            }

            var preview    = data.preview;
            var toGenerate = preview.to_generate;
            var html       = buildPreviewHtml(preview, options.skipLabels);

            // Step 3a: nothing to generate — inform and stop
            if (toGenerate === 0) {
                Swal.fire({
                    title: options.title,
                    html: html + `
                        <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4 mt-4">
                            <i class="ki-duotone ki-information fs-2tx text-warning me-3">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column justify-content-center">
                                <span class="fw-semibold text-warning fs-7">No invoices to generate for this selection.</span>
                            </div>
                        </div>`,
                    icon: options.icon,
                    confirmButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Close',
                    customClass: { confirmButton: 'btn btn-light' },
                    buttonsStyling: false,
                    showCancelButton: false,
                });
                return;
            }

            // Step 3b: show preview with confirm
            Swal.fire({
                title: options.title,
                html: html,
                icon: options.icon,
                showCancelButton: true,
                confirmButtonText: `<i class="ki-outline ki-check fs-4 me-1"></i> Generate ${toGenerate} Invoice(s)`,
                cancelButtonText:  '<i class="ki-outline ki-cross fs-4 me-1"></i> Cancel',
                customClass: {
                    confirmButton: 'btn ' + options.confirmClass,
                    cancelButton:  'btn btn-light'
                },
                buttonsStyling: false,
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    options.onConfirm(preview);
                }
            });
        })
        .catch(function () {
            Swal.fire({
                title: 'Network Error',
                text: 'Could not reach the server. Please check your connection and try again.',
                icon: 'error',
                confirmButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Close',
                customClass: { confirmButton: 'btn btn-danger' },
                buttonsStyling: false
            });
        });
    };

    // ─── Execution strategies ─────────────────────────────────────────────────

    /** Current / Due — full page redirect after confirm (keeps existing flash-message flow) */
    var executeViaRedirect = function (btn, url) {
        btn.setAttribute('data-kt-indicator', 'on');
        btn.disabled = true;

        Swal.fire({
            title: 'Generating Invoices...',
            html: 'Please wait while invoices are being created.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
        });

        window.location.href = url;
    };

    /** Sheet Fee — AJAX POST, result shown in SweetAlert without page reload */
    var executeViaAjax = function (btn, actionUrl, csrfToken, branchId) {
        btn.setAttribute('data-kt-indicator', 'on');
        btn.disabled = true;

        Swal.fire({
            title: 'Generating Sheet Fee Invoices...',
            html: 'Please wait while invoices are being created.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
        });

        fetch(actionUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: makeFormData(csrfToken, branchId)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            btn.removeAttribute('data-kt-indicator');
            btn.disabled = false;

            if (data.success) {
                var r = data.result;
                Swal.fire({
                    title: 'Sheet Fee Invoices Generated',
                    html: `
                        <p class="text-gray-600 mb-4">${data.message}</p>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light-success rounded">
                                <span class="fw-semibold text-gray-700 fs-7">Generated</span>
                                <span class="badge badge-success fs-7">${r.generated}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light-warning rounded">
                                <span class="fw-semibold text-gray-700 fs-7">Already invoiced (skipped)</span>
                                <span class="badge badge-warning fs-7">${r.existing}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded">
                                <span class="fw-semibold text-gray-700 fs-7">No sheet / zero price (skipped)</span>
                                <span class="badge badge-secondary fs-7">${r.no_sheet}</span>
                            </div>
                        </div>`,
                    icon: 'success',
                    confirmButtonText: '<i class="ki-outline ki-check fs-4 me-1"></i> Done',
                    customClass: { confirmButton: 'btn btn-success' },
                    buttonsStyling: false,
                });
            } else {
                Swal.fire({
                    title: 'Generation Failed',
                    text: data.message || 'An unexpected error occurred. Please try again.',
                    icon: 'error',
                    confirmButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Close',
                    customClass: { confirmButton: 'btn btn-danger' },
                    buttonsStyling: false,
                });
            }
        })
        .catch(function () {
            btn.removeAttribute('data-kt-indicator');
            btn.disabled = false;

            Swal.fire({
                title: 'Network Error',
                text: 'Could not reach the server. Please check your connection and try again.',
                icon: 'error',
                confirmButtonText: '<i class="ki-outline ki-cross fs-4 me-1"></i> Close',
                customClass: { confirmButton: 'btn btn-danger' },
                buttonsStyling: false,
            });
        });
    };

    // ─── Button initialisers ──────────────────────────────────────────────────
    var initCurrentInvoiceButton = function () {
        btnGenerateCurrent  = document.getElementById('btn_generate_current');
        currentBranchSelect = document.getElementById('current_branch_select');
        if (!btnGenerateCurrent) return;

        btnGenerateCurrent.addEventListener('click', function (e) {
            e.preventDefault();

            var baseUrl    = this.getAttribute('data-base-url');
            var previewUrl = this.getAttribute('data-preview-url');
            var csrfToken  = this.getAttribute('data-csrf');
            var branchId   = currentBranchSelect ? currentBranchSelect.value : '';
            var url        = getUrlWithBranch(baseUrl, branchId);

            runWithPreview({
                previewUrl:   previewUrl,
                csrfToken:    csrfToken,
                branchId:     branchId,
                title:        'Current Month — Invoice Preview',
                icon:         'info',
                confirmClass: 'btn-primary',
                skipLabels: {
                    inactive_student:    'Inactive students',
                    inactive_class:      'In inactive class',
                    wrong_payment_style: 'Due payment style',
                    no_payment_profile:  'No payment profile',
                    free:                'FREE (zero tuition fee)',
                    already_invoiced:    'Already invoiced this month',
                },
                onConfirm: function () {
                    executeViaRedirect(btnGenerateCurrent, url);
                }
            });
        });
    };

    var initDueInvoiceButton = function () {
        btnGenerateDue  = document.getElementById('btn_generate_due');
        dueBranchSelect = document.getElementById('due_branch_select');
        if (!btnGenerateDue) return;

        btnGenerateDue.addEventListener('click', function (e) {
            e.preventDefault();

            var baseUrl    = this.getAttribute('data-base-url');
            var previewUrl = this.getAttribute('data-preview-url');
            var csrfToken  = this.getAttribute('data-csrf');
            var branchId   = dueBranchSelect ? dueBranchSelect.value : '';
            var url        = getUrlWithBranch(baseUrl, branchId);

            runWithPreview({
                previewUrl:   previewUrl,
                csrfToken:    csrfToken,
                branchId:     branchId,
                title:        'Due Month — Invoice Preview',
                icon:         'warning',
                confirmClass: 'btn-warning',
                skipLabels: {
                    inactive_student:    'Inactive students',
                    inactive_class:      'In inactive class',
                    wrong_payment_style: 'Current-style students',
                    no_payment_profile:  'No payment profile',
                    free:                'FREE (zero tuition fee)',
                    already_invoiced:    'Already invoiced this period',
                },
                onConfirm: function () {
                    executeViaRedirect(btnGenerateDue, url);
                }
            });
        });
    };

    var initSheetInvoiceButton = function () {
        btnGenerateSheet  = document.getElementById('btn_generate_sheet');
        sheetBranchSelect = document.getElementById('sheet_branch_select');
        if (!btnGenerateSheet) return;

        btnGenerateSheet.addEventListener('click', function () {
            var actionUrl  = this.getAttribute('data-action-url');
            var previewUrl = this.getAttribute('data-preview-url');
            var csrfToken  = this.getAttribute('data-csrf');
            var branchId   = sheetBranchSelect ? sheetBranchSelect.value : '';

            runWithPreview({
                previewUrl:   previewUrl,
                csrfToken:    csrfToken,
                branchId:     branchId,
                title:        'Sheet Fee — Invoice Preview',
                icon:         'question',
                confirmClass: 'btn-success',
                skipLabels: {
                    inactive_student: 'Inactive students',
                    inactive_class:   'In inactive class',
                    no_sheet:         'Class has no sheet assigned',
                    zero_price:       'Zero-price sheet',
                    already_invoiced: 'Already invoiced this year',
                },
                onConfirm: function () {
                    executeViaAjax(btnGenerateSheet, actionUrl, csrfToken, branchId);
                }
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

KTUtil.onDOMContentLoaded(function () {
    AutoInvoice.init();
});