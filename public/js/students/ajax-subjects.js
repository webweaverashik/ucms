$(function () {
     // DOM elements
     const $classSelect = $('#student_class_input');
     const $groupSection = $('#student-group-selection');
     const $subjectContainer = $('#subject_list');

     // Initialize
     $classSelect.select2();
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
               loadSubjects();
          }
     }

     function handleClassChange() {
          updateGroupVisibility();
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

     function loadSubjects() {
          const classId = $classSelect.val();
          if (!classId) {
               showMessage('Please select a class first');
               return;
          }

          const classNumeral = getClassNumeral();
          const academicGroup = getAcademicGroup(classNumeral);
          const includeGeneral = classNumeral >= 9;

          showLoading();

          $.ajax({
               url: '/get-subjects',
               method: 'GET',
               data: {
                    class_id: classId,
                    group: academicGroup,  // Changed from academic_group to group
                    include_general: includeGeneral ? 1 : 0  // Send as 1/0
               },
               success: function (response) {
                    if (response?.subjects?.length) {
                         renderSubjects(response.subjects, academicGroup);
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

     // Helper functions
     function getClassNumeral() {
          return parseInt($classSelect.find(':selected').data('class-numeral')) || 0;
     }

     function getAcademicGroup(classNumeral) {
          return classNumeral <= 8 ? 'General' : $('[name="student_academic_group"]:checked').val() || 'General';
     }

     function renderSubjects(subjects, currentGroup) {
          const generalSubjects = subjects.filter(s => s.academic_group === 'General');
          const groupSubjects = subjects.filter(s => s.academic_group !== 'General');

          let html = `
             
         `;

          if (generalSubjects.length) {
               html += createSubjectSection('Common Subjects', generalSubjects);
          }

          if (groupSubjects.length) {
               html += createSubjectSection(`${currentGroup} Subjects`, groupSubjects);
          }

          $subjectContainer.html(html || '<div class="alert alert-info">No subjects available</div>');
          updateSelectAllState();
     }

     function createSubjectSection(title, subjects) {
          return `
             <div class="subject-section">
                 <h6>${title}</h6>
                 <div class="row">
                     ${subjects.map(subject => `
                         <div class="col-md-3 mb-3">
                             <div class="form-check">
                                 <input class="form-check-input subject-checkbox" type="checkbox"
                                        name="subjects[]" value="${subject.id}" id="sub_${subject.id}">
                                 <label class="form-check-label" for="sub_${subject.id}">
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
});