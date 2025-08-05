$(document).ready(function () {
      // Initialize with month_year_type radio disabled
      $('input[name="month_year_type"]').prop('disabled', true);

      // 1. Handle student selection change
      $('select[name="invoice_student"]').on('change', function () {
            const studentId = $(this).val();
            const monthYearSelect = $('select[name="invoice_month_year"]');
            const invoiceTypeSelect = $('select[name="invoice_type"]');
            const invoiceAmountInput = $('input[name="invoice_amount"]');
            const monthYearTypeRadios = $('input[name="month_year_type"]');

            if (studentId) {
                  invoiceTypeSelect.prop('disabled', false);
                  monthYearSelect.prop('disabled', false);
                  monthYearTypeRadios.prop('disabled', false);

                  monthYearSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);
                  invoiceAmountInput.val('').prop('disabled', true);

                  $.ajax({
                        url: `/students/${studentId}/invoice-months-data`,
                        method: 'GET',
                        success: function (data) {
                              console.log('Invoice data:', data);

                              // Store data for later use
                              invoiceAmountInput.data('tuition-fee', data.tuition_fee);
                              monthYearSelect.data('payment-style', data.payment_style);
                              monthYearSelect.data('invoice-months', data);

                              // Clear and prepare dropdown
                              monthYearSelect.empty().append('<option value=""></option>');

                              // Calculate new invoice month (next month after last invoice)
                              if (data.last_invoice_month) {
                                    const [lastMonth, lastYear] = data.last_invoice_month.split('_').map(Number);
                                    let nextMonth = lastMonth + 1;
                                    let nextYear = lastYear;

                                    // Handle December → January transition
                                    if (nextMonth > 12) {
                                          nextMonth = 1;
                                          nextYear++;
                                    }

                                    const monthStr = String(nextMonth).padStart(2, '0');
                                    const monthYear = `${monthStr}_${nextYear}`;
                                    const monthName = new Date(nextYear, nextMonth - 1, 1)
                                          .toLocaleString('default', { month: 'long' });

                                    monthYearSelect.append(
                                          $('<option></option>')
                                                .val(monthYear)
                                                .text(`${monthName} ${nextYear}`)
                                    );
                              } else {
                                    // For new students with no invoices
                                    const currentDate = new Date();
                                    let displayMonth, displayYear;

                                    if (data.payment_style === 'due') {
                                          // For due payments, show previous month
                                          displayMonth = currentDate.getMonth(); // months are 0-11
                                          displayYear = currentDate.getFullYear();
                                    } else {
                                          // For current payments, show current month
                                          displayMonth = currentDate.getMonth() + 1;
                                          displayYear = currentDate.getFullYear();
                                    }

                                    const monthStr = String(displayMonth).padStart(2, '0');
                                    const monthYear = `${monthStr}_${displayYear}`;
                                    const monthName = new Date(displayYear, displayMonth - 1, 1)
                                          .toLocaleString('default', { month: 'long' });

                                    monthYearSelect.append(
                                          $('<option></option>')
                                                .val(monthYear)
                                                .text(`${monthName} ${displayYear}`)
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
                  monthYearTypeRadios.prop('disabled', true);
            }
      });

      // 2. Handle month/year type radio button change
      $('input[name="month_year_type"]').on('change', function () {
            const studentId = $('select[name="invoice_student"]').val();
            const monthYearSelect = $('select[name="invoice_month_year"]');
            const invoiceType = $('select[name="invoice_type"]').val();
            const invoiceAmountInput = $('input[name="invoice_amount"]');
            const paymentStyle = monthYearSelect.data('payment-style');
            const invoiceMonthsData = monthYearSelect.data('invoice-months');

            if (!studentId) return;

            const selectedType = $(this).val();

            if (selectedType === 'new_invoice') {
                  // For new invoice, show next month after last invoice
                  monthYearSelect.empty().append('<option value=""></option>');

                  if (invoiceMonthsData.last_invoice_month) {
                        const [lastMonth, lastYear] = invoiceMonthsData.last_invoice_month.split('_').map(Number);
                        let nextMonth = lastMonth + 1;
                        let nextYear = lastYear;

                        // Handle December → January transition
                        if (nextMonth > 12) {
                              nextMonth = 1;
                              nextYear++;
                        }

                        const monthStr = String(nextMonth).padStart(2, '0');
                        const monthYear = `${monthStr}_${nextYear}`;
                        const monthName = new Date(nextYear, nextMonth - 1, 1)
                              .toLocaleString('default', { month: 'long' });

                        monthYearSelect.append(
                              $('<option></option>')
                                    .val(monthYear)
                                    .text(`${monthName} ${nextYear}`)
                        );
                  } else {
                        // For new students with no invoices
                        const currentDate = new Date();
                        let displayMonth, displayYear;

                        if (paymentStyle === 'due') {
                              // For due payments, show previous month
                              displayMonth = currentDate.getMonth(); // months are 0-11
                              displayYear = currentDate.getFullYear();
                        } else {
                              // For current payments, show current month
                              displayMonth = currentDate.getMonth() + 1;
                              displayYear = currentDate.getFullYear();
                        }

                        const monthStr = String(displayMonth).padStart(2, '0');
                        const monthYear = `${monthStr}_${displayYear}`;
                        const monthName = new Date(displayYear, displayMonth - 1, 1)
                              .toLocaleString('default', { month: 'long' });

                        monthYearSelect.append(
                              $('<option></option>')
                                    .val(monthYear)
                                    .text(`${monthName} ${displayYear}`)
                        );
                  }
            } else if (selectedType === 'old_invoice') {
                  // For old invoice, show month before oldest invoice
                  monthYearSelect.empty().append('<option value=""></option>');

                  if (invoiceMonthsData.oldest_invoice_month) {
                        const [oldestMonth, oldestYear] = invoiceMonthsData.oldest_invoice_month.split('_').map(Number);
                        let prevMonth = oldestMonth - 1;
                        let prevYear = oldestYear;

                        // Handle January → December transition
                        if (prevMonth < 1) {
                              prevMonth = 12;
                              prevYear--;
                        }

                        const monthStr = String(prevMonth).padStart(2, '0');
                        const monthYear = `${monthStr}_${prevYear}`;
                        const monthName = new Date(prevYear, prevMonth - 1, 1)
                              .toLocaleString('default', { month: 'long' });

                        monthYearSelect.append(
                              $('<option></option>')
                                    .val(monthYear)
                                    .text(`${monthName} ${prevYear}`)
                        );
                  } else {
                        // For new students with no invoices
                        const currentDate = new Date();
                        let displayMonth, displayYear;

                        if (paymentStyle === 'due') {
                              // For due payments, show month before previous (current - 2)
                              displayMonth = currentDate.getMonth() - 1; // months are 0-11
                              displayYear = currentDate.getFullYear();
                              if (displayMonth < 0) {
                                    displayMonth = 11;
                                    displayYear--;
                              }
                        } else {
                              // For current payments, show previous month
                              displayMonth = currentDate.getMonth(); // months are 0-11
                              displayYear = currentDate.getFullYear();
                              if (displayMonth < 0) {
                                    displayMonth = 11;
                                    displayYear--;
                              }
                        }

                        const monthStr = String(displayMonth + 1).padStart(2, '0');
                        const monthYear = `${monthStr}_${displayYear}`;
                        const monthName = new Date(displayYear, displayMonth, 1)
                              .toLocaleString('default', { month: 'long' });

                        monthYearSelect.append(
                              $('<option></option>')
                                    .val(monthYear)
                                    .text(`${monthName} ${displayYear}`)
                        );
                  }
            }

            if (monthYearSelect.val() && invoiceType === 'tuition_fee') {
                  invoiceAmountInput.val(invoiceAmountInput.data('tuition-fee')).prop('disabled', false);
            } else {
                  invoiceAmountInput.val('').prop('disabled', true);
            }
      });

      // 3. Handle month/year selection
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

      // 4. Handle invoice type change
      $('select[name="invoice_type"]').on('change', function () {
            const selectedType = $(this).val();
            const studentId = $('select[name="invoice_student"]').val();
            const monthYearTypeSelect = $('#month_year_type_id');
            const monthYearSection = $('#month_year_id');
            const monthYearSelect = $('select[name="invoice_month_year"]');
            const invoiceAmountInput = $('input[name="invoice_amount"]');
            const monthYearTypeRadios = $('input[name="month_year_type"]');

            if (selectedType === 'sheet_fee') {
                  // Hide and disable month/year
                  monthYearTypeSelect.hide();
                  monthYearSection.hide();
                  monthYearSelect.prop('required', false);
                  monthYearTypeRadios.prop('disabled', true);

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
                  monthYearTypeSelect.hide();
                  monthYearSection.hide();
                  monthYearSelect.prop('required', false);
                  monthYearTypeRadios.prop('disabled', true);
                  invoiceAmountInput.prop('disabled', false).val('');
            } else {
                  // Tuition fee is selected
                  monthYearTypeSelect.show();
                  monthYearSection.show();
                  monthYearSelect.prop('required', true);
                  monthYearTypeRadios.prop('disabled', false);
                  invoiceAmountInput.prop('disabled', !monthYearSelect.val());

                  if (monthYearSelect.val()) {
                        invoiceAmountInput.val(invoiceAmountInput.data('tuition-fee'));
                  }
            }
      });

      // 5. Form reset handling
      function resetInvoiceForm() {
            $('select[data-control="select2"]').val(null).trigger('change');
            $('select[name="invoice_type"]').prop('disabled', true);
            $('select[name="invoice_month_year"]').prop('disabled', true);
            $('input[name="invoice_amount"]').val('').prop('disabled', true);
            $('select[name="invoice_type"]').val('tuition_fee').trigger('change');
            $('select[name="invoice_month_year"]').prop('required', true);
            $('input[name="month_year_type"]').prop('disabled', true);
            $('#new_invoice_input').prop('checked', true);
      }

      $('[data-kt-add-invoice-modal-action="cancel"], [data-kt-add-invoice-modal-action="close"]').on('click', resetInvoiceForm);
});