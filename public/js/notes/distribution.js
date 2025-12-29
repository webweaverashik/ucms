$(document).ready(function () {

      /* ===============================
       * Init
       * =============================== */
      $('[data-control="select2"]').select2();

      /* ===============================
       * Student change → Load sheet groups
       * =============================== */
      $('#student_select_id').on('change', function () {
            const studentId = $(this).val();

            // Reset UI
            $('#student_paid_sheet_group')
                  .empty()
                  .append('<option></option>')
                  .prop('disabled', true)
                  .trigger('change');

            $('#load_topics_btn').prop('disabled', true);
            $('#student_notes_distribution').empty();

            if (!studentId) return;

            $.ajax({
                  url: `/sheets/paid/${studentId}`,
                  method: 'GET',
                  success(response) {
                        const $sheet = $('#student_paid_sheet_group');

                        response.sheets.forEach(sheet => {
                              $sheet.append(
                                    `<option value="${sheet.id}">
                            ${sheet.name} (${sheet.payment_status})
                        </option>`
                              );
                        });

                        $sheet.prop('disabled', false).trigger('change');
                  },
                  error() {
                        toastr.error('Failed to fetch paid sheets');
                  }
            });
      });

      /* ===============================
       * Enable Load Topics button
       * =============================== */
      $('#student_paid_sheet_group').on('change', function () {
            $('#load_topics_btn').prop('disabled', !$(this).val());
            $('#student_notes_distribution').empty();
      });

      /* ===============================
       * Load Topics button
       * =============================== */
      $('#load_topics_btn').on('click', function () {
            const studentId = $('#student_select_id').val();
            const sheetId = $('#student_paid_sheet_group').val();

            if (!studentId || !sheetId) {
                  toastr.warning('Please select student and sheet');
                  return;
            }

            $.ajax({
                  url: `/sheets/${sheetId}/topics/${studentId}`,
                  method: 'GET',
                  beforeSend() {
                        $('#load_topics_btn')
                              .prop('disabled', true)
                              .text('Loading...');
                  },
                  success(response) {
                        // Normalize topic name
                        response.topics = response.topics.map(t => ({
                              ...t,
                              name: t.topic_name
                        }));

                        renderNotesDistribution(response);
                  },
                  error() {
                        toastr.error('Failed to fetch sheet topics');
                  },
                  complete() {
                        $('#load_topics_btn')
                              .prop('disabled', false)
                              .html('<i class="ki-outline ki-eye fs-3 me-2"></i> Load Topics');
                  }
            });
      });

      /* ===============================
       * Render Notes Distribution
       * =============================== */
      function renderNotesDistribution(data) {
            const $container = $('#student_notes_distribution');
            $container.empty();

            if (!data.topics || data.topics.length === 0) {
                  $container.html(`<div class="alert alert-warning">No topics found.</div>`);
                  return;
            }

            /* ---------- Counts ---------- */
            let totalTopics = data.topics.length;
            let takenTopics = 0;
            let inactiveTopics = 0;

            data.topics.forEach(topic => {
                  if (data.distributedTopics.map(String).includes(String(topic.id))) {
                        takenTopics++;
                  } else if (topic.status !== 'active') {
                        inactiveTopics++;
                  }
            });

            const availableTopics = totalTopics - takenTopics - inactiveTopics;

            /* ---------- Group by subject ---------- */
            const subjects = {};
            data.topics.forEach(topic => {
                  const subjectName = topic.subject?.name || 'Uncategorized';

                  subjects[subjectName] ??= {
                        academicGroup: topic.subject?.academic_group || 'General',
                        topics: []
                  };

                  subjects[subjectName].topics.push(topic);
            });

            /* ---------- Order subjects: General → Others ---------- */
            const orderedSubjects = Object.entries(subjects).sort((a, b) => {
                  const gA = a[1].academicGroup;
                  const gB = b[1].academicGroup;

                  if (gA === 'General' && gB !== 'General') return -1;
                  if (gA !== 'General' && gB === 'General') return 1;

                  return gA.localeCompare(gB);
            });

            /* ===============================
             * Build HTML
             * =============================== */
            let html = `
        <!-- Summary Counts -->
        <div class="row g-4 mb-6">

            <div class="col-6 col-md-3">
                <div class="card topic-count total h-100">
                    <div class="card-body text-center">
                        <div class="fs-2hx fw-bold">${totalTopics}</div>
                        <div class="fs-7">Total Topics</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card topic-count taken h-100">
                    <div class="card-body text-center">
                        <div class="fs-2hx fw-bold">${takenTopics}</div>
                        <div class="fs-7">Already Taken</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card topic-count available h-100">
                    <div class="card-body text-center">
                        <div class="fs-2hx fw-bold">${availableTopics}</div>
                        <div class="fs-7">Available</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card topic-count inactive h-100">
                    <div class="card-body text-center">
                        <div class="fs-2hx fw-bold">${inactiveTopics}</div>
                        <div class="fs-7">Inactive</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Calendar -->
        <div class="sheet-calendar">
            <div class="calendar-header">
                <div class="header-cell corner-cell"></div>
        `;

            orderedSubjects.forEach(([subject, s]) => {
                  const badge = s.academicGroup !== 'General'
                        ? `<span class="subject-group-badge ${s.academicGroup.toLowerCase()}">${s.academicGroup}</span>`
                        : '';

                  html += `<div class="header-cell subject-header">${subject}${badge}</div>`;
            });

            html += `</div><div class="calendar-body">`;

            const maxRows = Math.max(...orderedSubjects.map(([_, s]) => s.topics.length));

            for (let i = 0; i < maxRows; i++) {
                  html += `<div class="calendar-row">
                        <div class="row-header">Topic ${i + 1}</div>`;

                  orderedSubjects.forEach(([_, s]) => {
                        const topic = s.topics[i];

                        if (!topic) {
                              html += `<div class="calendar-cell empty-cell"></div>`;
                              return;
                        }

                        const isTaken = data.distributedTopics.map(String).includes(String(topic.id));
                        const isActive = topic.status === 'active';

                        let cls = 'calendar-cell';
                        if (isTaken) cls += ' taken';
                        if (!isActive) cls += ' inactive';
                        if (isActive && !isTaken) cls += ' selectable';

                        html += `<div class="${cls}" data-topic-id="${topic.id}">${topic.name}</div>`;
                  });

                  html += `</div>`;
            }

            html += `
        </div></div>

        <!-- Actions -->
        <div class="mt-4 d-flex gap-2">
            <button id="reset_notes_distribution" class="btn btn-light">
                Clear Selection
            </button>
            <button id="save_notes_distribution" class="btn btn-primary">
                Save Distribution
            </button>
        </div>

        <!-- Legend -->
        <div class="calendar-legend mt-4 d-flex flex-wrap gap-6 fs-7">
            <div class="d-flex align-items-center gap-2">
                <span class="legend-color available"></span> Available
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-color taken"></span> Already Taken
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-color inactive"></span> Inactive
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="legend-color selected"></span> Selected
            </div>
        </div>
        `;

            $container.html(html);

            $('.calendar-cell.selectable').on('click', function () {
                  $(this).toggleClass('selected');
            });
      }

      /* ===============================
       * Save Distribution
       * =============================== */
      $(document).on('click', '#save_notes_distribution', function () {
            const selectedTopics = $('.calendar-cell.selected')
                  .map((_, el) => $(el).data('topic-id'))
                  .get();

            $.ajax({
                  url: '/sheet-topics/distribute',
                  method: 'POST',
                  data: {
                        student_id: $('#student_select_id').val(),
                        sheet_id: $('#student_paid_sheet_group').val(),
                        topics: selectedTopics,
                        _token: $('meta[name="csrf-token"]').attr('content')
                  },
                  success(res) {
                        toastr.success(res.message);
                        $('#load_topics_btn').trigger('click');
                  },
                  error() {
                        toastr.error('Failed to save distribution');
                  }
            });
      });

      /* ===============================
       * Reset Selection
       * =============================== */
      $(document).on('click', '#reset_notes_distribution', function () {
            $('.calendar-cell.selected').removeClass('selected');
            toastr.info('Selection cleared');
      });

});
