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

            // Group topics by subject
            const subjects = {};
            data.topics.forEach(topic => {
                  const subjectName = topic.subject?.name || 'Uncategorized';
                  if (!subjects[subjectName]) {
                        subjects[subjectName] = [];
                  }
                  subjects[subjectName].push(topic);
            });

            // Build calendar header (subject names)
            let html = `
        <div class="sheet-calendar">
            <div class="calendar-header">
                <div class="header-cell corner-cell"></div>
    `;

            // Add subject headers
            Object.keys(subjects).forEach(subject => {
                  html += `<div class="header-cell subject-header">${subject}</div>`;
            });

            html += `</div><div class="calendar-body">`;

            // Find the subject with most topics to determine row count
            const maxTopics = Math.max(...Object.values(subjects).map(s => s.length));

            // Build calendar rows
            for (let i = 0; i < maxTopics; i++) {
                  html += `<div class="calendar-row">`;
                  html += `<div class="row-header">Topic ${i + 1}</div>`;

                  Object.entries(subjects).forEach(([subject, topics]) => {
                        const topic = topics[i];
                        if (topic) {
                              const isTaken = data.distributedTopics.includes(topic.id);
                              const isActive = topic.status === 'active';
                              const isSelectable = isActive && !isTaken;

                              let cellClass = 'calendar-cell';
                              if (isTaken) cellClass += ' taken';
                              if (!isActive) cellClass += ' inactive';
                              if (isSelectable) cellClass += ' selectable';

                              html += `
                    <div class="${cellClass}" 
                         data-topic-id="${topic.id}"
                         data-topic-name="${topic.name}"
                         data-subject="${subject}">
                        ${topic.name}
                        ${isTaken ? '<div class="status-badge taken-badge">✓</div>' : ''}
                        ${!isActive ? '<div class="status-badge inactive-badge">✗</div>' : ''}
                    </div>
                `;
                        } else {
                              html += `<div class="calendar-cell empty-cell"></div>`;
                        }
                  });

                  html += `</div>`;
            }

            html += `</div></div>
        <div class="mt-4">
            <button type="button" id="reset_notes_distribution" class="btn btn-light">Reset</button>
            <button type="button" id="save_notes_distribution" class="btn btn-primary">Save Distribution</button>
        </div>
        <div class="calendar-legend mt-3">
            <div><span class="legend-color available"></span> Available</div>
            <div><span class="legend-color taken"></span> Already Taken</div>
            <div><span class="legend-color inactive"></span> Inactive</div>
            <div><span class="legend-color selected"></span> Selected</div>
        </div>
    `;

            $container.html(html);

            // Add click handler for selectable cells
            $('.calendar-cell.selectable').on('click', function () {
                  $(this).toggleClass('selected');
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
            $('.calendar-cell.selected').each(function () {
                  selectedTopics.push($(this).data('topic-id'));
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

      $(document).on('click', '#save_notes_distribution', function () {
            saveDistribution();
      });
});