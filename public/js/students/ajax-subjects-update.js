$(function () {
     // DOM elements
     const $classSelect = $('#student_class_input');
     const $institutionSelect = $('#institution_select');
     const $groupSection = $('#student-group-selection');
     const $subjectContainer = $('#subject_list');

     // Initialize
     $classSelect.select2();
     $institutionSelect.select2();
     initEventHandlers();
     initPage();

     function initEventHandlers() {
          $classSelect.on('change', handleClassChange);
          $(document).on('change', '[name="student_academic_group"]', loadSubjects);
          $(document).on('change', '#select_all_subjects', toggleSelectAll);
          $(document).on('change', '.subject-checkbox', updateSelectAllState);
     }

     function initPage() {
          updateGroupVisibility();
          if ($classSelect.val()) {
               loadSubjects(); // Only load subjects initially
          }
          // Institution select keeps its server-side selected value
     }

     function handleClassChange() {
          updateGroupVisibility();
          loadInstitutions(); // Always reload institutions when class changes
          loadSubjects();
     }

     function updateGroupVisibility() {
          const classNumeral = getClassNumeral();
          const isJuniorClass = classNumeral >= 1 && classNumeral <= 8;

          $groupSection.toggle(!isJuniorClass);
          $('[name="student_academic_group"]').prop('disabled', isJuniorClass);

          if (isJuniorClass) {
               if (!$('#hidden_group_input').length) {
                    $('<input>', {
                         type: 'hidden',
                         id: 'hidden_group_input',
                         name: 'student_academic_group',
                         value: 'General'
                    }).appendTo($groupSection);
               }
          } else {
               $('#hidden_group_input').remove();
          }
     }

     function loadInstitutions() {
          const classNumeral = getClassNumeral();
          const institutionType = classNumeral >= 11 ? 'college' : 'school';

          showInstitutionLoading();

          $.ajax({
               url: `/institutions/by-type/${institutionType}`,
               method: 'GET',
               success: function (response) {
                    if (response?.success && response.data?.length) {
                         renderInstitutions(response.data);
                    } else {
                         showInstitutionMessage(response?.message || `No ${institutionType} institutions found`);
                    }
               },
               error: function (xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Error loading institutions';
                    showInstitutionMessage(errorMsg);
               }
          });
     }

     function renderInstitutions(institutions) {
          const $select = $('#institution_select');
          $select.empty().append('<option></option>');

          if (institutions.length === 0) {
               showInstitutionMessage('No institutions available');
               return;
          }

          institutions.forEach(institution => {
               $select.append(
                    $('<option></option>')
                         .val(institution.id)
                         .text(`${institution.name} (EIIN: ${institution.eiin_number || 'N/A'})`)
               );
          });

          $select.prop('disabled', false)
               .trigger('change')
               .parent().find('.select2-selection').removeClass('disabled');
     }

     function loadSubjects() {
          const classId = $classSelect.val();
          const studentId = $('#student_id_input').val();

          if (!classId) {
               showMessage('Please select a class first');
               return;
          }

          if (!studentId) {
               showMessage('Student ID is missing');
               return;
          }

          const classNumeral = getClassNumeral();
          const academicGroup = getAcademicGroup(classNumeral);
          const includeGeneral = classNumeral >= 9;

          showLoading();

          $.ajax({
               url: '/get-taken-subjects',
               method: 'GET',
               data: {
                    class_id: classId,
                    student_id: studentId,
                    group: academicGroup,
                    include_general: includeGeneral ? 1 : 0
               },
               success: function (response) {
                    if (response.success && response.subjects?.length) {
                         renderSubjects(response.subjects, response.taken_subjects, academicGroup);
                    } else {
                         showMessage('No subjects found');
                    }
               },
               error: function (xhr) {
                    console.error('Error:', xhr.responseJSON);
                    showMessage('Error loading subjects');
               }
          });
     }

     function getClassNumeral() {
          return parseInt($classSelect.find(':selected').data('class-numeral')) || 0;
     }

     function getAcademicGroup(classNumeral) {
          return classNumeral <= 8 ? 'General' : $('[name="student_academic_group"]:checked').val() || 'General';
     }

     function renderSubjects(subjects, takenSubjects, currentGroup) {
          const generalSubjects = subjects.filter(s => s.academic_group === 'General');
          const groupSubjects = subjects.filter(s => s.academic_group !== 'General');

          let html = '';

          if (generalSubjects.length) {
               html += createSubjectSection('Compulsory', generalSubjects, takenSubjects);
          }

          if (groupSubjects.length) {
               html += createSubjectSection(`${currentGroup} Group`, groupSubjects, takenSubjects);
          }

          $subjectContainer.html(html || '<div class="alert alert-info">No subjects available</div>');
          updateSelectAllState();
     }

     function createSubjectSection(title, subjects, takenSubjects) {
          return `
            <div class="subject-section">
                <label class="form-label">${title}</label>
                <div class="row">
                    ${subjects.map(subject => `
                        <div class="col-md-3 mb-3">
                            <div class="form-check">
                                <input class="form-check-input subject-checkbox" type="checkbox"
                                       name="subjects[${subject.id}]" value="${subject.id}"
                                       id="sub_${subject.id}" ${takenSubjects.includes(subject.id) ? 'checked' : ''}>
                                <label class="form-check-label fs-6" for="sub_${subject.id}">
                                    ${subject.name}
                                </label>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
     }

     function toggleSelectAll() {
          $('.subject-checkbox').prop('checked', $(this).prop('checked'));
     }

     function updateSelectAllState() {
          const $checkboxes = $('.subject-checkbox');
          const checkedCount = $checkboxes.filter(':checked').length;
          $('#select_all_subjects')
               .prop('checked', checkedCount === $checkboxes.length && $checkboxes.length > 0)
               .prop('indeterminate', checkedCount > 0 && checkedCount < $checkboxes.length);
     }

     function showLoading() {
          $subjectContainer.html('<div class="text-center py-4"><div class="spinner-border"></div></div>');
     }

     function showMessage(msg) {
          $subjectContainer.html(`<div class="alert alert-info">${msg}</div>`);
     }

     function showInstitutionLoading() {
          $institutionSelect.prop('disabled', true);
          $institutionSelect.parent().find('.select2-selection').addClass('disabled');
     }

     function showInstitutionMessage(msg) {
          $institutionSelect.empty().append(`<option value="">${msg}</option>`);
          $institutionSelect.prop('disabled', false);
          $institutionSelect.parent().find('.select2-selection').removeClass('disabled');
     }
});