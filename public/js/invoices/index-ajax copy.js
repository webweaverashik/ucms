$(document).ready(function () {
      // 1. Handle student selection change
      $('select[name="invoice_student"]').on('change', function () {
            const studentId = $(this).val();
            const monthYearSelect = $('select[name="invoice_month_year"]');
            const invoiceTypeSelect = $('select[name="invoice_type"]');
            const invoiceAmountInput = $('input[name="invoice_amount"]');

            if (studentId) {
                  invoiceTypeSelect.prop('disabled', false);
                  monthYearSelect.prop('disabled', false);

                  monthYearSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
                  invoiceAmountInput.val('').prop('disabled', true);

                  $.ajax({
                        url: `/students/${studentId}/last-invoice-month`,
                        method: 'GET',
                        success: function (data) {
                              console.log('Invoice data:', data);

                              // Store tuition fee
                              invoiceAmountInput.data('tuition-fee', data.tuition_fee);

                              // Clear and prepare dropdown
                              monthYearSelect.empty().append('<option value=""></option>');

                              if (data.last_invoice_month) {
                                    // Calculate next month after last invoice
                                    const [lastMonth, lastYear] = data.last_invoice_month.split('_').map(Number);
                                    let nextMonth = lastMonth + 1;
                                    let nextYear = lastYear;

                                    // Handle December → January transition
                                    if (nextMonth > 12) {
                                          nextMonth = 1;
                                          nextYear++;
                                    }

                                    // Format values
                                    const monthStr = String(nextMonth).padStart(2, '0');
                                    const monthYear = `${monthStr}_${nextYear}`;
                                    const monthName = new Date(nextYear, nextMonth - 1, 1)
                                          .toLocaleString('default', { month: 'long' });

                                    // Add to dropdown
                                    monthYearSelect.append(
                                          $('<option></option>')
                                                .val(monthYear)
                                                .text(`${monthName} ${nextYear}`)
                                    );
                              } else {
                                    // If no invoices exist, show current month
                                    const currentDate = new Date();
                                    const currentMonth = currentDate.getMonth() + 1;
                                    const currentYear = currentDate.getFullYear();
                                    const monthStr = String(currentMonth).padStart(2, '0');
                                    const monthYear = `${monthStr}_${currentYear}`;
                                    const monthName = currentDate.toLocaleString('default', { month: 'long' });

                                    monthYearSelect.append(
                                          $('<option></option>')
                                                .val(monthYear)
                                                .text(`${monthName} ${currentYear}`)
                                    );
                              }

                              monthYearSelect.prop('disabled', false).trigger('change');

                              if (invoiceTypeSelect.val() === 'tuition_fee' && monthYearSelect.val()) {
                                    invoiceAmountInput.val(invoiceAmountInput.data('tuition-fee')).prop('disabled', false);
                              }
                        },
                        error: function (error) {
                              console.error('Error:', error);
                              monthYearSelect.empty().append('<option value="">Error loading months</option>');
                        }
                  });
            } else {
                  invoiceTypeSelect.prop('disabled', true);
                  monthYearSelect.prop('disabled', true).empty().append('<option value=""></option>').trigger('change');
                  invoiceAmountInput.val('').prop('disabled', true);
            }
      });

      // 2. Handle month/year selection
      $('select[name="invoice_month_year"]').on('change', function () {
            const invoiceType = $('select[name="invoice_type"]').val();
            const invoiceAmountInput = $('input[name="invoice_amount"]');

            if ($(this).val()) {
                  invoiceAmountInput.prop('disabled', false);
                  if (invoiceType === 'tuition_fee') {
                        invoiceAmountInput.val(invoiceAmountInput.data('tuition-fee'));
                  }
            } else {
                  invoiceAmountInput.prop('disabled', true);
            }
      });

      // 3. Handle invoice type change
      $('select[name="invoice_type"]').on('change', function () {
            const selectedType = $(this).val();
            const studentId = $('select[name="invoice_student"]').val();
            const monthYearSection = $('#month_year_id');
            const monthYearSelect = $('select[name="invoice_month_year"]');
            const invoiceAmountInput = $('input[name="invoice_amount"]');

            if (selectedType === 'sheet_fee') {
                  // Hide and disable month/year
                  monthYearSection.hide();
                  monthYearSelect.prop('required', false);

                  invoiceAmountInput.prop('disabled', true).val('');

                  if (studentId) {
                        // Fetch sheet fee amount from backend
                        $.ajax({
                              url: `/students/${studentId}/sheet-fee`,
                              method: 'GET',
                              success: function (response) {
                                    if (response.sheet_fee) {
                                          invoiceAmountInput.val(response.sheet_fee).prop('disabled', false);
                                    } else {
                                          invoiceAmountInput.val('0').prop('disabled', false);
                                          toastr.warning('No sheet fee found for the student\'s class.');
                                    }
                              },
                              error: function () {
                                    invoiceAmountInput.val('').prop('disabled', false);
                                    toastr.error('Failed to fetch sheet fee.');
                              }
                        });
                  } else {
                        invoiceAmountInput.val('').prop('disabled', true);
                  }
            } else if (selectedType !== 'tuition_fee') {
                  // Other than tuition fee and sheet fee
                  monthYearSection.hide();
                  monthYearSelect.prop('required', false);
                  invoiceAmountInput.prop('disabled', false).val('');
            } else {
                  // Tuition fee is selected
                  monthYearSection.show();
                  monthYearSelect.prop('required', true);
                  invoiceAmountInput.prop('disabled', !monthYearSelect.val());

                  if (monthYearSelect.val()) {
                        invoiceAmountInput.val(invoiceAmountInput.data('tuition-fee'));
                  }
            }
      });


      // 4. Form reset handling
      function resetInvoiceForm() {
            $('select[data-control="select2"]').val(null).trigger('change');
            $('select[name="invoice_type"]').prop('disabled', true);
            $('select[name="invoice_month_year"]').prop('disabled', true);
            $('input[name="invoice_amount"]').val('').prop('disabled', true);
            $('select[name="invoice_type"]').val('tuition_fee').trigger('change');
            $('select[name="invoice_month_year"]').prop('required', true);
      }

      $('[data-kt-add-invoice-modal-action="cancel"], [data-kt-add-invoice-modal-action="close"]').on('click', resetInvoiceForm);
});