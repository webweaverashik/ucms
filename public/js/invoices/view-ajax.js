$(document).ready(function () {
// Get the invoice data from the hidden inputs and any status indicators
    const invoice = {
        id: $('input[name="transaction_invoice"]').val(),
        student_id: $('input[name="transaction_student"]').val(),
        amount_due: parseFloat($('#transaction_amount_input').val()),
        // Assuming you have a way to get the current status (you might need to add this to your HTML)
        status: $('#invoice_status_indicator').data('status') || 'unpaid' // Default to 'unpaid' if not found
    };

    // Initialize form behavior
    function initializeForm() {
        const $amountInput = $('#transaction_amount_input');
        const $fullPaymentOption = $('input[name="transaction_type"][value="full"]');
        const $partialPaymentOption = $('input[name="transaction_type"][value="partial"]');

        // Set up amount input
        $amountInput
            .val(invoice.amount_due)
            .prop('disabled', false)
            .data('max', invoice.amount_due)
            .attr('min', 1);

        // Check invoice status to determine payment options
        if (invoice.status === 'partially_paid' || invoice.amount_due < invoice.total_amount) {
            // Disable full payment for partially paid invoices
            $fullPaymentOption.prop('disabled', true).prop('checked', false);
            $partialPaymentOption.prop('checked', true);
            $amountInput.val(''); // Clear value for partial payment
        } else {
            // Enable both options for unpaid invoices
            $fullPaymentOption.prop('disabled', false);
            $partialPaymentOption.prop('disabled', false);
            $fullPaymentOption.prop('checked', true);
            $amountInput.val(invoice.amount_due);
        }
    }

      // 1. Toggle input behavior for payment type
      $('input[name="transaction_type"]').on('change', function () {
            const isPartial = $(this).val() === 'partial';
            const $amountInput = $('#transaction_amount_input');

            if (isPartial) {
                  $amountInput.val(''); // Clear value for partial payment
            } else {
                  $amountInput.val(invoice.amount_due); // Set to full amount
            }
      });

      // 2. Validate amount input
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
            } else if (amount < 1) {
                  isValid = false;
                  errorMessage = 'Amount must be at least ৳1';
            } else if (isPartial && amount > maxAmount) {
                  isValid = false;
                  errorMessage = `Amount cannot exceed the due amount of ৳${maxAmount}`;
            } else if (!isPartial && amount != maxAmount) {
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

      // 3. Form submission validation
      $('#kt_modal_add_transaction_form').on('submit', function (e) {
            const amount = parseFloat($('#transaction_amount_input').val());
            const maxAmount = parseFloat($('#transaction_amount_input').data('max'));
            const isPartial = $('input[name="transaction_type"]:checked').val() === 'partial';

            // Check validation
            let isValid = true;

            if (isNaN(amount)) {
                  isValid = false;
            } else if (amount < 1) {
                  isValid = false;
            } else if (isPartial && amount > maxAmount) {
                  isValid = false;
            } else if (!isPartial && amount != maxAmount) {
                  isValid = false;
            }

            if (!isValid || $('#transaction_amount_input').hasClass('is-invalid')) {
                  e.preventDefault();
                  toastr.warning('Please enter a valid amount.');
                  return false;
            }

            return true;
      });

      // Initialize the form
      initializeForm();
});



