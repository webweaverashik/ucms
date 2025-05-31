$(document).ready(function () {
      // 1. Enable invoice_type and invoice_month_year when a student is selected
      $('select[name="invoice_student"]').on('change', function () {
            const studentId = $(this).val();
            const monthYearSelect = $('select[name="invoice_month_year"]');
            const invoiceTypeSelect = $('select[name="invoice_type"]');
            const invoiceAmountInput = $('input[name="invoice_amount"]');

            if (studentId) {
                  invoiceTypeSelect.prop('disabled', false);
                  monthYearSelect.prop('disabled', false);

                  // Clear existing options and add loading state
                  monthYearSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
                  invoiceAmountInput.val('').prop('disabled', true);

                  // Fetch the student's last paid tuition fee month and tuition fee amount
                  $.ajax({
                        url: `/students/${studentId}/last-paid-month`,
                        method: 'GET',
                        success: function (data) {
                              console.log('AJAX Response:', data);

                              // Store tuition fee amount in data attribute for later use
                              invoiceAmountInput.data('tuition-fee', data.tuition_fee);

                              // Month/Year population logic
                              const lastPaidMonth = data.last_paid_month;
                              const currentDate = new Date();
                              const currentMonth = currentDate.getMonth() + 1;
                              const currentYear = currentDate.getFullYear();

                              let startMonth, startYear;

                              if (lastPaidMonth) {
                                    const [lastMonth, lastYear] = lastPaidMonth.split('_').map(Number);

                                    // Set start date to the first day of the NEXT month
                                    startMonth = lastMonth + 1;
                                    startYear = lastYear;

                                    // Handle December to January transition
                                    if (startMonth > 12) {
                                          startMonth = 1;
                                          startYear++;
                                    }

                                    // If the calculated start date is in the future, use current month instead
                                    const startDate = new Date(startYear, startMonth - 1, 1);
                                    if (startDate > currentDate) {
                                          startMonth = currentMonth;
                                          startYear = currentYear;
                                    }
                              } else {
                                    // If no paid tuition fees yet, start from current month
                                    startMonth = currentMonth;
                                    startYear = currentYear;
                              }

                              // Generate months from start month/year to current month/year
                              monthYearSelect.empty().append('<option value=""></option>');
                              let date = new Date(startYear, startMonth - 1, 1);

                              while (date <= currentDate) {
                                    const month = date.getMonth() + 1;
                                    const year = date.getFullYear();
                                    const monthStr = month.toString().padStart(2, '0');
                                    const monthYear = `${monthStr}_${year}`;
                                    const monthName = date.toLocaleString('default', { month: 'long' });

                                    monthYearSelect.append(
                                          $('<option></option>').val(monthYear).text(`${monthName} ${year}`)
                                    );

                                    // Move to next month
                                    date.setMonth(date.getMonth() + 1);
                              }

                              // Re-enable the select and trigger Select2 update
                              monthYearSelect.prop('disabled', false).trigger('change');

                              // If invoice type is already selected as tuition_fee, ensure month is selected
                              if (invoiceTypeSelect.val() === 'tuition_fee' && monthYearSelect.val()) {
                                    const tuitionFee = invoiceAmountInput.data('tuition-fee');
                                    if (tuitionFee) {
                                          invoiceAmountInput.val(tuitionFee).prop('disabled', false);
                                    }
                              }
                        },
                        error: function (error) {
                              console.error('Error:', error);
                              monthYearSelect.empty().append('<option value="">Error loading months</option>');
                        }
                  });
            } else {
                  invoiceTypeSelect.prop('disabled', true);
                  monthYearSelect.prop('disabled', true);
                  monthYearSelect.empty().append('<option value=""></option>').trigger('change');
                  $('input[name="invoice_amount"]').val('').prop('disabled', true);
            }
      });

      // 2. Enable invoice_amount when invoice_month_year is selected and auto-fill if tuition_fee
      $('select[name="invoice_month_year"]').on('change', function () {
            const invoiceType = $('select[name="invoice_type"]').val();
            const invoiceAmountInput = $('input[name="invoice_amount"]');

            if ($(this).val()) {
                  invoiceAmountInput.prop('disabled', false);

                  // Auto-fill amount if invoice type is tuition_fee
                  if (invoiceType === 'tuition_fee') {
                        const tuitionFee = invoiceAmountInput.data('tuition-fee');
                        if (tuitionFee) {
                              invoiceAmountInput.val(tuitionFee);
                        }
                  }
            } else {
                  invoiceAmountInput.prop('disabled', true);
            }
      });

      // 3. Show/hide #month_year_id and toggle invoice_amount based on invoice_type value
      $('select[name="invoice_type"]').on('change', function () {
            const selectedType = $(this).val();
            const invoiceAmountInput = $('input[name="invoice_amount"]');
            const monthYear = $('select[name="invoice_month_year"]').val();

            if (selectedType !== 'tuition_fee') {
                  $('#month_year_id').hide();
                  $('select[name="invoice_month_year"]').prop('required', false);
                  invoiceAmountInput.prop('disabled', false);
                  invoiceAmountInput.val(''); // Clear amount for non-tuition fees
            } else {
                  $('#month_year_id').show();
                  $('select[name="invoice_month_year"]').prop('required', true);

                  if (monthYear) {
                        invoiceAmountInput.prop('disabled', false);
                        // Auto-fill tuition fee amount if available
                        const tuitionFee = invoiceAmountInput.data('tuition-fee');
                        if (tuitionFee) {
                              invoiceAmountInput.val(tuitionFee);
                        }
                  } else {
                        invoiceAmountInput.prop('disabled', true);
                  }
            }
      });

      // 4. When the reset button is clicked
      function resetInvoiceForm() {
            // Reset Select2 inputs
            $('select[data-control="select2"]').val(null).trigger('change');

            // Optionally disable fields again after reset
            $('select[name="invoice_type"]').prop('disabled', true);
            $('select[name="invoice_month_year"]').prop('disabled', true);
            $('input[name="invoice_amount"]').val('').prop('disabled', true);

            // Optional: show invoice_type_id again and make invoice_month_year required
            $('select[name="invoice_type"]').val('tuition_fee').trigger('change');
            $('select[name="invoice_month_year"]').prop('required', true);
      }

      // When the reset button is clicked
      $('[data-kt-add-invoice-modal-action="cancel"]').on('click', resetInvoiceForm);

      // When the close button is clicked
      $('[data-kt-add-invoice-modal-action="close"]').on('click', resetInvoiceForm);
});