let invoices = [];

// 1. Fetch invoices on student select
$('#transaction_student_select').on('change', function () {
    const studentId = $(this).val();
    if (!studentId) return;

    $.ajax({
        url: `/students/${studentId}/due-invoices`,
        method: 'GET',
        success: function (response) {
            invoices = response; // store globally
            const $invoiceSelect = $('#student_due_invoice_select');
            $invoiceSelect.empty().append(`<option></option>`);
            response.forEach(invoice => {
                $invoiceSelect.append(
                    `<option value="${invoice.id}">${invoice.invoice_number} - ৳${invoice.amount}</option>`
                );
            });
            $('#transaction_amount_input').val('').prop('disabled', true); // reset amount
            $('#transaction_amount_input').removeClass('is-invalid'); // Remove previous error state
            $('#transaction_amount_error').remove(); // Remove error message
        }
    });
});

// 2. Populate amount when invoice selected
$('#student_due_invoice_select').on('change', function () {
    const selectedId = $(this).val();
    const invoice = invoices.find(inv => inv.id == selectedId);
    if (invoice) {
        $('#transaction_amount_input')
            .val(invoice.amount)
            .prop('disabled', false) // Changed to always enabled
            .data('max', invoice.amount); // store max for validation
    }
});

// 3. Toggle input behavior for payment type
$('input[name="transaction_type"]').on('change', function () {
    const isPartial = $(this).val() === 'partial';
    const $amountInput = $('#transaction_amount_input');
    const selectedId = $('#student_due_invoice_select').val();
    const invoice = invoices.find(inv => inv.id == selectedId);

    if (invoice) {
        if (isPartial) {
            $amountInput.val('').prop('disabled', false); // Clear value for partial payment
        } else {
            $amountInput.val(invoice.amount).prop('disabled', false); // Set to full amount but keep enabled
        }
    }
});

// 4. Continuously check the validity of the amount as the user types
$('#transaction_amount_input').on('input', function () {
    const amount = parseFloat($(this).val());
    const maxAmount = parseFloat($(this).data('max'));
    const isPartial = $('input[name="transaction_type"]:checked').val() === 'partial';

    // Remove previous error state
    $(this).removeClass('is-invalid');
    $('#transaction_amount_error').remove();

    // Validate the amount
    let isValid = true;
    let errorMessage = '';

    if (isNaN(amount)) {
        isValid = false;
        errorMessage = 'Please enter a valid number';
    } else if (amount < 500) {
        isValid = false;
        errorMessage = 'Amount must be at least ৳500';
    } else if (isPartial && amount >= maxAmount) {
        isValid = false;
        errorMessage = `For partial payments, amount must be less than ৳${maxAmount}`;
    } else if (!isPartial && amount != maxAmount) {
        isValid = false;
        errorMessage = `For full payment, amount must be exactly ৳${maxAmount}`;
    } else if (amount > maxAmount) {
        isValid = false;
        errorMessage = `Amount cannot exceed ৳${maxAmount}`;
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

// 5. Validation before form submit
$('#kt_modal_add_transaction_form').on('submit', function (e) {
    const amount = parseFloat($('#transaction_amount_input').val());
    const maxAmount = parseFloat($('#transaction_amount_input').data('max'));
    const isPartial = $('input[name="transaction_type"]:checked').val() === 'partial';

    // Check validation
    let isValid = true;

    if (isNaN(amount)) {
        isValid = false;
    } else if (amount < 500) {
        isValid = false;
    } else if (isPartial && amount >= maxAmount) {
        isValid = false;
    } else if (!isPartial && amount != maxAmount) {
        isValid = false;
    } else if (amount > maxAmount) {
        isValid = false;
    }

    if (!isValid || $('#transaction_amount_input').hasClass('is-invalid')) {
        e.preventDefault();
        toastr.warning('Please enter a valid amount.');
        return false;
    }

    return true;
});