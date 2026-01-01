/**
 * Bulk Notes Distribution JavaScript
 * Handles bulk distribution of sheet topics to students
 */

$(document).ready(function () {

      /* ===============================
       * Initialize Select2
       * =============================== */
      $('[data-control="select2"]').select2();

      /* ===============================
       * Sheet Group Change → Load Topics
       * =============================== */
      $('#bulk_sheet_group_select').on('change', function () {
            const sheetId = $(this).val();
            const $topicSelect = $('#bulk_sheet_topic_select');

            // Reset
            $topicSelect.empty().append('<option></option>').prop('disabled', true).trigger('change');
            $('#bulk_load_students_btn').prop('disabled', true);
            resetStudentsView();

            if (!sheetId) return;

            // Show loading
            $topicSelect.prop('disabled', true);

            $.ajax({
                  url: `/sheets/${sheetId}/topics-list`,
                  method: 'GET',
                  beforeSend: function () {
                        // Optional: Add loading indicator
                  },
                  success: function (response) {
                        if (response.success && response.topics) {
                              response.topics.forEach(function (topic) {
                                    const groupBadge = topic.academic_group !== 'General'
                                          ? ` (${topic.academic_group})`
                                          : '';

                                    $topicSelect.append(
                                          `<option value="${topic.id}" 
                                data-subject="${topic.subject}"
                                data-group="${topic.academic_group}"
                                data-name="${topic.name}">
                                ${topic.name} - ${topic.subject}${groupBadge}
                            </option>`
                                    );
                              });

                              $topicSelect.prop('disabled', false);
                              toastr.success(`${response.topics.length} topics loaded`);
                        }
                  },
                  error: function (xhr) {
                        toastr.error('Failed to load topics');
                        console.error('Error loading topics:', xhr);
                  }
            });
      });

      /* ===============================
       * Topic Change → Enable Load Button
       * =============================== */
      $('#bulk_sheet_topic_select').on('change', function () {
            const topicId = $(this).val();
            $('#bulk_load_students_btn').prop('disabled', !topicId);
            resetStudentsView();
      });

      /* ===============================
       * Load Students Button
       * =============================== */
      $('#bulk_load_students_btn').on('click', function () {
            const sheetId = $('#bulk_sheet_group_select').val();
            const topicId = $('#bulk_sheet_topic_select').val();
            const $topicOption = $('#bulk_sheet_topic_select option:selected');

            if (!sheetId || !topicId) {
                  toastr.warning('Please select both Sheet Group and Topic');
                  return;
            }

            const $btn = $(this);

            $.ajax({
                  url: `/sheets/${sheetId}/topics/${topicId}/pending-students`,
                  method: 'GET',
                  beforeSend: function () {
                        $btn.prop('disabled', true)
                              .html('<span class="spinner-border spinner-border-sm me-2"></span> Loading...');
                  },
                  success: function (response) {
                        if (response.success) {
                              // Update topic banner
                              $('#banner_topic_name').text(response.topic.name);
                              $('#banner_subject_name').text(response.topic.subject);
                              $('#banner_class_name').text(response.sheet.class_name);

                              const group = response.topic.academic_group;
                              $('#banner_group_badge')
                                    .text(group)
                                    .removeClass('badge-light-info badge-light-primary badge-light-warning badge-light-secondary')
                                    .addClass(getGroupBadgeClass(group));

                              $('#bulk_topic_banner').removeClass('d-none');

                              // Update stats
                              $('#stat_total_paid').text(response.stats.total_paid);
                              $('#stat_already_distributed').text(response.stats.already_distributed);
                              $('#stat_pending').text(response.stats.pending);
                              $('#stat_selected').text(0);
                              $('#bulk_info_cards').removeClass('d-none');

                              // Render students
                              renderStudents(response.students);

                              toastr.success(`${response.students.length} pending students loaded`);
                        }
                  },
                  error: function (xhr) {
                        toastr.error('Failed to load students');
                        console.error('Error loading students:', xhr);
                  },
                  complete: function () {
                        $btn.prop('disabled', false)
                              .html('<i class="ki-outline ki-people fs-3 me-2"></i> Load Students');
                  }
            });
      });

      /* ===============================
       * Render Students Grid
       * =============================== */
      function renderStudents(students) {
            const $grid = $('#bulk_students_grid');
            $grid.empty();

            if (students.length === 0) {
                  $('#bulk_empty_state').removeClass('d-none')
                        .find('h3').text('All Students Distributed!');
                  $('#bulk_empty_state p').html('All students who paid for this sheet have already received this topic.');
                  $('#bulk_students_list').addClass('d-none');
                  return;
            }

            $('#bulk_empty_state').addClass('d-none');
            $('#bulk_students_list').removeClass('d-none');

            students.forEach(function (student) {
                  const initials = getInitials(student.name);
                  const avatarColor = getAvatarColor(student.id);

                  $grid.append(`
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="student-card card card-flush h-100" data-student-id="${student.id}">
                        <div class="card-body d-flex align-items-center p-4">
                            <div class="form-check form-check-custom form-check-solid me-4">
                                <input type="checkbox" class="form-check-input student-checkbox" value="${student.id}">
                            </div>
                            <div class="symbol symbol-45px symbol-circle me-4">
                                <span class="symbol-label bg-light-${avatarColor} text-${avatarColor} fs-5 fw-bold">
                                    ${initials}
                                </span>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-bold text-gray-800 text-truncate">${student.name}</div>
                                <div class="text-gray-500 fs-7">${student.student_unique_id}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            });
      }

      /* ===============================
       * Student Card Click Handler
       * =============================== */
      $(document).on('click', '.student-card', function (e) {
            if ($(e.target).is('input[type="checkbox"]')) return;

            $(this).toggleClass('selected');
            $(this).find('.student-checkbox').prop('checked', $(this).hasClass('selected'));
            updateSelectedCount();
      });

      $(document).on('change', '.student-checkbox', function () {
            $(this).closest('.student-card').toggleClass('selected', $(this).is(':checked'));
            updateSelectedCount();
      });

      /* ===============================
       * Select All / Clear Selection
       * =============================== */
      $('#bulk_select_all_btn').on('click', function () {
            $('.student-card:visible').addClass('selected')
                  .find('.student-checkbox').prop('checked', true);
            updateSelectedCount();
            toastr.info('All visible students selected');
      });

      $('#bulk_clear_selection_btn').on('click', function () {
            $('.student-card').removeClass('selected')
                  .find('.student-checkbox').prop('checked', false);
            updateSelectedCount();
            toastr.info('Selection cleared');
      });

      $('#bulk_reset_btn').on('click', function () {
            $('.student-card').removeClass('selected')
                  .find('.student-checkbox').prop('checked', false);
            updateSelectedCount();
            toastr.info('Selection cleared');
      });

      /* ===============================
       * Student Search
       * =============================== */
      $('#bulk_student_search').on('input', function () {
            const query = $(this).val().toLowerCase();

            $('.student-card').each(function () {
                  const $card = $(this);
                  const name = $card.find('.text-gray-800').text().toLowerCase();
                  const id = $card.find('.text-gray-500').text().toLowerCase();

                  $card.closest('.col-sm-6').toggle(name.includes(query) || id.includes(query));
            });
      });

      /* ===============================
       * Distribute Button
       * =============================== */
      $('#bulk_distribute_btn').on('click', function () {
            const selectedIds = $('.student-card.selected').map(function () {
                  return $(this).data('student-id');
            }).get();

            if (selectedIds.length === 0) {
                  toastr.warning('Please select at least one student');
                  return;
            }

            const sheetId = $('#bulk_sheet_group_select').val();
            const topicId = $('#bulk_sheet_topic_select').val();
            const $btn = $(this);

            Swal.fire({
                  title: 'Confirm Distribution',
                  html: `Are you sure you want to distribute this topic to <strong>${selectedIds.length}</strong> student(s)?`,
                  icon: 'question',
                  showCancelButton: true,
                  confirmButtonText: 'Yes, Distribute',
                  cancelButtonText: 'Cancel',
                  confirmButtonColor: '#50cd89',
                  cancelButtonColor: '#f1416c'
            }).then((result) => {
                  if (result.isConfirmed) {
                        $.ajax({
                              url: '/sheet-topics/bulk-distribute',
                              method: 'POST',
                              data: {
                                    sheet_id: sheetId,
                                    topic_id: topicId,
                                    student_ids: selectedIds,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                              },
                              beforeSend: function () {
                                    $btn.prop('disabled', true)
                                          .html('<span class="spinner-border spinner-border-sm me-2"></span> Distributing...');
                              },
                              success: function (response) {
                                    if (response.success) {
                                          toastr.success(response.message);

                                          // Remove distributed students from grid
                                          selectedIds.forEach(function (id) {
                                                $(`.student-card[data-student-id="${id}"]`)
                                                      .closest('.col-sm-6')
                                                      .fadeOut(300, function () {
                                                            $(this).remove();

                                                            // Update stats
                                                            const currentPending = parseInt($('#stat_pending').text());
                                                            const currentDistributed = parseInt($('#stat_already_distributed').text());
                                                            $('#stat_pending').text(currentPending - 1);
                                                            $('#stat_already_distributed').text(currentDistributed + 1);
                                                      });
                                          });

                                          updateSelectedCount();

                                          // Check if no students left
                                          setTimeout(function () {
                                                if ($('#bulk_students_grid .student-card').length === 0) {
                                                      $('#bulk_students_list').addClass('d-none');
                                                      $('#bulk_empty_state').removeClass('d-none')
                                                            .find('h3').text('All Students Distributed!');
                                                      $('#bulk_empty_state p').html('All students who paid for this sheet have already received this topic.');
                                                }
                                          }, 400);
                                    } else {
                                          toastr.error(response.message || 'Distribution failed');
                                    }
                              },
                              error: function (xhr) {
                                    const errorMsg = xhr.responseJSON?.message || 'Failed to distribute';
                                    toastr.error(errorMsg);
                                    console.error('Error distributing:', xhr);
                              },
                              complete: function () {
                                    $btn.prop('disabled', false)
                                          .html('<i class="ki-outline ki-send fs-4 me-2"></i> Distribute to Selected');
                              }
                        });
                  }
            });
      });

      /* ===============================
       * Helper Functions
       * =============================== */
      function resetStudentsView() {
            $('#bulk_empty_state').removeClass('d-none')
                  .find('h3').text('No Students Loaded');
            $('#bulk_empty_state p').html(
                  'Select a Sheet Group and Topic, then click "Load Students" to view<br>' +
                  'students who have paid but not yet received this topic.'
            );
            $('#bulk_students_list').addClass('d-none');
            $('#bulk_info_cards').addClass('d-none');
            $('#bulk_topic_banner').addClass('d-none');
            $('#bulk_students_grid').empty();
      }

      function updateSelectedCount() {
            const count = $('.student-card.selected').length;
            $('#stat_selected').text(count);
            $('#bulk_selection_summary').text(`${count} student${count !== 1 ? 's' : ''} selected`);
            $('#bulk_distribute_btn').prop('disabled', count === 0);

            // Add pulse animation to selected card
            if (count > 0) {
                  $('.topic-count.selected').addClass('pulse-animation');
            } else {
                  $('.topic-count.selected').removeClass('pulse-animation');
            }
      }

      function getGroupBadgeClass(group) {
            const classes = {
                  'Science': 'badge-light-info',
                  'Commerce': 'badge-light-primary',
                  'Arts': 'badge-light-warning',
                  'General': 'badge-light-secondary'
            };
            return classes[group] || 'badge-light-secondary';
      }

      function getInitials(name) {
            if (!name) return '?';
            const parts = name.split(' ');
            if (parts.length >= 2) {
                  return (parts[0][0] + parts[1][0]).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
      }

      function getAvatarColor(id) {
            const colors = ['primary', 'success', 'info', 'warning', 'danger'];
            return colors[id % colors.length];
      }

});