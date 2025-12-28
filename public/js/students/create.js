"use strict";

// Class definition
var KTCreateStudent = (function () {
     // Elements
     var modal;
     var modalEl;
     var stepper;
     var form;
     var formSubmitButton;
     var formContinueButton;

     // Variables
     var stepperObj;
     var validations = [];

     // Reference Elements
     var referredBySelect;

     // Subject Elements
     var classSelect;
     var groupSection;
     var subjectContainer;
     var institutionSelect;
     var currentSelectionMode = null;

     // Track last selected values for click-to-deselect functionality
     var lastMainValue = null;
     var lastFourthValue = null;

     // Private Functions - Stepper
     var initStepper = function () {
          stepperObj = new KTStepper(stepper);

          stepperObj.on("kt.stepper.changed", function (stepper) {
               var currentStep = stepperObj.getCurrentStepIndex();

               if (currentStep === 4) {
                    formSubmitButton.classList.remove("d-none");
                    formSubmitButton.classList.add("d-inline-block");
                    formContinueButton.classList.add("d-none");
               } else if (currentStep === 5) {
                    formSubmitButton.classList.add("d-none");
                    formSubmitButton.classList.remove("d-inline-block");
                    formContinueButton.classList.add("d-none");
               } else {
                    formSubmitButton.classList.add("d-none");
                    formSubmitButton.classList.remove("d-inline-block");
                    formContinueButton.classList.remove("d-none");
               }

               document.querySelectorAll('[data-kt-stepper-element="content"]').forEach(function (content, index) {
                    content.classList.toggle("d-none", index !== currentStep - 1);
                    if (index === currentStep - 1) {
                         content.classList.add("current");
                    } else {
                         content.classList.remove("current");
                    }
               });
          });

          stepperObj.on("kt.stepper.next", function (stepper) {
               var currentStep = stepper.getCurrentStepIndex();

               // Validate Step 3 - Subjects
               if (currentStep === 3) {
                    var subjectValidation = validateSubjectSelection();
                    if (!subjectValidation.valid) {
                         toastr.error(subjectValidation.message);
                         return;
                    }
               }

               var validator = validations[currentStep - 1];

               if (validator) {
                    validator.validate().then(function (status) {
                         if (status === "Valid") {
                              stepper.goNext();
                              KTUtil.scrollTop();
                         } else {
                              toastr.options.progressBar = true;
                              toastr.warning("You have to fill up the required fields.");
                              KTUtil.scrollTop();
                         }
                    });
               } else {
                    stepper.goNext();
                    KTUtil.scrollTop();
               }
          });

          stepperObj.on("kt.stepper.previous", function (stepper) {
               stepper.goPrevious();
               KTUtil.scrollTop();
          });
     };

     // Private Functions - Reference (ajax-reference.js)
     var initReference = function () {
          referredBySelect = $('select[name="referred_by"]');

          if (referredBySelect.length === 0) {
               return;
          }

          referredBySelect.select2({
               placeholder: "Select the person",
               allowClear: true
          });

          loadReferredByData('teacher');

          $('input[name="referer_type"]').on('change', function () {
               var selectedType = $(this).val();
               loadReferredByData(selectedType);
          });
     };

     var loadReferredByData = function (type) {
          var url = type === 'teacher' ? ajaxTeacherRoute : ajaxStudentRoute;

          $.ajax({
               url: url,
               type: 'GET',
               dataType: 'json',
               beforeSend: function () {
                    referredBySelect.prop('disabled', true);
               },
               success: function (data) {
                    referredBySelect.empty().append(
                         '<option value="" disabled selected>Select the person</option>'
                    );

                    $.each(data, function (index, person) {
                         var displayText = person.name;
                         if (person.student_unique_id) {
                              displayText += ' (' + person.student_unique_id + ')';
                         }
                         referredBySelect.append(
                              '<option value="' + person.id + '">' + displayText + '</option>'
                         );
                    });

                    referredBySelect.prop('disabled', false).trigger('change');
               },
               error: function (xhr, status, error) {
                    console.error("Error loading referred by data:", error);
                    toastr.error("Failed to load data. Please try again.");
                    referredBySelect.prop('disabled', false);
               }
          });
     };

     // Private Functions - Subjects (ajax-subjects.js)
     var initSubjects = function () {
          classSelect = $('#student_class_input');
          groupSection = $('#student-group-selection');
          subjectContainer = $('#subject_list');
          institutionSelect = $('#institution_select');

          if (classSelect.length === 0) {
               return;
          }

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

          // Optional subject radio handlers with click-to-deselect
          $(document).on('click', '.optional-main-radio', handleMainRadioClick);
          $(document).on('click', '.optional-4th-radio', handleFourthRadioClick);
          $(document).on('change', '.optional-main-radio', handleMainOptionalSelection);
          $(document).on('change', '.optional-4th-radio', handle4thOptionalSelection);

          // Clear all optional selections button
          $(document).on('click', '.clear-optional-selections', clearAllOptionalSelections);
     };

     var initSubjectPage = function () {
          updateGroupVisibility();
          if (classSelect.val()) {
               loadSubjects();
               loadInstitutions();
          }
     };

     var handleClassChange = function () {
          updateGroupVisibility();
          loadSubjects();
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
               institutionSelect.append(
                    $('<option></option>')
                         .val(institution.id)
                         .text(institution.name + ' (EIIN: ' + (institution.eiin_number || 'N/A') + ')')
               );
          });

          institutionSelect.prop('disabled', false).trigger('change');
     };

     var loadSubjects = function () {
          var classId = classSelect.val();
          if (!classId) {
               showSubjectMessage('Please select a class first');
               return;
          }

          var classNumeral = getClassNumeral();
          var academicGroup = getAcademicGroup(classNumeral);
          var includeGeneral = classNumeral >= 9;

          showSubjectLoading();

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
                         // Reset last selected values when subjects reload
                         lastMainValue = null;
                         lastFourthValue = null;
                         renderSubjects(response.subjects, response.group, response.has_optional, response.selection_mode);
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

     var renderSubjects = function (subjects, currentGroup, hasOptional, selectionMode) {
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

          subjectContainer.html(html || '<div class="alert alert-info">No subjects available</div>');
          updateSelectAllCompulsoryState();
     };

     // UPDATED: Using data-subject-id instead of name attribute
     var createCompulsorySection = function (title, subjects, colorClass) {
          var subjectsHtml = '';
          subjects.forEach(function (subject) {
               subjectsHtml += '<div class="col-md-3 mb-3">' +
                    '<div class="form-check form-check-custom form-check-solid">' +
                    '<input class="form-check-input subject-checkbox-compulsory" type="checkbox" ' +
                    'data-subject-id="' + subject.id + '" ' +
                    'id="sub_' + subject.id + '" checked>' +
                    '<label class="form-check-label fs-6" for="sub_' + subject.id + '">' +
                    subject.name + '</label></div></div>';
          });

          return '<div class="subject-section mb-6 p-4 border border-dashed border-' + colorClass + ' rounded">' +
               '<label class="form-label fw-bold text-' + colorClass + ' fs-5 mb-4">' +
               '<i class="ki-outline ki-book-open fs-4 me-2"></i>' + title + '</label>' +
               '<div class="row">' + subjectsHtml + '</div></div>';
     };

     // UPDATED: Added clear button next to title
     var createOptionalSection = function (title, subjects, selectionMode) {
          var requiresMain = selectionMode && selectionMode.requires_main;
          var requires4th = selectionMode && selectionMode.requires_4th;
          var instruction = selectionMode ? selectionMode.instruction : 'Select optional subjects';

          var subjectsRows = '';
          subjects.forEach(function (subject) {
               subjectsRows += '<tr data-subject-id="' + subject.id + '">';
               subjectsRows += '<td><span class="text-gray-800 fw-semibold fs-6">' + subject.name + '</span></td>';

               if (requiresMain) {
                    subjectsRows += '<td class="text-center">' +
                         '<div class="form-check form-check-custom form-check-solid justify-content-center">' +
                         '<input class="form-check-input optional-main-radio" type="radio" ' +
                         'name="optional_main_subject" value="' + subject.id + '" ' +
                         'data-subject-id="' + subject.id + '" data-subject-name="' + subject.name + '" ' +
                         'id="main_' + subject.id + '"></div></td>';
               }

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

          var tableHeaders = '<th class="ps-4 min-w-200px rounded-start">Subject Name</th>';
          if (requiresMain) {
               tableHeaders += '<th class="w-120px text-center">Main Subject</th>';
          }
          if (requires4th) {
               var roundedClass = !requiresMain ? ' rounded-end' : '';
               tableHeaders += '<th class="w-120px text-center' + roundedClass + '">4th Subject</th>';
          }

          var summaryContent = '<span class="fw-semibold">Selected: </span>';
          if (requiresMain) {
               summaryContent += '<span id="main_subject_display" class="badge badge-light-primary me-2"></span>';
          }
          summaryContent += '<span id="fourth_subject_display" class="badge badge-light-warning"></span>';

          // Title with clear button
          var titleHtml = '<div class="d-flex justify-content-between align-items-center mb-3">' +
               '<label class="form-label fw-bold text-warning fs-5 mb-0">' +
               '<i class="ki-outline ki-abstract-26 fs-4 me-2"></i>' + title + '</label>' +
               '<button type="button" class="btn btn-icon btn-sm btn-light-danger clear-optional-selections" ' +
               'data-bs-toggle="tooltip" data-bs-placement="top" title="Clear selections">' +
               '<i class="ki-outline ki-cross fs-2"></i></button></div>';

          return '<div class="subject-section mb-6 p-4 border border-dashed border-warning rounded">' +
               titleHtml +
               '<div class="alert alert-info d-flex align-items-center py-3 mb-4">' +
               '<i class="ki-outline ki-information-5 fs-2 text-info me-3"></i>' +
               '<div class="d-flex flex-column"><span class="fw-semibold">Optional Selection:</span>' +
               '<span>' + instruction + ' (Optional - skip if not needed)</span></div></div>' +
               '<div class="table-responsive"><table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3">' +
               '<thead><tr class="fw-bold text-muted bg-light">' + tableHeaders + '</tr></thead>' +
               '<tbody>' + subjectsRows + '</tbody></table></div>' +
               '<div class="mt-4 p-3 bg-light-primary rounded" id="optional_selection_summary" style="display: none;">' +
               '<div class="d-flex align-items-center">' +
               '<i class="ki-outline ki-check-circle fs-2 text-success me-3"></i>' +
               '<div>' + summaryContent + '</div></div></div></div>';
     };

     // Click-to-deselect handlers
     var handleMainRadioClick = function () {
          var currentValue = $(this).val();
          if (lastMainValue === currentValue) {
               $(this).prop('checked', false);
               lastMainValue = null;
               updateOptionalSummary();
          } else {
               lastMainValue = currentValue;
          }
     };

     var handleFourthRadioClick = function () {
          var currentValue = $(this).val();
          if (lastFourthValue === currentValue) {
               $(this).prop('checked', false);
               lastFourthValue = null;
               updateOptionalSummary();
          } else {
               lastFourthValue = currentValue;
          }
     };

     // Clear all optional selections
     var clearAllOptionalSelections = function () {
          $('input[name="optional_main_subject"]').prop('checked', false);
          $('input[name="optional_4th_subject"]').prop('checked', false);
          lastMainValue = null;
          lastFourthValue = null;
          updateOptionalSummary();
          toastr.info('Optional selections cleared');
     };

     var handleMainOptionalSelection = function () {
          var mainSelected = $(this).val();
          var fourthSelected = $('input[name="optional_4th_subject"]:checked').val();

          if (mainSelected && fourthSelected && mainSelected === fourthSelected) {
               $('input[name="optional_4th_subject"]:checked').prop('checked', false);
               lastFourthValue = null;
               toastr.info('4th Subject has been cleared as it was same as Main Subject');
          }

          updateOptionalSummary();
     };

     var handle4thOptionalSelection = function () {
          var fourthSelected = $(this).val();
          var mainSelected = $('input[name="optional_main_subject"]:checked').val();

          if (currentSelectionMode && currentSelectionMode.requires_main) {
               if (mainSelected && fourthSelected && mainSelected === fourthSelected) {
                    $(this).prop('checked', false);
                    lastFourthValue = null;
                    toastr.warning('You cannot select the same subject for both Main and 4th Subject');
                    return false;
               }
          }

          updateOptionalSummary();
     };

     var updateOptionalSummary = function () {
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
          subjectContainer.html('<div class="text-center py-10">' +
               '<div class="spinner-border text-primary" role="status">' +
               '<span class="visually-hidden">Loading...</span></div>' +
               '<p class="text-muted mt-3">Loading subjects...</p></div>');
     };

     var showSubjectMessage = function (msg) {
          subjectContainer.html('<div class="alert alert-info d-flex align-items-center">' +
               '<i class="ki-outline ki-information-5 fs-2 me-3"></i>' + msg + '</div>');
     };

     var showInstitutionLoading = function () {
          institutionSelect.prop('disabled', true);
     };

     var showInstitutionMessage = function (msg) {
          institutionSelect.empty().append('<option value="">' + msg + '</option>');
          institutionSelect.prop('disabled', false);
     };

     // =====================================================
     // Subject Validation Functions
     // =====================================================

     /**
      * Validates that at least one subject is selected from any section
      */
     var validateSubjectSelection = function () {
          // Count subjects from all sections
          var compulsoryChecked = $('.subject-checkbox-compulsory:checked').length;
          var mainOptionalSelected = $('input[name="optional_main_subject"]:checked').val();
          var fourthSubjectSelected = $('input[name="optional_4th_subject"]:checked').val();

          // Calculate total selected subjects
          var totalSelected = compulsoryChecked;
          if (mainOptionalSelected) totalSelected++;
          if (fourthSubjectSelected) totalSelected++;

          // At least one subject must be selected from any section
          if (totalSelected === 0) {
               return {
                    valid: false,
                    message: 'Please select at least one subject from any section'
               };
          }

          // Still validate optional subject combinations
          var optionalValidation = validateOptionalSubjects();
          if (!optionalValidation.valid) {
               return optionalValidation;
          }

          return { valid: true };
     };

     /**
      * Validates optional subject selection
      * - Does NOT require Main or 4th subjects to be selected
      * - Only validates that Main ≠ 4th when both are selected
      */
     var validateOptionalSubjects = function () {
          // Check if optional section exists
          var hasOptionalSection = $('.optional-4th-radio').length > 0 ||
               $('.optional-main-radio').length > 0;

          if (!hasOptionalSection) {
               return { valid: true };
          }

          var mainSelected = $('input[name="optional_main_subject"]:checked').val();
          var fourthSelected = $('input[name="optional_4th_subject"]:checked').val();

          // Only validate that Main and 4th are different IF BOTH are selected
          if (mainSelected && fourthSelected && mainSelected === fourthSelected) {
               return {
                    valid: false,
                    message: 'Main and 4th Subject cannot be the same. Please select different subjects.'
               };
          }

          // All other cases are valid - optional subjects are truly optional now
          return { valid: true };
     };

     // =====================================================
     // Collect Subjects Data for Form Submission
     // =====================================================

     /**
      * Collects all selected subjects into a clean array format
      * This is called before form submission to build the subjects data
      */
     var collectSubjectsData = function () {
          var subjects = [];

          // Collect checked compulsory subjects (is_4th = false)
          $('.subject-checkbox-compulsory:checked').each(function () {
               subjects.push({
                    id: $(this).data('subject-id'),
                    is_4th: false
               });
          });

          // Collect main optional subject (is_4th = false)
          var mainOptional = $('input[name="optional_main_subject"]:checked').val();
          if (mainOptional) {
               subjects.push({
                    id: parseInt(mainOptional),
                    is_4th: false
               });
          }

          // Collect 4th subject (is_4th = true)
          var fourthSubject = $('input[name="optional_4th_subject"]:checked').val();
          if (fourthSubject) {
               subjects.push({
                    id: parseInt(fourthSubject),
                    is_4th: true
               });
          }

          return subjects;
     };

     // Private Functions - Form Handler
     var handleForm = function () {
          formSubmitButton.addEventListener("click", function (e) {
               e.preventDefault();

               var validator = validations[3];

               validator.validate().then(function (status) {
                    if (status === "Valid") {
                         formSubmitButton.disabled = true;
                         formSubmitButton.setAttribute("data-kt-indicator", "on");

                         var formData = new FormData(document.getElementById("kt_create_student_form"));
                         formData.append("_token", csrfToken);

                         // =====================================================
                         // IMPORTANT: Clean and rebuild subjects data
                         // =====================================================

                         // Remove any existing subjects entries (stale data)
                         var keysToDelete = [];
                         for (var pair of formData.entries()) {
                              if (pair[0].startsWith('subjects[') ||
                                   pair[0] === 'optional_main_subject' ||
                                   pair[0] === 'optional_4th_subject') {
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

                         // =====================================================

                         fetch(storeStudentRoute, {
                              method: "POST",
                              body: formData,
                              headers: {
                                   "X-CSRF-TOKEN": csrfToken,
                                   Accept: "application/json",
                              },
                         })
                              .then(function (response) {
                                   if (!response.ok) {
                                        return response.text().then(function (text) {
                                             try {
                                                  var data = JSON.parse(text);
                                                  return Promise.reject(data.errors || data.message || "Request failed");
                                             } catch (e) {
                                                  return Promise.reject(text || "Request failed with status " + response.status);
                                             }
                                        });
                                   }
                                   return response.json();
                              })
                              .then(function (data) {
                                   if (data.success) {
                                        Swal.fire({
                                             text: "Student admission completed successfully! Pending for Branch Manager approval.",
                                             icon: "success",
                                             buttonsStyling: false,
                                             confirmButtonText: "Ok",
                                             customClass: {
                                                  confirmButton: "btn btn-primary",
                                             },
                                        });

                                        document.getElementById("admitted_name").innerText = data.student.name;
                                        document.getElementById("admitted_id").innerText = data.student.student_unique_id;

                                        stepperObj.goNext();

                                        setTimeout(function () {
                                             var prevButton = document.querySelector('[data-kt-stepper-action="previous"]');
                                             if (prevButton) {
                                                  prevButton.style.display = "none";
                                             }
                                        }, 300);
                                   } else {
                                        showErrors(data.errors || ["An unknown error occurred."]);
                                        enablePreviousButton();
                                   }
                              })
                              .catch(function (error) {
                                   var errorMessages = [];

                                   if (error.response && error.response.data) {
                                        var data = error.response.data;
                                        if (typeof data === "string") {
                                             errorMessages.push(data);
                                        } else if (data.message) {
                                             errorMessages.push(data.message);
                                        }
                                        if (data.errors && typeof data.errors === "object") {
                                             for (var key in data.errors) {
                                                  if (Array.isArray(data.errors[key])) {
                                                       errorMessages = errorMessages.concat(data.errors[key]);
                                                  }
                                             }
                                        }
                                   } else if (typeof error === "string") {
                                        errorMessages.push(error);
                                   } else if (error.message) {
                                        errorMessages.push(error.message);
                                   } else if (Array.isArray(error)) {
                                        errorMessages = error;
                                   } else if (typeof error === "object") {
                                        errorMessages.push(JSON.stringify(error));
                                   } else {
                                        errorMessages.push("Something went wrong!");
                                   }

                                   showErrors(errorMessages);
                                   console.error("Error:", error);
                                   enablePreviousButton();
                              })
                              .finally(function () {
                                   formSubmitButton.removeAttribute("data-kt-indicator");
                                   formSubmitButton.disabled = false;
                              });
                    } else {
                         toastr.options.progressBar = true;
                         toastr.error("Please, fill up the required fields.");
                         KTUtil.scrollTop();
                    }
               });
          });
     };

     var showErrors = function (errors) {
          var errorContainer = document.getElementById("error-container");

          if (!errorContainer) {
               console.error("Error container not found!");
               return;
          }

          errors.forEach(function (error) {
               var errorElement = document.createElement("div");
               errorElement.classList.add(
                    "alert",
                    "alert-dismissible",
                    "bg-light-danger",
                    "border",
                    "border-danger",
                    "border-dashed",
                    "d-flex",
                    "flex-column",
                    "flex-sm-row",
                    "w-100",
                    "p-5",
                    "mb-10"
               );
               errorElement.setAttribute("role", "alert");

               errorElement.innerHTML =
                    '<i class="ki-duotone ki-message-text-2 fs-2hx text-danger me-4 mb-5 mb-sm-0">' +
                    '<span class="path1"></span>' +
                    '<span class="path2"></span>' +
                    '<span class="path3"></span>' +
                    "</i>" +
                    '<div class="d-flex flex-column pe-0 pe-sm-10">' +
                    '<h5 class="mb-1 text-danger">The following errors have been found.</h5>' +
                    '<span class="text-danger">' + error + "</span>" +
                    "</div>" +
                    '<button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">' +
                    '<i class="ki-outline ki-cross fs-1 text-danger"></i>' +
                    "</button>";

               errorContainer.prepend(errorElement);
          });
     };

     var enablePreviousButton = function () {
          var prevButton = document.querySelector('[data-kt-stepper-action="previous"]');
          if (prevButton) {
               prevButton.style.display = "block";
          }
     };

     // Private Functions - Validation
     var initValidation = function () {
          // Step 1 - Student Personal Information
          validations.push(
               FormValidation.formValidation(form, {
                    fields: {
                         student_name: {
                              validators: {
                                   notEmpty: {
                                        message: "Full name is required",
                                   },
                              },
                         },
                         student_home_address: {
                              validators: {
                                   notEmpty: {
                                        message: "Home address is required",
                                   },
                              },
                         },
                         student_phone_home: {
                              validators: {
                                   notEmpty: {
                                        message: "Mobile no. is required",
                                   },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: "Please enter a valid Bangladeshi mobile number",
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
                                        message: "The mobile number must be exactly 11 digits",
                                   },
                              },
                         },
                         avatar: {
                              validators: {
                                   file: {
                                        extension: "jpg,jpeg,png",
                                        type: "image/jpeg,image/png",
                                        maxSize: 51200,
                                        message: "The selected file type or size is not valid",
                                   },
                              },
                         },
                         student_phone_sms: {
                              validators: {
                                   notEmpty: {
                                        message: "SMS no. is required for result and notice",
                                   },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: "Please enter a valid Bangladeshi mobile number",
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
                                        message: "The mobile number must be exactly 11 digits",
                                   },
                              },
                         },
                         student_phone_whatsapp: {
                              validators: {
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: "Please enter a valid Bangladeshi mobile number",
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
                                        message: "The mobile number must be exactly 11 digits",
                                   },
                              },
                         },
                         student_gender: {
                              validators: {
                                   notEmpty: {
                                        message: "Gender is required",
                                   },
                              },
                         },
                         student_email: {
                              validators: {
                                   emailAddress: {
                                        message: "The value is not a valid email address",
                                   },
                              },
                         },
                         birth_date: {},
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: ".fv-row",
                              eleInvalidClass: "",
                              eleValidClass: "",
                         }),
                    },
               })
          );

          // Step 2 - Guardian & Sibling Info
          validations.push(
               FormValidation.formValidation(form, {
                    fields: {
                         guardian_1_name: {
                              validators: {
                                   notEmpty: {
                                        message: "Name is required",
                                   },
                              },
                         },
                         guardian_1_mobile: {
                              validators: {
                                   notEmpty: {
                                        message: "Mobile number is required",
                                   },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: "Please enter a valid Bangladeshi mobile number",
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
                                        message: "The mobile number must be exactly 11 digits",
                                   },
                              },
                         },
                         guardian_1_gender: {
                              validators: {
                                   notEmpty: {
                                        message: "Select the gender",
                                   },
                              },
                         },
                         guardian_1_relationship: {
                              validators: {
                                   notEmpty: {
                                        message: "Required field",
                                   },
                              },
                         },
                         guardian_2_name: {
                              validators: {
                                   callback: {
                                        message: "Name is required",
                                        callback: function (input) {
                                             var name = input.value.trim();
                                             var mobile = form.querySelector('[name="guardian_2_mobile"]').value.trim();
                                             var gender = form.querySelector('[name="guardian_2_gender"]').value.trim();
                                             var relation = form.querySelector('[name="guardian_2_relationship"]').value.trim();
                                             if (name === "" && mobile === "" && gender === "" && relation === "") return true;
                                             return name !== "";
                                        },
                                   },
                              },
                         },
                         guardian_2_mobile: {
                              validators: {
                                   callback: {
                                        message: "Mobile number is required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="guardian_2_name"]').value.trim();
                                             var mobile = input.value.trim();
                                             var gender = form.querySelector('[name="guardian_2_gender"]').value.trim();
                                             var relation = form.querySelector('[name="guardian_2_relationship"]').value.trim();
                                             if (name === "" && mobile === "" && gender === "" && relation === "") return true;
                                             return mobile !== "";
                                        },
                                   },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: "Please enter a valid Bangladeshi mobile number",
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
                                        message: "The mobile number must be exactly 11 digits",
                                   },
                              },
                         },
                         guardian_2_gender: {
                              validators: {
                                   callback: {
                                        message: "Gender is required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="guardian_2_name"]').value.trim();
                                             var mobile = form.querySelector('[name="guardian_2_mobile"]').value.trim();
                                             var gender = input.value.trim();
                                             var relation = form.querySelector('[name="guardian_2_relationship"]').value.trim();
                                             if (name === "" && mobile === "" && gender === "" && relation === "") return true;
                                             return gender !== "";
                                        },
                                   },
                              },
                         },
                         guardian_2_relationship: {
                              validators: {
                                   callback: {
                                        message: "Relationship is required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="guardian_2_name"]').value.trim();
                                             var mobile = form.querySelector('[name="guardian_2_mobile"]').value.trim();
                                             var gender = form.querySelector('[name="guardian_2_gender"]').value.trim();
                                             var relation = input.value.trim();
                                             if (name === "" && mobile === "" && gender === "" && relation === "") return true;
                                             return relation !== "";
                                        },
                                   },
                              },
                         },
                         sibling_1_name: {
                              validators: {
                                   callback: {
                                        message: "Name is required",
                                        callback: function (input) {
                                             var name = input.value.trim();
                                             var age = form.querySelector('[name="sibling_1_year"]').value.trim();
                                             var cls = form.querySelector('[name="sibling_1_class"]').value.trim();
                                             var inst = form.querySelector('[name="sibling_1_institution"]').value.trim();
                                             var rel = form.querySelector('[name="sibling_1_relationship"]').value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return name !== "";
                                        },
                                   },
                              },
                         },
                         sibling_1_year: {
                              validators: {
                                   callback: {
                                        message: "Required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="sibling_1_name"]').value.trim();
                                             var age = input.value.trim();
                                             var cls = form.querySelector('[name="sibling_1_class"]').value.trim();
                                             var inst = form.querySelector('[name="sibling_1_institution"]').value.trim();
                                             var rel = form.querySelector('[name="sibling_1_relationship"]').value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return age !== "";
                                        },
                                   },
                              },
                         },
                         sibling_1_class: {
                              validators: {
                                   callback: {
                                        message: "Required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="sibling_1_name"]').value.trim();
                                             var age = form.querySelector('[name="sibling_1_year"]').value.trim();
                                             var cls = input.value.trim();
                                             var inst = form.querySelector('[name="sibling_1_institution"]').value.trim();
                                             var rel = form.querySelector('[name="sibling_1_relationship"]').value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return cls !== "";
                                        },
                                   },
                              },
                         },
                         sibling_1_institution: {
                              validators: {
                                   callback: {
                                        message: "Institution is required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="sibling_1_name"]').value.trim();
                                             var age = form.querySelector('[name="sibling_1_year"]').value.trim();
                                             var cls = form.querySelector('[name="sibling_1_class"]').value.trim();
                                             var inst = input.value.trim();
                                             var rel = form.querySelector('[name="sibling_1_relationship"]').value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return inst !== "";
                                        },
                                   },
                              },
                         },
                         sibling_1_relationship: {
                              validators: {
                                   callback: {
                                        message: "Required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="sibling_1_name"]').value.trim();
                                             var age = form.querySelector('[name="sibling_1_year"]').value.trim();
                                             var cls = form.querySelector('[name="sibling_1_class"]').value.trim();
                                             var inst = form.querySelector('[name="sibling_1_institution"]').value.trim();
                                             var rel = input.value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return rel !== "";
                                        },
                                   },
                              },
                         },
                         sibling_2_name: {
                              validators: {
                                   callback: {
                                        message: "Name is required",
                                        callback: function (input) {
                                             var name = input.value.trim();
                                             var age = form.querySelector('[name="sibling_2_year"]').value.trim();
                                             var cls = form.querySelector('[name="sibling_2_class"]').value.trim();
                                             var inst = form.querySelector('[name="sibling_2_institution"]').value.trim();
                                             var rel = form.querySelector('[name="sibling_2_relationship"]').value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return name !== "";
                                        },
                                   },
                              },
                         },
                         sibling_2_year: {
                              validators: {
                                   callback: {
                                        message: "Required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="sibling_2_name"]').value.trim();
                                             var age = input.value.trim();
                                             var cls = form.querySelector('[name="sibling_2_class"]').value.trim();
                                             var inst = form.querySelector('[name="sibling_2_institution"]').value.trim();
                                             var rel = form.querySelector('[name="sibling_2_relationship"]').value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return age !== "";
                                        },
                                   },
                              },
                         },
                         sibling_2_class: {
                              validators: {
                                   callback: {
                                        message: "Required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="sibling_2_name"]').value.trim();
                                             var age = form.querySelector('[name="sibling_2_year"]').value.trim();
                                             var cls = input.value.trim();
                                             var inst = form.querySelector('[name="sibling_2_institution"]').value.trim();
                                             var rel = form.querySelector('[name="sibling_2_relationship"]').value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return cls !== "";
                                        },
                                   },
                              },
                         },
                         sibling_2_institution: {
                              validators: {
                                   callback: {
                                        message: "Institution is required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="sibling_2_name"]').value.trim();
                                             var age = form.querySelector('[name="sibling_2_year"]').value.trim();
                                             var cls = form.querySelector('[name="sibling_2_class"]').value.trim();
                                             var inst = input.value.trim();
                                             var rel = form.querySelector('[name="sibling_2_relationship"]').value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return inst !== "";
                                        },
                                   },
                              },
                         },
                         sibling_2_relationship: {
                              validators: {
                                   callback: {
                                        message: "Required",
                                        callback: function (input) {
                                             var name = form.querySelector('[name="sibling_2_name"]').value.trim();
                                             var age = form.querySelector('[name="sibling_2_year"]').value.trim();
                                             var cls = form.querySelector('[name="sibling_2_class"]').value.trim();
                                             var inst = form.querySelector('[name="sibling_2_institution"]').value.trim();
                                             var rel = input.value.trim();
                                             if (name === "" && age === "" && cls === "" && inst === "" && rel === "") return true;
                                             return rel !== "";
                                        },
                                   },
                              },
                         },
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: ".fv-row",
                              eleInvalidClass: "",
                              eleValidClass: "",
                         }),
                    },
               })
          );

          // Step 3 - Enrolled Subjects
          validations.push(
               FormValidation.formValidation(form, {
                    fields: {
                         student_institution: {
                              validators: {
                                   notEmpty: {
                                        message: "Please, select an institution",
                                   },
                              },
                         },
                         student_class: {
                              validators: {
                                   notEmpty: {
                                        message: "Please, assign this student to a class",
                                   },
                              },
                         },
                         student_academic_group: {
                              validators: {
                                   notEmpty: {
                                        message: "Select a group",
                                   },
                              },
                         },
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: ".fv-row",
                              eleInvalidClass: "",
                              eleValidClass: "",
                         }),
                    },
               })
          );

          // Step 4 - Administrative Info
          validations.push(
               FormValidation.formValidation(form, {
                    fields: {
                         student_batch: {
                              validators: {
                                   notEmpty: {
                                        message: "Select a batch",
                                   },
                              },
                         },
                         student_admission_fee: {
                              validators: {
                                   notEmpty: {
                                        message: "Enter an admission fee",
                                   },
                              },
                         },
                         student_tuition_fee: {
                              validators: {
                                   notEmpty: {
                                        message: "Enter a tuition fee",
                                   },
                              },
                         },
                         payment_style: {
                              validators: {
                                   notEmpty: {
                                        message: "Select any payment style",
                                   },
                              },
                         },
                         payment_due_date: {
                              validators: {
                                   notEmpty: {
                                        message: "Select payment deadline",
                                   },
                              },
                         },
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: ".fv-row",
                              eleInvalidClass: "",
                              eleValidClass: "",
                         }),
                    },
               })
          );
     };

     // Private Functions - Branch/Batch Toggle
     var toggleShiftsByBranch = function () {
          var branchRadios = document.querySelectorAll(".branch-radio");

          branchRadios.forEach(function (radio) {
               radio.addEventListener("change", function () {
                    var selectedBranch = this.value;

                    var batchOptions = document.querySelectorAll(".batch-option");

                    batchOptions.forEach(function (option) {
                         option.style.display = "none";
                         var input = option.querySelector('input[type="radio"]');
                         var label = option.querySelector('label');
                         if (input) input.checked = false;
                         if (label) label.classList.remove('active');
                    });

                    var matchingBatches = document.querySelectorAll('.batch-option[data-branch="' + selectedBranch + '"]');
                    matchingBatches.forEach(function (option) {
                         option.style.display = "block";
                    });

                    var firstVisible = document.querySelector('.batch-option[data-branch="' + selectedBranch + '"]');
                    if (firstVisible) {
                         var input = firstVisible.querySelector('input[type="radio"]');
                         var label = firstVisible.querySelector('label');
                         if (input) input.checked = true;
                         if (label) label.classList.add('active');
                    }

                    reinitBatchButtons();
               });
          });

          var checkedRadio = document.querySelector(".branch-radio:checked");
          if (checkedRadio) {
               checkedRadio.dispatchEvent(new Event("change"));
          }
     };

     var reinitBatchButtons = function () {
          var batchContainer = document.getElementById('batch-container');
          if (batchContainer) {
               var labels = batchContainer.querySelectorAll('[data-kt-button="true"]');
               labels.forEach(function (label) {
                    label.addEventListener('click', function () {
                         batchContainer.querySelectorAll('.batch-option:not([style*="display: none"]) [data-kt-button="true"]').forEach(function (l) {
                              l.classList.remove('active');
                         });
                         this.classList.add('active');
                    });
               });
          }
     };

     // Private Functions - Date Picker
     var initDatePicker = function () {
          var birthDateInput = document.getElementById("student_birth_date");
          if (birthDateInput) {
               $(birthDateInput).flatpickr({
                    dateFormat: "d-m-Y",
               });
          }
     };

     return {
          // Public Functions
          init: function () {
               modalEl = document.querySelector("#kt_modal_create_account");
               if (modalEl) {
                    modal = new bootstrap.Modal(modalEl);
               }

               stepper = document.querySelector("#kt_create_student_stepper");
               if (!stepper) {
                    return;
               }

               form = stepper.querySelector("#kt_create_student_form");
               formSubmitButton = stepper.querySelector('[data-kt-stepper-action="submit"]');
               formContinueButton = stepper.querySelector('[data-kt-stepper-action="next"]');

               // Initialize all modules
               initStepper();
               initReference();
               initSubjects();
               toggleShiftsByBranch();
               reinitBatchButtons();
               initValidation();
               handleForm();
               initDatePicker();
          },

          // Public method to validate optional subjects (if needed externally)
          validateOptionalSubjects: function () {
               return validateOptionalSubjects();
          }
     };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
     KTCreateStudent.init();
});