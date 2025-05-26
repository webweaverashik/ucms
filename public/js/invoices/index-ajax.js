$(document).ready(function () {
      // 1. Enable invoice_type and invoice_month_year when a student is selected
      $('select[name="invoice_student"]').on('change', function () {
            if ($(this).val()) {
                  $('select[name="invoice_type"]').prop('disabled', false);
                  $('select[name="invoice_month_year"]').prop('disabled', false);
            }
      });

      // 2. Enable invoice_amount when invoice_month_year is selected
      $('select[name="invoice_month_year"]').on('change', function () {
            if ($(this).val()) {
                  $('input[name="invoice_amount"]').prop('disabled', false);
            }
      });

      // 3. Show/hide #month_year_id and toggle invoice_amount based on invoice_type value
      $('select[name="invoice_type"]').on('change', function () {
            const selectedType = $(this).val();

            if (selectedType !== 'tuition_fee') {
                  $('#month_year_id').hide();
                  $('select[name="invoice_month_year"]').prop('required', false);
                  $('input[name="invoice_amount"]').prop('disabled', false);
            } else {
                  $('#month_year_id').show();
                  $('select[name="invoice_month_year"]').prop('required', true);

                  if (!$('select[name="invoice_month_year"]').val()) {
                        $('input[name="invoice_amount"]').prop('disabled', true);
                  }
            }
      });

      // 4. When the reset button is clicked
      $('[data-kt-add-invoice-modal-action="cancel"]').on('click', function () {
            // Reset Select2 inputs
            $('select[data-control="select2"]').val(null).trigger('change');

            // Optionally disable fields again after reset
            $('select[name="invoice_type"]').prop('disabled', true);
            $('select[name="invoice_month_year"]').prop('disabled', true);
            $('input[name="invoice_amount"]').prop('disabled', true);

            // Optional: show invoice_type_id again and make invoice_month_year required
            $('select[name="invoice_type"]').val('tuition_fee').trigger('change');
            $('select[name="invoice_month_year"]').prop('required', true);
      });
});
