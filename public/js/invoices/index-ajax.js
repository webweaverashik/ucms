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

                              const currentDate = new Date();
                              const currentMonth = currentDate.getMonth() + 1;
                              const currentYear = currentDate.getFullYear();

                              let startMonth, startYear;

                              // If it's 25th+ and current month invoice exists, start from next month
                              if (data.should_show_next_month) {
                                    startMonth = currentMonth + 1;
                                    startYear = currentYear;

                                    if (startMonth > 12) {
                                          startMonth = 1;
                                          startYear++;
                                    }

                                    // Generate only the next month with (Advance) label
                                    monthYearSelect.empty().append('<option value=""></option>');
                                    const nextMonthDate = new Date(startYear, startMonth - 1, 1);
                                    const month = nextMonthDate.getMonth() + 1;
                                    const year = nextMonthDate.getFullYear();
                                    const monthStr = String(month).padStart(2, '0');
                                    const monthYear = `${monthStr}_${year}`;
                                    const monthName = nextMonthDate.toLocaleString('default', { month: 'long' });

                                    monthYearSelect.append(
                                          $('<option></option>')
                                                .val(monthYear)
                                                .text(`${monthName} ${year} (Advance)`)
                                                .data('is-advance', true)
                                    );

                              }
                              // Otherwise use normal logic
                              else if (data.last_invoice_month) {
                                    const [lastMonth, lastYear] = data.last_invoice_month.split('_').map(Number);
                                    startMonth = lastMonth + 1;
                                    startYear = lastYear;

                                    if (startMonth > 12) {
                                          startMonth = 1;
                                          startYear++;
                                    }

                                    // Generate months from start to current
                                    monthYearSelect.empty().append('<option value=""></option>');
                                    let date = new Date(startYear, startMonth - 1, 1);
                                    const endDate = new Date(currentYear, currentMonth - 1, 1);

                                    while (date <= endDate) {
                                          const month = date.getMonth() + 1;
                                          const year = date.getFullYear();
                                          const monthStr = String(month).padStart(2, '0');
                                          const monthYear = `${monthStr}_${year}`;
                                          const monthName = date.toLocaleString('default', { month: 'long' });

                                          monthYearSelect.append(
                                                $('<option></option>').val(monthYear).text(`${monthName} ${year}`)
                                          );

                                          date.setMonth(date.getMonth() + 1);
                                    }
                              } else {
                                    // No invoices - start from current month
                                    startMonth = currentMonth;
                                    startYear = currentYear;

                                    monthYearSelect.empty().append('<option value=""></option>');
                                    const monthStr = String(startMonth).padStart(2, '0');
                                    const monthYear = `${monthStr}_${startYear}`;
                                    const monthName = new Date(startYear, startMonth - 1, 1)
                                          .toLocaleString('default', { month: 'long' });

                                    monthYearSelect.append(
                                          $('<option></option>').val(monthYear).text(`${monthName} ${startYear}`)
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
                  monthYearSection.hide();
                  monthYearSelect.prop('required', false);
                  invoiceAmountInput.prop('disabled', false).val('');
            } else {
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