$(document).ready(function () {

      // ------------------------------
      // 1. FILTER BATCHES BY BRANCH
      // ------------------------------
      $('#student_branch_group').on('change', function () {
            let branchID = $(this).val();

            $.ajax({
                  url: window.UCMS.routes.batchesByBranch,
                  type: "GET",
                  data: { branch_id: branchID },

                  success: function (data) {
                        let batchSelect = $('#student_batch_group');

                        batchSelect.empty().append('<option></option>');

                        data.forEach(batch => {
                              batchSelect.append(
                                    `<option value="${batch.id}">${batch.name}</option>`
                              );
                        });

                        batchSelect.val('').trigger('change');
                  }
            });
      });


      // ------------------------------
      // 2. LOAD STUDENTS VIA AJAX
      // ------------------------------
      $('#student_list_filter_form').on('submit', function (e) {
            e.preventDefault();

            $('#student_list_loader').show();

            $.ajax({
                  url: window.UCMS.routes.loadStudents,
                  type: "GET",
                  data: $(this).serialize(),

                  success: function (response) {
                        $('#student_list_loader').hide();

                        let students = response.students;
                        let savedAttendance = response.attendance;
                        let offDay = response.off_day;

                        // ------------------------------
                        // OFF-DAY WARNING
                        // ------------------------------
                        if (offDay) {
                              $('#off_day_warning').html(`
                        <div class="alert alert-warning fw-bold fs-5">
                            ⚠ Warning: Today is an off-day for this batch.
                        </div>
                    `);
                        } else {
                              $('#off_day_warning').html("");
                        }

                        // ------------------------------
                        // BUILD STUDENT LIST TABLE
                        // ------------------------------
                        let html = `
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="10%">Roll</th>
                                <th>Name</th>
                                <th class="text-center">Present</th>
                                <th class="text-center">Late</th>
                                <th class="text-center">Leave</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                        students.forEach(student => {
                              let existing = savedAttendance[student.id]?.status ?? "";

                              html += `
                        <tr>
                            <td>${student.student_unique_id ?? ''}</td>
                            <td>${student.name}</td>

                            <td class="text-center">
                                <input type="radio" name="status_${student.id}" value="present"
                                ${existing === 'present' ? 'checked' : ''}>
                            </td>

                            <td class="text-center">
                                <input type="radio" name="status_${student.id}" value="late"
                                ${existing === 'late' ? 'checked' : ''}>
                            </td>

                            <td class="text-center">
                                <input type="radio" name="status_${student.id}" value="absent"
                                ${existing === 'absent' ? 'checked' : ''}>
                            </td>
                        </tr>
                    `;
                        });

                        html += `</tbody></table>`;

                        $('#student_list_container').html(html);

                        $('#bulk_buttons').show();
                        $('#save_attendance_section').show();
                  },

                  error: function () {
                        $('#student_list_loader').hide();
                        toastr.error("Failed to load students.");
                  }
            });
      });


      // ------------------------------
      // 3. BULK BUTTONS
      // ------------------------------

      $('#mark_all_present').on('click', function () {
            $('input[value="present"]').prop('checked', true);
      });

      $('#mark_all_late').on('click', function () {
            $('input[value="late"]').prop('checked', true);
      });


      // ------------------------------
      // 4. SAVE ATTENDANCE VIA AJAX
      // ------------------------------
      $('#save_attendance_button').on('click', function () {

            let formData = $('#student_list_filter_form').serializeArray();
            let attendance = [];

            // Extract selected values
            $('#student_list_container input[type="radio"]:checked').each(function () {
                  let studentId = $(this).attr('name').split('_')[1];
                  let status = $(this).val();

                  attendance.push({
                        student_id: studentId,
                        status: status
                  });
            });

            // Add attendance array to formData
            formData.push({
                  name: "attendance",
                  value: JSON.stringify(attendance)
            });

            $.ajax({
                  url: window.UCMS.routes.storeAttendance,
                  type: "POST",
                  data: formData,

                  success: function () {
                        toastr.success("Attendance saved successfully!");
                  },

                  error: function () {
                        toastr.error("Failed to save attendance.");
                  }
            });
      });

});
