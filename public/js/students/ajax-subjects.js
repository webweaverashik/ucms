$(function () {
     var $classSelect = $('#student_class_input');
     var $groupSection = $('#student-group-selection');
     var $subjectContainer = $('#subject_list');
     var $institutionSelect = $('#institution_select');

     // Store current selection mode
     var currentSelectionMode = null;

     $classSelect.select2();
     $institutionSelect.select2();

     initEventHandlers();
     initPage();

     function initEventHandlers() {
          $classSelect.on('change', handleClassChange);
          $(document).on('change', '[name="student_academic_group"]', loadSubjects);
          $(document).on('change', '#select_all_compulsory', toggleSelectAllCompulsory);
          $(document).on('change', '.subject-checkbox-compulsory', updateSelectAllCompulsoryState);
          $(document).on('change', '.optional-main-radio', handleMainOptionalSelection);
          $(document).on('change', '.optional-4th-radio', handle4thOptionalSelection);
     }

     function initPage() {
          updateGroupVisibility();
          if ($classSelect.val()) {
               loadSubjects();
               loadInstitutions();
          }
     }

     function handleClassChange() {
          updateGroupVisibility();
          loadSubjects();
          loadInstitutions();
     }

     function updateGroupVisibility() {
          var classNumeral = getClassNumeral();
          var isJuniorClass = classNumeral >= 1 && classNumeral <= 8;

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
          var classNumeral = getClassNumeral();
          var institutionType = classNumeral >= 11 ? 'college' : 'school';

          showInstitutionLoading();

          $.ajax({
               url: '/institutions/by-type/' + institutionType,
               method: 'GET',
               success: function (response) {
                    if (response && response.success && response.data && response.data.length) {
                         renderInstitutions(response.data);
                    } else {
                         showInstitutionMessage(response.message || 'No ' + institutionType + ' institutions found');
                    }
               },
               error: function (xhr) {
                    var errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Error loading institutions';
                    showInstitutionMessage(errorMsg);
               }
          });
     }

     function renderInstitutions(institutions) {
          var $select = $('#institution_select');
          $select.empty().append('<option></option>');

          if (institutions.length === 0) {
               showInstitutionMessage('No institutions available');
               return;
          }

          institutions.forEach(function (institution) {
               $select.append(
                    $('<option></option>')
                         .val(institution.id)
                         .text(institution.name + ' (EIIN: ' + (institution.eiin_number || 'N/A') + ')')
               );
          });

          $select.prop('disabled', false).trigger('change');
     }

     function loadSubjects() {
          var classId = $classSelect.val();
          if (!classId) {
               showMessage('Please select a class first');
               return;
          }

          var classNumeral = getClassNumeral();
          var academicGroup = getAcademicGroup(classNumeral);
          var includeGeneral = classNumeral >= 9;

          showLoading();

          $.ajax({
               url: '/get-subjects',
               method: 'GET',
               data: {
                    class_id: classId,
                    group: academicGroup,
                    include_general: includeGeneral ? 1 : 0
               },
               success: function (response) {
                    if (response && response.success) {
                         currentSelectionMode = response.selection_mode;
                         renderSubjects(response.subjects, response.group, response.has_optional, response.selection_mode);
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
          return classNumeral <= 8 ? 'General' : $('[name="student_academic_group"]:checked').val() || 'Science';
     }

     function renderSubjects(subjects, currentGroup, hasOptional, selectionMode) {
          var html = '';

          var hasCompulsory = (subjects.general_compulsory && subjects.general_compulsory.length) ||
               (subjects.group_compulsory && subjects.group_compulsory.length);

          if (hasCompulsory) {
               html += '<div class="form-check mb-4">' +
                    '<input class="form-check-input" type="checkbox" id="select_all_compulsory" checked>' +
                    '<label class="form-check-label fw-bold fs-6" for="select_all_compulsory">' +
                    'Select All Compulsory Subjects</label></div>';
          }

          if (subjects.general_compulsory && subjects.general_compulsory.length) {
               html += createCompulsorySection('General (Compulsory for All)', subjects.general_compulsory, 'primary');
          }

          if (subjects.group_compulsory && subjects.group_compulsory.length) {
               html += createCompulsorySection(currentGroup + ' Group - Main Subjects (Compulsory)', subjects.group_compulsory, 'info');
          }

          if (subjects.group_optional && subjects.group_optional.length) {
               html += createOptionalSection(currentGroup + ' Group - Optional Subjects', subjects.group_optional, selectionMode);
          }

          $subjectContainer.html(html || '<div class="alert alert-info">No subjects available</div>');
          updateSelectAllCompulsoryState();
     }

     function createCompulsorySection(title, subjects, colorClass) {
          var subjectsHtml = '';
          subjects.forEach(function (subject) {
               subjectsHtml += '<div class="col-md-3 mb-3">' +
                    '<div class="form-check form-check-custom form-check-solid">' +
                    '<input class="form-check-input subject-checkbox-compulsory" type="checkbox" ' +
                    'name="subjects[' + subject.id + '][id]" value="' + subject.id + '" ' +
                    'id="sub_' + subject.id + '" checked>' +
                    '<input type="hidden" name="subjects[' + subject.id + '][is_4th]" value="0">' +
                    '<label class="form-check-label fs-6" for="sub_' + subject.id + '">' +
                    subject.name + '</label></div></div>';
          });

          return '<div class="subject-section mb-6 p-4 border border-dashed border-' + colorClass + ' rounded">' +
               '<label class="form-label fw-bold text-' + colorClass + ' fs-5 mb-4">' +
               '<i class="ki-outline ki-book-open fs-4 me-2"></i>' + title + '</label>' +
               '<div class="row">' + subjectsHtml + '</div></div>';
     }

     function createOptionalSection(title, subjects, selectionMode) {
          var requiresMain = selectionMode && selectionMode.requires_main;
          var requires4th = selectionMode && selectionMode.requires_4th;
          var instruction = selectionMode ? selectionMode.instruction : 'Select optional subjects';
          var selectionType = selectionMode ? selectionMode.type : 'none';

          var subjectsRows = '';
          subjects.forEach(function (subject) {
               subjectsRows += '<tr data-subject-id="' + subject.id + '">';
               subjectsRows += '<td><span class="text-gray-800 fw-semibold fs-6">' + subject.name + '</span></td>';

               // Main Subject column (only if requires_main is true)
               if (requiresMain) {
                    subjectsRows += '<td class="text-center">' +
                         '<div class="form-check form-check-custom form-check-solid justify-content-center">' +
                         '<input class="form-check-input optional-main-radio" type="radio" ' +
                         'name="optional_main_subject" value="' + subject.id + '" ' +
                         'data-subject-id="' + subject.id + '" data-subject-name="' + subject.name + '" ' +
                         'id="main_' + subject.id + '"></div></td>';
               }

               // 4th Subject column
               if (requires4th) {
                    subjectsRows += '<td class="text-center">' +
                         '<div class="form-check form-check-custom form-check-solid justify-content-center">' +
                         '<input class="form-check-input optional-4th-radio" type="radio" ' +
                         'name="optional_4th_subject" value="' + subject.id + '" ' +
                         'data-subject-id="' + subject.id + '" data-subject-name="' + subject.name + '" ' +
                         'id="fourth_' + subject.id + '"></div></td>';
               }

               subjectsRows += '</tr>';
          });

          // Build table headers
          var tableHeaders = '<th class="ps-4 min-w-200px rounded-start">Subject Name</th>';
          if (requiresMain) {
               tableHeaders += '<th class="w-120px text-center">Main Subject</th>';
          }
          if (requires4th) {
               var roundedClass = !requiresMain ? ' rounded-end' : '';
               tableHeaders += '<th class="w-120px text-center' + roundedClass + '">4th Subject</th>';
          }

          // Build summary display
          var summaryContent = '<span class="fw-semibold">Selected: </span>';
          if (requiresMain) {
               summaryContent += '<span id="main_subject_display" class="badge badge-light-primary me-2"></span>';
          }
          summaryContent += '<span id="fourth_subject_display" class="badge badge-light-warning"></span>';

          return '<div class="subject-section mb-6 p-4 border border-dashed border-warning rounded">' +
               '<label class="form-label fw-bold text-warning fs-5 mb-3">' +
               '<i class="ki-outline ki-abstract-26 fs-4 me-2"></i>' + title + '</label>' +
               '<div class="alert alert-warning d-flex align-items-center py-3 mb-4">' +
               '<i class="ki-outline ki-information-5 fs-2 text-warning me-3"></i>' +
               '<div class="d-flex flex-column"><span class="fw-semibold">Selection Required:</span>' +
               '<span>' + instruction + '</span></div></div>' +
               '<div class="table-responsive"><table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">' +
               '<thead><tr class="fw-bold text-muted bg-light">' + tableHeaders + '</tr></thead>' +
               '<tbody>' + subjectsRows + '</tbody></table></div>' +
               '<div class="mt-4 p-3 bg-light-primary rounded" id="optional_selection_summary" style="display: none;">' +
               '<div class="d-flex align-items-center">' +
               '<i class="ki-outline ki-check-circle fs-2 text-success me-3"></i>' +
               '<div>' + summaryContent + '</div></div></div></div>';
     }

     function handleMainOptionalSelection() {
          var mainSelected = $(this).val();
          var fourthSelected = $('input[name="optional_4th_subject"]:checked').val();

          if (mainSelected && fourthSelected && mainSelected === fourthSelected) {
               $('input[name="optional_4th_subject"]:checked').prop('checked', false);
               toastr.info('4th Subject has been cleared as it was same as Main Subject');
          }

          updateOptionalSummary();
     }

     function handle4thOptionalSelection() {
          var fourthSelected = $(this).val();
          var mainSelected = $('input[name="optional_main_subject"]:checked').val();

          // Only check conflict if main selection is required and selected
          if (currentSelectionMode && currentSelectionMode.requires_main) {
               if (mainSelected && fourthSelected && mainSelected === fourthSelected) {
                    $(this).prop('checked', false);
                    toastr.warning('You cannot select the same subject for both Main and 4th Subject');
                    return false;
               }
          }

          updateOptionalSummary();
     }

     function updateOptionalSummary() {
          var $mainRadio = $('input[name="optional_main_subject"]:checked');
          var $fourthRadio = $('input[name="optional_4th_subject"]:checked');

          var mainName = $mainRadio.length ? $mainRadio.data('subject-name') : '';
          var fourthName = $fourthRadio.length ? $fourthRadio.data('subject-name') : '';

          var hasSelection = mainName || fourthName;

          if (hasSelection) {
               $('#optional_selection_summary').show();

               if ($('#main_subject_display').length) {
                    $('#main_subject_display').text(mainName ? 'Main: ' + mainName : 'Main: Not selected');
               }

               $('#fourth_subject_display').text(fourthName ? '4th: ' + fourthName : '4th: Not selected');
          } else {
               $('#optional_selection_summary').hide();
          }
     }

     function toggleSelectAllCompulsory() {
          var isChecked = $(this).prop('checked');
          $('.subject-checkbox-compulsory').prop('checked', isChecked);
     }

     function updateSelectAllCompulsoryState() {
          var $checkboxes = $('.subject-checkbox-compulsory');
          if ($checkboxes.length === 0) return;

          var checkedCount = $checkboxes.filter(':checked').length;
          var $selectAll = $('#select_all_compulsory');

          $selectAll
               .prop('checked', checkedCount === $checkboxes.length)
               .prop('indeterminate', checkedCount > 0 && checkedCount < $checkboxes.length);
     }

     function showLoading() {
          $subjectContainer.html('<div class="text-center py-10">' +
               '<div class="spinner-border text-primary" role="status">' +
               '<span class="visually-hidden">Loading...</span></div>' +
               '<p class="text-muted mt-3">Loading subjects...</p></div>');
     }

     function showMessage(msg) {
          $subjectContainer.html('<div class="alert alert-info d-flex align-items-center">' +
               '<i class="ki-outline ki-information-5 fs-2 me-3"></i>' + msg + '</div>');
     }

     function showInstitutionLoading() {
          $institutionSelect.prop('disabled', true);
     }

     function showInstitutionMessage(msg) {
          $institutionSelect.empty().append('<option value="">' + msg + '</option>');
          $institutionSelect.prop('disabled', false);
     }

     // Expose validation function globally
     window.validateOptionalSubjects = function () {
          var hasOptionalSection = $('.optional-4th-radio').length > 0;

          if (!hasOptionalSection) {
               return { valid: true };
          }

          if (!currentSelectionMode) {
               return { valid: true };
          }

          var requiresMain = currentSelectionMode.requires_main;
          var requires4th = currentSelectionMode.requires_4th;

          var mainSelected = $('input[name="optional_main_subject"]:checked').val();
          var fourthSelected = $('input[name="optional_4th_subject"]:checked').val();

          // Validate based on selection mode
          if (requiresMain && !mainSelected) {
               return { valid: false, message: 'Please select a Main Optional Subject' };
          }

          if (requires4th && !fourthSelected) {
               return { valid: false, message: 'Please select a 4th Subject' };
          }

          // Check conflict only if main is required
          if (requiresMain && mainSelected && fourthSelected && mainSelected === fourthSelected) {
               return { valid: false, message: 'Main and 4th Subject cannot be the same' };
          }

          return { valid: true };
     };
});