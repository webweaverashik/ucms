let invoices = [];

// 1. Fetch invoices on student select
$('#transaction_student_select').on('change', function () {
    const studentId = $(this).val();
    if (!studentId) return;

    $.ajax({
        url: `/students/${studentId}/due-invoices`,
        method: 'GET',
        success: function (response) {
            invoices = response;
            const $invoiceSelect = $('#student_due_invoice_select');
            $invoiceSelect.empty().append(`<option></option>`);

            response.forEach(invoice => {
                $invoiceSelect.append(
                    `<option value="${invoice.id}">
                        ${invoice.invoice_number} - Total: ৳${invoice.total_amount}, Due: ৳${invoice.amount_due}
                    </option>`
                );
            });

            $('#transaction_amount_input').val('').prop('disabled', true);
            $('#transaction_amount_input').removeClass('is-invalid');
            $('#transaction_amount_error').remove();

            // Reset payment type options
            $('input[name="transaction_type"]').prop('disabled', false);
        }
    });
});

// 2. Populate amount and adjust payment options when invoice selected
$('#student_due_invoice_select').on('change', function () {
    const selectedId = $(this).val();
    const invoice = invoices.find(inv => inv.id == selectedId);

    if (invoice) {
        const $amountInput = $('#transaction_amount_input');
        $amountInput
            .val(invoice.amount_due)
            .prop('disabled', false) // Always enabled now
            .data('max', invoice.amount_due)
            .attr('min', 1);

        // Enable/disable payment type options based on amount due
        const $fullPaymentOption = $('input[name="transaction_type"][value="full"]');
        const $partialPaymentOption = $('input[name="transaction_type"][value="partial"]');

        if (invoice.amount_due < invoice.total_amount) {
            // Only partial payment allowed if amount_due < total_amount
            $fullPaymentOption.prop('disabled', true).prop('checked', false);
            $partialPaymentOption.prop('checked', true);
            $amountInput.val(''); // Clear value but keep enabled
        } else {
            // Both options allowed if full amount is due
            $fullPaymentOption.prop('disabled', false);
            $partialPaymentOption.prop('disabled', false);
            $fullPaymentOption.prop('checked', true);
            $amountInput.val(invoice.amount_due); // Set to full amount but keep enabled
        }
    }
});

// 3. Toggle input behavior for payment type (but keep enabled)
$('input[name="transaction_type"]').on('change', function () {
    const paymentType = $(this).val();
    const $amountInput = $('#transaction_amount_input');
    const selectedId = $('#student_due_invoice_select').val();
    const invoice = invoices.find(inv => inv.id == selectedId);

    if (invoice) {
        if (paymentType === 'partial') {
            $amountInput.val(''); // Clear value for partial payment
        } else if (paymentType === 'discounted') {
            $amountInput.val(''); // Clear value for discounted payment
        } else {
            $amountInput.val(invoice.amount_due); // Set to full amount
        }
    }
});

// 4. Validate amount input
$('#transaction_amount_input').on('input', function () {
    const amount = parseFloat($(this).val());
    const maxAmount = parseFloat($(this).data('max'));
    const paymentType = $('input[name="transaction_type"]:checked').val();

    // Remove previous error state
    $(this).removeClass('is-invalid');
    $('#transaction_amount_error').remove();

    // Validate the amount
    let isValid = true;
    let errorMessage = '';

    if (isNaN(amount)) {
        isValid = false;
        errorMessage = 'Please enter a valid number';
    } else if (amount < 1) {
        isValid = false;
        errorMessage = 'Amount must be at least ৳1';
    } else if (
        (paymentType === 'partial' || paymentType === 'discounted') &&
        amount >= maxAmount
    ) {
        isValid = false;
        errorMessage = `For ${paymentType} payment, amount must be less than the due amount of ৳${maxAmount}`;
    } else if (paymentType === 'full' && amount != maxAmount) {
        isValid = false;
        errorMessage = `For full payment, amount must be exactly ৳${maxAmount}`;
    }

    if (!isValid) {
        $(this).addClass('is-invalid');
        $(this).after(
            `<div class="invalid-feedback" id="transaction_amount_error">
                ${errorMessage}
            </div>`
        );
    }
});

// 5. Form submission validation
$('#kt_modal_add_transaction_form').on('submit', function (e) {
    const amount = parseFloat($('#transaction_amount_input').val());
    const maxAmount = parseFloat($('#transaction_amount_input').data('max'));
    const paymentType = $('input[name="transaction_type"]:checked').val();

    // Check validation
    let isValid = true;

    if (isNaN(amount)) {
        isValid = false;
    } else if (amount < 1) {
        isValid = false;
    } else if (
        (paymentType === 'partial' || paymentType === 'discounted') &&
        amount >= maxAmount
    ) {
        isValid = false;
    } else if (paymentType === 'full' && amount != maxAmount) {
        isValid = false;
    }

    if (!isValid || $('#transaction_amount_input').hasClass('is-invalid')) {
        e.preventDefault();
        toastr.warning('Please enter a valid amount.');
        return false;
    }

    return true;
});