"use strict";

// Class definition - Edit Student Module
var KTUpdateStudent = (function () {
     // ============================================
     // PRIVATE VARIABLES
     // ============================================

     // Stepper elements
     var stepper;
     var stepperObj;
     var form;
     var formSubmitButton;
     var formContinueButton;

     // Subject/Class related elements
     var classSelect;
     var institutionSelect;
     var groupSection;
     var subjectContainer;

     // Store initial institution ID for preselection
     var initialInstitutionId = null;

     // Current selection mode
     var currentSelectionMode = null;

     // Validation array
     var validations = [];

     // ============================================
     // STEPPER FUNCTIONS
     // ============================================

     var initStepper = function () {
          stepperObj = new KTStepper(stepper);

          stepperObj.on('kt.stepper.changed', function (stepper) {
               var currentStep = stepperObj.getCurrentStepIndex();

               if (currentStep === 4) {
                    formSubmitButton.classList.remove('d-none');
                    formSubmitButton.classList.add('d-inline-block');
                    formContinueButton.classList.add('d-none');
               } else if (currentStep === 5) {
                    formSubmitButton.classList.add('d-none');
                    formContinueButton.classList.add('d-none');
               } else {
                    formSubmitButton.classList.add('d-none');
                    formSubmitButton.classList.remove('d-inline-block');
                    formContinueButton.classList.remove('d-none');
               }

               var contents = document.querySelectorAll('[data-kt-stepper-element="content"]');
               contents.forEach(function (content, index) {
                    content.classList.toggle('d-none', index !== (currentStep - 1));
                    if (index === (currentStep - 1)) {
                         content.classList.add('current');
                    } else {
                         content.classList.remove('current');
                    }
               });
          });

          stepperObj.on('kt.stepper.next', function (stepper) {
               var currentStep = stepper.getCurrentStepIndex();

               // Step 3 validation - Subjects
               if (currentStep === 3) {
                    // Validate subject selection (at least one from any section)
                    var subjectValidation = validateSubjectSelection();
                    if (!subjectValidation.valid) {
                         toastr.error(subjectValidation.message);
                         return;
                    }
               }

               var validator = validations[currentStep - 1];
               if (validator) {
                    validator.validate().then(function (status) {
                         if (status === 'Valid') {
                              stepper.goNext();
                              KTUtil.scrollTop();
                         } else {
                              toastr.options.progressBar = true;
                              toastr.warning('Please fill up the required fields.');
                              KTUtil.scrollTop();
                         }
                    });
               } else {
                    stepper.goNext();
                    KTUtil.scrollTop();
               }
          });

          stepperObj.on('kt.stepper.previous', function (stepper) {
               stepper.goPrevious();
               KTUtil.scrollTop();
          });
     };

     // ============================================
     // SUBJECT MODULE
     // ============================================

     var initSubjectModule = function () {
          classSelect = $('#student_class_input');
          institutionSelect = $('#institution_select');
          groupSection = $('#student-group-selection');
          subjectContainer = $('#subject_list');

          if (classSelect.length === 0) return;

          // Store the initial institution ID before any AJAX calls
          initialInstitutionId = institutionSelect.val();

          classSelect.select2();
          institutionSelect.select2();

          initSubjectEventHandlers();
          initSubjectPage();
     };

     var initSubjectEventHandlers = function () {
          classSelect.on('change', handleClassChange);
          $(document).on('change', '[name="student_academic_group"]', loadSubjects);
          $(document).on('change', '#select_all_compulsory', toggleSelectAllCompulsory);
          $(document).on('change', '.subject-checkbox-compulsory', updateSelectAllCompulsoryState);
          $(document).on('change', '.optional-main-radio', handleMainOptionalSelection);
          $(document).on('change', '.optional-4th-radio', handle4thOptionalSelection);

          // Click to deselect radio buttons
          $(document).on('click', '.optional-main-radio', handleRadioClick);
          $(document).on('click', '.optional-4th-radio', handleRadioClick);

          // Clear all optional selections button
          $(document).on('click', '.clear-optional-selections', clearAllOptionalSelections);
     };

     // Track last selected values for click-to-deselect
     var lastMainValue = null;
     var lastFourthValue = null;

     var handleRadioClick = function (e) {
          var $radio = $(this);
          var name = $radio.attr('name');
          var value = $radio.val();

          if (name === 'main_optional_subject') {
               if (lastMainValue === value) {
                    // Same radio clicked again - deselect it
                    e.preventDefault();
                    $radio.prop('checked', false);
                    lastMainValue = null;
                    updateOptionalSummary();
                    return false;
               }
               lastMainValue = value;
          } else if (name === 'fourth_subject') {
               if (lastFourthValue === value) {
                    // Same radio clicked again - deselect it
                    e.preventDefault();
                    $radio.prop('checked', false);
                    lastFourthValue = null;
                    updateOptionalSummary();
                    return false;
               }
               lastFourthValue = value;
          }
     };

     var clearAllOptionalSelections = function (e) {
          e.preventDefault();
          $('input[name="main_optional_subject"]').prop('checked', false);
          $('input[name="fourth_subject"]').prop('checked', false);
          lastMainValue = null;
          lastFourthValue = null;
          updateOptionalSummary();
          toastr.info('Optional subject selections cleared');
     };

     var initSubjectPage = function () {
          updateGroupVisibility();
          if (classSelect.val()) {
               loadSubjects();
               // Don't load institutions on initial page load - they're already rendered by Blade
               // Only load them when the class changes
          }
     };

     var handleClassChange = function () {
          updateGroupVisibility();
          loadSubjects();
          // When class changes, reset the initial institution ID since user is changing class
          // and should select a new institution from the filtered list
          initialInstitutionId = null;
          loadInstitutions();
     };

     var updateGroupVisibility = function () {
          var classNumeral = getClassNumeral();
          var isJuniorClass = classNumeral >= 1 && classNumeral <= 8;

          groupSection.toggle(!isJuniorClass);
          $('[name="student_academic_group"]').prop('disabled', isJuniorClass);

          if (isJuniorClass) {
               if (!$('#hidden_group_input').length) {
                    $('<input>', {
                         type: 'hidden',
                         id: 'hidden_group_input',
                         name: 'student_academic_group',
                         value: 'General'
                    }).appendTo(groupSection);
               }
          } else {
               $('#hidden_group_input').remove();
          }
     };

     var loadInstitutions = function () {
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
     };

     var renderInstitutions = function (institutions) {
          institutionSelect.empty().append('<option></option>');

          if (institutions.length === 0) {
               showInstitutionMessage('No institutions available');
               return;
          }

          institutions.forEach(function (institution) {
               var selected = (institution.id == initialInstitutionId) ? 'selected' : '';
               institutionSelect.append(
                    '<option value="' + institution.id + '" ' + selected + '>' +
                    institution.name + ' (EIIN: ' + (institution.eiin_number || 'N/A') + ')' +
                    '</option>'
               );
          });

          institutionSelect.prop('disabled', false).trigger('change');
     };

     var loadSubjects = function () {
          var classId = classSelect.val();
          var studentId = $('#student_id_input').val();

          if (!classId) {
               showSubjectMessage('Please select a class first');
               return;
          }

          var classNumeral = getClassNumeral();
          var academicGroup = getAcademicGroup(classNumeral);
          var includeGeneral = classNumeral >= 9;

          showSubjectLoading();

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
                    if (response && response.success) {
                         currentSelectionMode = response.selection_mode;
                         renderSubjects(
                              response.subjects,
                              response.group,
                              response.has_optional,
                              response.selection_mode,
                              response.taken_subjects || {},
                              response.main_optional_id,
                              response.fourth_subject_id
                         );
                    } else {
                         showSubjectMessage('No subjects found');
                    }
               },
               error: function (xhr) {
                    console.error('Error:', xhr.responseJSON);
                    showSubjectMessage('Error loading subjects');
               }
          });
     };

     var getClassNumeral = function () {
          return parseInt(classSelect.find(':selected').data('class-numeral')) || 0;
     };

     var getAcademicGroup = function (classNumeral) {
          return classNumeral <= 8 ? 'General' : $('[name="student_academic_group"]:checked').val() || 'Science';
     };

     var renderSubjects = function (subjects, currentGroup, hasOptional, selectionMode, takenSubjects, mainOptionalId, fourthSubjectId) {
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
               html += createCompulsorySection(
                    'General (Compulsory for All)',
                    subjects.general_compulsory,
                    'primary',
                    takenSubjects
               );
          }

          if (subjects.group_compulsory && subjects.group_compulsory.length) {
               html += createCompulsorySection(
                    currentGroup + ' Group - Main Subjects (Compulsory)',
                    subjects.group_compulsory,
                    'info',
                    takenSubjects
               );
          }

          if (subjects.group_optional && subjects.group_optional.length) {
               html += createOptionalSection(
                    currentGroup + ' Group - Optional Subjects',
                    subjects.group_optional,
                    selectionMode,
                    mainOptionalId,
                    fourthSubjectId
               );
          }

          subjectContainer.html(html || '<div class="alert alert-info">No subjects available</div>');
          updateSelectAllCompulsoryState();

          // Initialize last selected values for click-to-deselect feature
          lastMainValue = mainOptionalId ? String(mainOptionalId) : null;
          lastFourthValue = fourthSubjectId ? String(fourthSubjectId) : null;

          // Update the summary display
          updateOptionalSummary();
     };

     var createCompulsorySection = function (title, subjects, colorClass, takenSubjects) {
          var subjectsHtml = '';

          subjects.forEach(function (subject) {
               // Check if this subject is taken by the student
               var isTaken = takenSubjects && takenSubjects.hasOwnProperty(subject.id);
               var checkedAttr = isTaken ? 'checked' : '';

               subjectsHtml += '<div class="col-md-3 mb-3">' +
                    '<div class="form-check form-check-custom form-check-solid">' +
                    '<input class="form-check-input subject-checkbox-compulsory" type="checkbox" ' +
                    'data-subject-id="' + subject.id + '" ' +
                    'id="sub_' + subject.id + '" ' + checkedAttr + '>' +
                    '<label class="form-check-label fs-6" for="sub_' + subject.id + '">' +
                    subject.name + '</label></div></div>';
          });

          return '<div class="subject-section mb-6 p-4 border border-dashed border-' + colorClass + ' rounded">' +
               '<label class="form-label fw-bold text-' + colorClass + ' fs-5 mb-4">' +
               '<i class="ki-outline ki-book-open fs-4 me-2"></i>' + title + '</label>' +
               '<div class="row">' + subjectsHtml + '</div></div>';
     };

     var createOptionalSection = function (title, subjects, selectionMode, mainOptionalId, fourthSubjectId) {
          var requiresMain = selectionMode && selectionMode.requires_main;
          var requires4th = selectionMode && selectionMode.requires_4th;
          var instruction = selectionMode ? selectionMode.instruction : 'Select optional subjects';

          // Make instruction more clear that it's optional
          var optionalNote = ' (Optional - skip if not needed)';

          var subjectsRows = '';
          subjects.forEach(function (subject) {
               var mainChecked = (subject.id == mainOptionalId) ? 'checked' : '';
               var fourthChecked = (subject.id == fourthSubjectId) ? 'checked' : '';

               subjectsRows += '<tr data-subject-id="' + subject.id + '">';
               subjectsRows += '<td><span class="text-gray-800 fw-semibold fs-6">' + subject.name + '</span></td>';

               if (requiresMain) {
                    subjectsRows += '<td class="text-center">' +
                         '<div class="form-check form-check-custom form-check-solid justify-content-center">' +
                         '<input class="form-check-input optional-main-radio" type="radio" ' +
                         'name="main_optional_subject" value="' + subject.id + '" ' +
                         'data-subject-id="' + subject.id + '" data-subject-name="' + subject.name + '" ' +
                         'id="main_' + subject.id + '" ' + mainChecked + '></div></td>';
               }

               if (requires4th) {
                    subjectsRows += '<td class="text-center">' +
                         '<div class="form-check form-check-custom form-check-solid justify-content-center">' +
                         '<input class="form-check-input optional-4th-radio" type="radio" ' +
                         'name="fourth_subject" value="' + subject.id + '" ' +
                         'data-subject-id="' + subject.id + '" data-subject-name="' + subject.name + '" ' +
                         'id="fourth_' + subject.id + '" ' + fourthChecked + '></div></td>';
               }

               subjectsRows += '</tr>';
          });

          var tableHeaders = '<th class="ps-4 min-w-200px rounded-start">Subject Name</th>';
          if (requiresMain) {
               tableHeaders += '<th class="min-w-100px text-center">Main Subject</th>';
          }
          if (requires4th) {
               var roundedClass = !requiresMain ? ' rounded-end' : '';
               tableHeaders += '<th class="min-w-100px text-center' + roundedClass + '">4th Subject</th>';
          }

          var summaryContent = '<span class="fw-semibold">Selected: </span>';
          if (requiresMain) {
               var mainName = mainOptionalId ? $('[data-subject-id="' + mainOptionalId + '"]').data('subject-name') || '' : '';
               summaryContent += '<span id="main_subject_display" class="badge badge-light-primary me-2">' +
                    (mainName ? 'Main: ' + mainName : '') + '</span>';
          }
          var fourthName = fourthSubjectId ? $('[data-subject-id="' + fourthSubjectId + '"]').data('subject-name') || '' : '';
          summaryContent += '<span id="fourth_subject_display" class="badge badge-light-warning">' +
               (fourthName ? '4th: ' + fourthName : '') + '</span>';

          var showSummary = mainOptionalId || fourthSubjectId ? '' : 'style="display: none;"';

          return '<div class="subject-section mb-6 p-4 border border-dashed border-warning rounded">' +
               '<div class="d-flex justify-content-between align-items-center mb-3">' +
               '<label class="form-label fw-bold text-warning fs-5 mb-0">' +
               '<i class="ki-outline ki-abstract-26 fs-4 me-2"></i>' + title + '</label>' +
               '<button type="button" class="btn btn-sm btn-icon btn-light-danger clear-optional-selections" ' +
               'data-bs-toggle="tooltip" data-bs-placement="top" title="Clear selections">' +
               '<i class="ki-outline ki-cross fs-2"></i></button>' +
               '</div>' +
               '<div class="alert alert-info d-flex align-items-center py-3 mb-4">' +
               '<i class="ki-outline ki-information-5 fs-2 text-info me-3"></i>' +
               '<div class="d-flex flex-column"><span class="fw-semibold">Optional Selection:</span>' +
               '<span>' + instruction + optionalNote + '</span></div></div>' +
               '<div class="table-responsive"><table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">' +
               '<thead><tr class="fw-bold text-muted bg-light">' + tableHeaders + '</tr></thead>' +
               '<tbody>' + subjectsRows + '</tbody></table></div>' +
               '<div class="mt-4 p-3 bg-light-primary rounded" id="optional_selection_summary" ' + showSummary + '>' +
               '<div class="d-flex align-items-center">' +
               '<i class="ki-outline ki-check-circle fs-2 text-success me-3"></i>' +
               '<div>' + summaryContent + '</div></div></div></div>';
     };

     var handleMainOptionalSelection = function () {
          var mainSelected = $(this).val();
          var fourthSelected = $('input[name="fourth_subject"]:checked').val();

          if (mainSelected && fourthSelected && mainSelected === fourthSelected) {
               $('input[name="fourth_subject"]:checked').prop('checked', false);
               toastr.info('4th Subject has been cleared as it was same as Main Subject');
          }

          updateOptionalSummary();
     };

     var handle4thOptionalSelection = function () {
          var fourthSelected = $(this).val();
          var mainSelected = $('input[name="main_optional_subject"]:checked').val();

          if (currentSelectionMode && currentSelectionMode.requires_main) {
               if (mainSelected && fourthSelected && mainSelected === fourthSelected) {
                    $(this).prop('checked', false);
                    toastr.warning('You cannot select the same subject for both Main and 4th Subject');
                    return false;
               }
          }

          updateOptionalSummary();
     };

     var updateOptionalSummary = function () {
          var $mainRadio = $('input[name="main_optional_subject"]:checked');
          var $fourthRadio = $('input[name="fourth_subject"]:checked');

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
     };

     var toggleSelectAllCompulsory = function () {
          var isChecked = $(this).prop('checked');
          $('.subject-checkbox-compulsory').prop('checked', isChecked);
     };

     var updateSelectAllCompulsoryState = function () {
          var $checkboxes = $('.subject-checkbox-compulsory');
          if ($checkboxes.length === 0) return;

          var checkedCount = $checkboxes.filter(':checked').length;
          var $selectAll = $('#select_all_compulsory');

          $selectAll
               .prop('checked', checkedCount === $checkboxes.length)
               .prop('indeterminate', checkedCount > 0 && checkedCount < $checkboxes.length);
     };

     var showSubjectLoading = function () {
          subjectContainer.html(
               '<div class="text-center py-10">' +
               '<div class="spinner-border text-primary" role="status">' +
               '<span class="visually-hidden">Loading...</span></div>' +
               '<p class="text-muted mt-3">Loading subjects...</p></div>'
          );
     };

     var showSubjectMessage = function (msg) {
          subjectContainer.html(
               '<div class="alert alert-info d-flex align-items-center">' +
               '<i class="ki-outline ki-information-5 fs-2 me-3"></i>' + msg + '</div>'
          );
     };

     var showInstitutionLoading = function () {
          institutionSelect.prop('disabled', true);
     };

     var showInstitutionMessage = function (msg) {
          institutionSelect.empty().append('<option value="">' + msg + '</option>');
          institutionSelect.prop('disabled', false);
     };

     var validateSubjectSelection = function () {
          // Count subjects from all sections
          var compulsoryChecked = $('.subject-checkbox-compulsory:checked').length;
          var mainOptionalSelected = $('input[name="main_optional_subject"]:checked').val();
          var fourthSubjectSelected = $('input[name="fourth_subject"]:checked').val();

          // Calculate total selected subjects
          var totalSelected = compulsoryChecked;
          if (mainOptionalSelected) totalSelected++;
          if (fourthSubjectSelected) totalSelected++;

          // At least one subject must be selected from any section
          if (totalSelected === 0) {
               return {
                    valid: false,
                    message: 'Please select at least one subject from any section (Compulsory or Optional)'
               };
          }

          // Now validate optional subject combinations if any are selected
          var optionalValidation = validateOptionalSubjects();
          if (!optionalValidation.valid) {
               return optionalValidation;
          }

          return { valid: true };
     };

     var validateOptionalSubjects = function () {
          var hasOptionalSection = $('.optional-4th-radio').length > 0;
          if (!hasOptionalSection) {
               return { valid: true };
          }

          if (!currentSelectionMode) {
               return { valid: true };
          }

          var requiresMain = currentSelectionMode.requires_main;
          var mainSelected = $('input[name="main_optional_subject"]:checked').val();
          var fourthSelected = $('input[name="fourth_subject"]:checked').val();

          // Optional subjects are truly optional - no mandatory selection required
          // But if user selects any, validate the combination

          // If main is required and user selected a 4th but not a main, warn them
          if (requiresMain && fourthSelected && !mainSelected) {
               return { valid: false, message: 'Please also select a Main Optional Subject when choosing a 4th Subject' };
          }

          // Prevent same subject selected as both main and 4th
          if (mainSelected && fourthSelected && mainSelected === fourthSelected) {
               return { valid: false, message: 'Main and 4th Subject cannot be the same' };
          }

          return { valid: true };
     };

     // ============================================
     // COLLECT SUBJECTS FOR FORM SUBMISSION
     // ============================================

     var collectSubjectsData = function () {
          var subjects = [];

          // Collect checked compulsory subjects
          $('.subject-checkbox-compulsory:checked').each(function () {
               subjects.push({
                    id: $(this).data('subject-id'),
                    is_4th: false
               });
          });

          // Collect main optional subject
          var mainOptional = $('input[name="main_optional_subject"]:checked').val();
          if (mainOptional) {
               subjects.push({
                    id: parseInt(mainOptional),
                    is_4th: false
               });
          }

          // Collect 4th subject
          var fourthSubject = $('input[name="fourth_subject"]:checked').val();
          if (fourthSubject) {
               subjects.push({
                    id: parseInt(fourthSubject),
                    is_4th: true
               });
          }

          return subjects;
     };

     // ============================================
     // FORM SUBMISSION HANDLER
     // ============================================

     var handleForm = function () {
          formSubmitButton.addEventListener('click', function (e) {
               e.preventDefault();

               var validator = validations[3];

               validator.validate().then(function (status) {
                    if (status === 'Valid') {
                         formSubmitButton.disabled = true;
                         formSubmitButton.setAttribute('data-kt-indicator', 'on');

                         var formElement = document.getElementById('kt_update_student_form');
                         var formData = new FormData(formElement);

                         // Remove any existing subjects entries (from old hidden inputs)
                         var keysToDelete = [];
                         for (var pair of formData.entries()) {
                              if (pair[0].startsWith('subjects[')) {
                                   keysToDelete.push(pair[0]);
                              }
                         }
                         keysToDelete.forEach(function (key) {
                              formData.delete(key);
                         });

                         // Add collected subjects as clean array
                         var subjectsData = collectSubjectsData();
                         subjectsData.forEach(function (subject, index) {
                              formData.append('subjects[' + index + '][id]', subject.id);
                              formData.append('subjects[' + index + '][is_4th]', subject.is_4th ? '1' : '0');
                         });

                         var studentId = document.getElementById('student_id_input').value;
                         var updateUrl = updateStudentRoute(studentId);

                         formData.append('_method', 'PUT');

                         fetch(updateUrl, {
                              method: 'POST',
                              body: formData,
                              headers: {
                                   'X-CSRF-TOKEN': csrfToken,
                                   'Accept': 'application/json'
                              }
                         })
                              .then(function (response) {
                                   if (!response.ok) {
                                        return response.text().then(function (text) {
                                             try {
                                                  var data = JSON.parse(text);
                                                  return Promise.reject(data.errors || data.message || 'Request failed');
                                             } catch (e) {
                                                  return Promise.reject(text || 'Request failed with status ' + response.status);
                                             }
                                        });
                                   }
                                   return response.json();
                              })
                              .then(function (data) {
                                   if (data.success) {
                                        Swal.fire({
                                             text: 'Student details updated successfully!',
                                             icon: 'success',
                                             buttonsStyling: false,
                                             confirmButtonText: 'Ok',
                                             customClass: {
                                                  confirmButton: 'btn btn-primary'
                                             }
                                        });

                                        document.getElementById('admitted_name').innerText = data.student.name;
                                        document.getElementById('admitted_id').innerText = data.student.student_unique_id;

                                        stepperObj.goNext();

                                        setTimeout(function () {
                                             var prevButton = document.querySelector('[data-kt-stepper-action="previous"]');
                                             if (prevButton) {
                                                  prevButton.style.display = 'none';
                                             }
                                        }, 300);
                                   } else {
                                        showErrors(data.errors || ['An unknown error occurred.']);
                                        enablePreviousButton();
                                   }
                              })
                              .catch(function (error) {
                                   var errorMessages = [];

                                   if (typeof error === 'string') {
                                        errorMessages.push(error);
                                   } else if (error && typeof error === 'object') {
                                        if (error.message) {
                                             errorMessages.push(error.message);
                                        }
                                        for (var key in error) {
                                             if (Array.isArray(error[key])) {
                                                  errorMessages = errorMessages.concat(error[key]);
                                             }
                                        }
                                   }

                                   if (errorMessages.length === 0) {
                                        errorMessages.push('Something went wrong!');
                                   }

                                   showErrors(errorMessages);
                                   console.error('Error:', error);
                                   enablePreviousButton();
                              })
                              .finally(function () {
                                   formSubmitButton.removeAttribute('data-kt-indicator');
                                   formSubmitButton.disabled = false;
                              });
                    } else {
                         toastr.options.progressBar = true;
                         toastr.error('Please fill up the required fields.');
                         KTUtil.scrollTop();
                    }
               });
          });
     };

     // ============================================
     // ERROR HANDLING
     // ============================================

     var showErrors = function (errors) {
          var errorContainer = document.getElementById('error-container');
          if (!errorContainer) {
               console.error('Error container not found!');
               return;
          }

          // Clear previous errors
          errorContainer.innerHTML = '';

          // Handle both array and object errors
          var errorList = [];
          if (Array.isArray(errors)) {
               errorList = errors;
          } else if (typeof errors === 'object') {
               for (var key in errors) {
                    if (Array.isArray(errors[key])) {
                         errorList = errorList.concat(errors[key]);
                    } else {
                         errorList.push(errors[key]);
                    }
               }
          } else if (typeof errors === 'string') {
               errorList.push(errors);
          }

          errorList.forEach(function (error) {
               var errorElement = document.createElement('div');
               errorElement.classList.add(
                    'alert', 'alert-dismissible', 'bg-light-danger',
                    'border', 'border-danger', 'border-dashed',
                    'd-flex', 'flex-column', 'flex-sm-row',
                    'w-100', 'p-5', 'mb-10'
               );
               errorElement.setAttribute('role', 'alert');

               errorElement.innerHTML =
                    '<i class="ki-duotone ki-message-text-2 fs-2hx text-danger me-4 mb-5 mb-sm-0">' +
                    '<span class="path1"></span><span class="path2"></span><span class="path3"></span></i>' +
                    '<div class="d-flex flex-column pe-0 pe-sm-10">' +
                    '<h5 class="mb-1 text-danger">Error</h5>' +
                    '<span class="text-danger">' + error + '</span></div>' +
                    '<button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">' +
                    '<i class="ki-outline ki-cross fs-1 text-danger"></i></button>';

               errorContainer.prepend(errorElement);
          });

          KTUtil.scrollTop();
     };

     var enablePreviousButton = function () {
          var prevButton = document.querySelector('[data-kt-stepper-action="previous"]');
          if (prevButton) {
               prevButton.style.display = 'block';
          }
     };

     // ============================================
     // FORM VALIDATION
     // ============================================

     var initValidation = function () {
          // Step 1 - Personal Information
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'student_name': {
                              validators: {
                                   notEmpty: { message: 'Full name is required' }
                              }
                         },
                         'student_home_address': {
                              validators: {
                                   notEmpty: { message: 'Home address is required' }
                              }
                         },
                         'student_phone_home': {
                              validators: {
                                   notEmpty: { message: 'Mobile no. is required' },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: 'Please enter a valid Bangladeshi mobile number'
                                   },
                                   stringLength: {
                                        min: 11, max: 11,
                                        message: 'The mobile number must be exactly 11 digits'
                                   }
                              }
                         },
                         'avatar': {
                              validators: {
                                   file: {
                                        extension: 'jpg,jpeg,png',
                                        type: 'image/jpeg,image/png',
                                        maxSize: 51200,
                                        message: 'The selected file type or size is not valid'
                                   }
                              }
                         },
                         'student_phone_sms': {
                              validators: {
                                   notEmpty: { message: 'SMS no. is required for result and notice' },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: 'Please enter a valid Bangladeshi mobile number'
                                   },
                                   stringLength: {
                                        min: 11, max: 11,
                                        message: 'The mobile number must be exactly 11 digits'
                                   }
                              }
                         },
                         'student_phone_whatsapp': {
                              validators: {
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: 'Please enter a valid Bangladeshi mobile number'
                                   },
                                   stringLength: {
                                        min: 11, max: 11,
                                        message: 'The mobile number must be exactly 11 digits'
                                   }
                              }
                         },
                         'student_gender': {
                              validators: {
                                   notEmpty: { message: 'Gender is required' }
                              }
                         },
                         'student_email': {
                              validators: {
                                   emailAddress: { message: 'The value is not a valid email address' }
                              }
                         }
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: '.fv-row',
                              eleInvalidClass: '',
                              eleValidClass: ''
                         })
                    }
               }
          ));

          // Step 2 - Guardian Information
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'guardian_1_name': {
                              validators: { notEmpty: { message: 'Name is required' } }
                         },
                         'guardian_1_mobile': {
                              validators: {
                                   notEmpty: { message: 'Mobile number is required' },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: 'Please enter a valid Bangladeshi mobile number'
                                   },
                                   stringLength: { min: 11, max: 11, message: 'Must be exactly 11 digits' }
                              }
                         },
                         'guardian_1_gender': {
                              validators: { notEmpty: { message: 'Select the gender' } }
                         },
                         'guardian_1_relationship': {
                              validators: { notEmpty: { message: 'Required field' } }
                         }
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: '.fv-row',
                              eleInvalidClass: '',
                              eleValidClass: ''
                         })
                    }
               }
          ));

          // Step 3 - Academic Information
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'student_institution': {
                              validators: { notEmpty: { message: 'Please select an institution' } }
                         },
                         'student_class': {
                              validators: { notEmpty: { message: 'Please assign this student to a class' } }
                         }
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: '.fv-row',
                              eleInvalidClass: '',
                              eleValidClass: ''
                         })
                    }
               }
          ));

          // Step 4 - Payment Information
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'student_batch': {
                              validators: { notEmpty: { message: 'Select a batch' } }
                         },
                         'student_tuition_fee': {
                              validators: { notEmpty: { message: 'Enter a tuition fee' } }
                         },
                         'payment_style': {
                              validators: { notEmpty: { message: 'Select any payment style' } }
                         },
                         'payment_due_date': {
                              validators: { notEmpty: { message: 'Select payment deadline' } }
                         }
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: '.fv-row',
                              eleInvalidClass: '',
                              eleValidClass: ''
                         })
                    }
               }
          ));
     };

     // ============================================
     // DATE PICKER
     // ============================================

     var initDatePicker = function () {
          var birthDateInput = document.getElementById('student_birth_date');
          if (birthDateInput && typeof flatpickr !== 'undefined') {
               flatpickr(birthDateInput, {
                    dateFormat: 'd-m-Y'
               });
          }
     };

     // ============================================
     // PUBLIC METHODS
     // ============================================

     return {
          init: function () {
               stepper = document.querySelector('#kt_update_student_stepper');
               if (!stepper) return;

               form = stepper.querySelector('#kt_update_student_form');
               formSubmitButton = stepper.querySelector('[data-kt-stepper-action="submit"]');
               formContinueButton = stepper.querySelector('[data-kt-stepper-action="next"]');

               initStepper();
               initValidation();
               handleForm();
               initSubjectModule();
               initDatePicker();
          },

          validateOptionalSubjects: function () {
               return validateOptionalSubjects();
          }
     };
})();

// Initialize on DOM ready
KTUtil.onDOMContentLoaded(function () {
     KTUpdateStudent.init();
});