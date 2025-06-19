$(document).ready(function () {
      // Initialize select2
      $('[data-control="select2"]').select2();

      // Student select change handler
      $('#student_select_id').on('change', function () {
            const studentId = $(this).val();

            if (!studentId) {
                  $('#student_paid_sheet_group').val(null).trigger('change').prop('disabled', true);
                  $('#student_notes_distribution').empty();
                  return;
            }

            // Fetch paid sheets
            $.ajax({
                  url: `/sheets/paid/${studentId}`,
                  method: 'GET',
                  success: function (response) {
                        const $sheetSelect = $('#student_paid_sheet_group');
                        $sheetSelect.empty().append('<option></option>');

                        response.sheets.forEach(function (sheet) {
                              $sheetSelect.append(
                                    `<option value="${sheet.id}">${sheet.name} (${sheet.payment_status})</option>`
                              );
                        });

                        $sheetSelect.prop('disabled', false).trigger('change');
                  },
                  error: function (xhr) {
                        console.error(xhr);
                        toastr.error('Failed to fetch paid sheets');
                  }
            });
      });

      // Sheet select change handler
      $('#student_paid_sheet_group').on('change', function () {
            const sheetId = $(this).val();
            const studentId = $('#student_select_id').val();

            if (!sheetId || !studentId) {
                  $('#student_notes_distribution').empty();
                  return;
            }

            // Fetch sheet topics
            $.ajax({
                  url: `/sheets/${sheetId}/topics/${studentId}`,
                  method: 'GET',
                  success: function (response) {
                        // Map topic_name to name for frontend compatibility
                        response.topics = response.topics.map(topic => {
                              return {
                                    ...topic,
                                    name: topic.topic_name
                              };
                        });
                        renderNotesDistribution(response);
                  },
                  error: function (xhr) {
                        console.error(xhr);
                        toastr.error('Failed to fetch sheet topics');
                  }
            });
      });

      // Render function
      function renderNotesDistribution(data) {
            const $container = $('#student_notes_distribution');
            $container.empty();

            if (data.topics.length === 0) {
                  $container.html('<div class="alert alert-info">No topics found for this sheet</div>');
                  return;
            }

            let html = `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="50px">Select</th>
                            <th>Topic</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            data.topics.forEach(function (topic) {
                  const isTaken = data.distributedTopics.includes(topic.id);
                  html += `
                <tr>
                    <td>
                        <input type="checkbox" name="topics[]" value="${topic.id}" 
                            ${isTaken ? 'checked disabled' : ''}
                            ${isTaken ? '' : 'checked'}>
                    </td>
                    <td>${topic.name}</td>
                    <td>${topic.subject?.name ?? 'N/A'}</td>
                    <td>${isTaken ? '<span class="badge badge-success">Already Taken</span>' : '<span class="badge badge-primary">Available</span>'}</td>
                </tr>
            `;
            });

            html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="button" id="reset_notes_distribution" class="btn btn-light">Reset</button>
                <button type="button" id="save_notes_distribution" class="btn btn-primary">Save Distribution</button>
            </div>
        `;

            $container.html(html);

            // Rebind the save button
            $('#save_notes_distribution').off('click').on('click', function () {
                  saveDistribution();
            });
      }

      // Save distribution function
      function saveDistribution() {
            const studentId = $('#student_select_id').val();
            const sheetId = $('#student_paid_sheet_group').val();

            if (!studentId || !sheetId) {
                  toastr.error('Please select both student and sheet');
                  return;
            }

            const selectedTopics = [];
            $('input[name="topics[]"]:checked:not(:disabled)').each(function () {
                  selectedTopics.push($(this).val());
            });

            $.ajax({
                  url: '/sheet-topics/distribute',
                  method: 'POST',
                  data: {
                        student_id: studentId,
                        sheet_id: sheetId,
                        topics: selectedTopics,
                        _token: $('meta[name="csrf-token"]').attr('content')
                  },
                  success: function (response) {
                        toastr.success(response.message);
                        // Refresh the distribution view
                        $('#student_paid_sheet_group').trigger('change');
                  },
                  error: function (xhr) {
                        console.error(xhr);
                        const errorMsg = xhr.responseJSON?.message || 'Failed to save distribution';
                        toastr.error(errorMsg);
                  }
            });
      }

      // Reset button handler
      $(document).on('click', '#reset_notes_distribution', function () {
            $('#student_select_id').val(null).trigger('change');
            $('#student_paid_sheet_group').val(null).trigger('change').prop('disabled', true);
            $('#student_notes_distribution').empty();
      });
});