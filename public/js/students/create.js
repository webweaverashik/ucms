"use strict";

// Class definition
var KTCreateStudent = function () {
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

     // Private Functions
     var initStepper = function () {
          // Initialize Stepper
          stepperObj = new KTStepper(stepper);

          // Stepper change event
          stepperObj.on('kt.stepper.changed', function (stepper) {
               let currentStep = stepperObj.getCurrentStepIndex();

               console.log("Current Step:", currentStep);

               // Handle button visibility
               if (currentStep === 4) {
                    formSubmitButton.classList.remove('d-none');
                    formSubmitButton.classList.add('d-inline-block');
                    formContinueButton.classList.add('d-none');
               } else if (currentStep === 5) {
                    formSubmitButton.classList.add('d-none');
                    formContinueButton.classList.add('d-none');
               } else {
                    formSubmitButton.classList.remove('d-inline-block', 'd-none');
                    formContinueButton.classList.remove('d-none');
               }

               // Toggle step content visibility
               document.querySelectorAll('[data-kt-stepper-element="content"]').forEach((content, index) => {
                    content.classList.toggle('d-none', index !== (currentStep - 1));
                    if (index === (currentStep - 1)) {
                         content.classList.add('current'); // Mark the active step
                    } else {
                         content.classList.remove('current');
                    }
               });
          });

          // Validation before going to next page
          stepperObj.on('kt.stepper.next', function (stepper) {
               console.log('Step: Moving to next');

               // Get the validator for the current step
               var validator = validations[stepper.getCurrentStepIndex() - 1];

               if (validator) {
                    validator.validate().then(function (status) {
                         console.log('Validation result:', status);

                         if (status === 'Valid') {
                              stepper.goNext();
                              KTUtil.scrollTop();
                         } else {
                              toastr.options.progressBar = true;
                              toastr.warning('You have to fill up the required fields.');
                              KTUtil.scrollTop();
                         }
                    });
               } else {
                    stepper.goNext();
                    KTUtil.scrollTop();
               }
          });

          // Prev event
          stepperObj.on('kt.stepper.previous', function (stepper) {
               console.log('Step: Moving to previous');
               stepper.goPrevious();
               KTUtil.scrollTop();
          });
     };


     var handleForm = function () {
          formSubmitButton.addEventListener('click', function (e) {
               e.preventDefault();

               var validator = validations[3];

               validator.validate().then(function (status) {
                    console.log('Validation Status:', status);

                    if (status === 'Valid') {
                         // Disable button and show loading indicator
                         formSubmitButton.disabled = true;
                         formSubmitButton.setAttribute('data-kt-indicator', 'on');

                         // Collect form data
                         var formData = new FormData(document.getElementById('kt_create_student_form'));

                         // Add CSRF token manually
                         formData.append('_token', csrfToken);

                         // Send data via AJAX
                         fetch(storeStudentRoute, {
                              method: "POST",
                              body: formData,
                              headers: {
                                   'X-CSRF-TOKEN': csrfToken,
                                   'Accept': 'application/json' // Explicitly ask for JSON
                              }
                         })
                              .then(response => {
                                   // First check if the response is OK (status 200-299)
                                   if (!response.ok) {
                                        return response.text().then(text => {
                                             // Try to parse as JSON even if status not OK
                                             try {
                                                  const data = JSON.parse(text);
                                                  return Promise.reject(data.errors || data.message || "Request failed");
                                             } catch {
                                                  return Promise.reject(text || "Request failed with status " + response.status);
                                             }
                                        });
                                   }
                                   return response.json();
                              })
                              .then(data => {
                                   if (data.success) {
                                        Swal.fire({
                                             text: "Student admission completed successfully! Pending for Branch Manager approval.",
                                             icon: "success",
                                             buttonsStyling: false,
                                             confirmButtonText: "Ok",
                                             customClass: {
                                                  confirmButton: "btn btn-primary"
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
                                        console.log("Errors:", data.errors);
                                        showErrors(data.errors || ["An unknown error occurred."]);
                                        enablePreviousButton();
                                   }
                              })
                              .catch(error => {
                                   let errorMessages = [];

                                   // Laravel validation or structured response
                                   if (error.response && error.response.data) {
                                        const data = error.response.data;

                                        if (typeof data === 'string') {
                                             errorMessages.push(data);
                                        } else if (data.message) {
                                             errorMessages.push(data.message);
                                        }

                                        if (data.errors && typeof data.errors === 'object') {
                                             for (const key in data.errors) {
                                                  if (Array.isArray(data.errors[key])) {
                                                       errorMessages.push(...data.errors[key]);
                                                  }
                                             }
                                        }
                                   }
                                   // If error is directly a string or message property exists
                                   else if (typeof error === 'string') {
                                        errorMessages.push(error);
                                   } else if (error.message) {
                                        errorMessages.push(error.message);
                                   } else if (typeof error === 'object') {
                                        // Fallback to stringify the object if nothing else
                                        errorMessages.push(JSON.stringify(error));
                                   } else {
                                        errorMessages.push("Something went wrong!");
                                   }


                                   showErrors(errorMessages);
                                   console.error("Error:", error);
                                   enablePreviousButton();
                              })
                              .finally(() => {
                                   formSubmitButton.removeAttribute('data-kt-indicator');
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


     // Function to display errors on the last step
     function showErrors(errors) {
          var errorContainer = document.getElementById("error-container"); // Ensure container exists

          if (!errorContainer) {
               console.error("Error container not found!");
               return;
          }

          errors.forEach(error => {
               var errorElement = document.createElement("div");
               errorElement.classList.add(
                    "alert", "alert-dismissible", "bg-light-danger", "border", "border-danger", "border-dashed",
                    "d-flex", "flex-column", "flex-sm-row", "w-100", "p-5", "mb-10"
               );
               errorElement.setAttribute("role", "alert");

               errorElement.innerHTML = `
                   <!--begin::Icon-->
                   <i class="ki-duotone ki-message-text-2 fs-2hx text-danger me-4 mb-5 mb-sm-0">
                       <span class="path1"></span>
                       <span class="path2"></span>
                       <span class="path3"></span>
                   </i>
                   <!--end::Icon-->
           
                   <!--begin::Content-->
                   <div class="d-flex flex-column pe-0 pe-sm-10">
                       <h5 class="mb-1 text-danger">The following errors have been found.</h5>
                       <span class="text-danger">${error}</span>
                   </div>
                   <!--end::Content-->
           
                   <!--begin::Close-->
                   <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                       data-bs-dismiss="alert">
                       <i class="ki-outline ki-cross fs-1 text-danger"></i>
                   </button>
                   <!--end::Close-->
               `;

               errorContainer.prepend(errorElement);
          });
     }

     function enablePreviousButton() {
          var prevButton = document.querySelector('[data-kt-stepper-action="previous"]');
          if (prevButton) {
               prevButton.style.display = "block";
          }
     }




     var initValidation = function () {
          // Init form validation rules. For more info check the FormValidation plugin's official
          documentation: https://formvalidation.io/
          // Step 1
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'student_name': {
                              validators: {
                                   notEmpty: {
                                        message: 'Full name is required'
                                   }
                              }
                         },
                         'student_home_address': {
                              validators: {
                                   notEmpty: {
                                        message: 'Home address is required'
                                   }
                              }
                         },
                         'student_phone_home': {
                              validators: {
                                   notEmpty: {
                                        message: 'Mobile no. is required'
                                   },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: 'Please enter a valid Bangladeshi mobile number'
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
                                        message: 'The mobile number must be exactly 11 digits'
                                   }
                              }
                         },
                         'avatar': {
                              validators: {
                                   file: {
                                        extension: 'jpg,jpeg,png',
                                        type: 'image/jpeg,image/png',
                                        maxSize: 102400, // 100 * 1024
                                        message: 'The selected file type or size is not valid'
                                   },
                              }
                         },
                         'student_phone_sms': {
                              validators: {
                                   notEmpty: {
                                        message: 'SMS no. is required for result and notice'
                                   },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: 'Please enter a valid Bangladeshi mobile number'
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
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
                                        min: 11,
                                        max: 11,
                                        message: 'The mobile number must be exactly 11 digits'
                                   }
                              }
                         },
                         'student_gender': {
                              validators: {
                                   notEmpty: {
                                        message: 'Gender is required'
                                   }
                              }
                         },
                         'student_email': {
                              validators: {
                                   emailAddress: {
                                        message: 'The value is not a valid email address',
                                   },
                              }
                         },
                         'birth_date': {
                              
                         },
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

          // Step 2
          validations.push(
               FormValidation.formValidation(form, {
                    fields: {
                         // Guardian 1 fields
                         'guardian_1_name': {
                              validators: {
                                   notEmpty: {
                                        message: 'Name is required'
                                   }
                              }
                         },
                         'guardian_1_mobile': {
                              validators: {
                                   notEmpty: {
                                        message: 'Mobile number is required'
                                   },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: 'Please enter a valid Bangladeshi mobile number'
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
                                        message: 'The mobile number must be exactly 11 digits'
                                   }
                              }
                         },
                         'guardian_1_gender': {
                              validators: {
                                   notEmpty: {
                                        message: 'Select the gender'
                                   }
                              }
                         },
                         'guardian_1_relationship': {
                              validators: {
                                   notEmpty: {
                                        message: 'Required field'
                                   }
                              }
                         },

                         // Guardian 2 fields
                         'guardian_2_name': {
                              validators: {
                                   callback: {
                                        message: 'Name is required',
                                        callback: function (input) {
                                             const name = input.value.trim();
                                             const mobile = form.querySelector('[name="guardian_2_mobile"]').value.trim();
                                             const gender = form.querySelector('[name="guardian_2_gender"]').value.trim();
                                             const relation = form.querySelector('[name="guardian_2_relationship"]').value.trim();
                                             if (name === '' && mobile === '' && gender === '' && relation === '') return true;
                                             return name !== '';
                                        }
                                   }
                              }
                         },
                         'guardian_2_mobile': {
                              validators: {
                                   callback: {
                                        message: 'Mobile number is required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="guardian_2_name"]').value.trim();
                                             const mobile = input.value.trim();
                                             const gender = form.querySelector('[name="guardian_2_gender"]').value.trim();
                                             const relation = form.querySelector('[name="guardian_2_relationship"]').value.trim();
                                             if (name === '' && mobile === '' && gender === '' && relation === '') return true;
                                             return mobile !== '';
                                        }
                                   },
                                   regexp: {
                                        regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                        message: 'Please enter a valid Bangladeshi mobile number'
                                   },
                                   stringLength: {
                                        min: 11,
                                        max: 11,
                                        message: 'The mobile number must be exactly 11 digits'
                                   }
                              }
                         },
                         'guardian_2_gender': {
                              validators: {
                                   callback: {
                                        message: 'Gender is required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="guardian_2_name"]').value.trim();
                                             const mobile = form.querySelector('[name="guardian_2_mobile"]').value.trim();
                                             const gender = input.value.trim();
                                             const relation = form.querySelector('[name="guardian_2_relationship"]').value.trim();
                                             if (name === '' && mobile === '' && gender === '' && relation === '') return true;
                                             return gender !== '';
                                        }
                                   }
                              }
                         },
                         'guardian_2_relationship': {
                              validators: {
                                   callback: {
                                        message: 'Relationship is required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="guardian_2_name"]').value.trim();
                                             const mobile = form.querySelector('[name="guardian_2_mobile"]').value.trim();
                                             const gender = form.querySelector('[name="guardian_2_gender"]').value.trim();
                                             const relation = input.value.trim();
                                             if (name === '' && mobile === '' && gender === '' && relation === '') return true;
                                             return relation !== '';
                                        }
                                   }
                              }
                         },

                         // --- Sibling 1 ---
                         'sibling_1_name': {
                              validators: {
                                   callback: {
                                        message: 'Name is required',
                                        callback: function (input) {
                                             const name = input.value.trim();
                                             const age = form.querySelector('[name="sibling_1_year"]').value.trim();
                                             const cls = form.querySelector('[name="sibling_1_class"]').value.trim();
                                             const inst = form.querySelector('[name="sibling_1_institution"]').value.trim();
                                             const rel = form.querySelector('[name="sibling_1_relationship"]').value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return name !== '';
                                        }
                                   }
                              }
                         },
                         'sibling_1_year': {
                              validators: {
                                   callback: {
                                        message: 'Required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="sibling_1_name"]').value.trim();
                                             const age = input.value.trim();
                                             const cls = form.querySelector('[name="sibling_1_class"]').value.trim();
                                             const inst = form.querySelector('[name="sibling_1_institution"]').value.trim();
                                             const rel = form.querySelector('[name="sibling_1_relationship"]').value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return age !== '';
                                        }
                                   }
                              }
                         },
                         'sibling_1_class': {
                              validators: {
                                   callback: {
                                        message: 'Required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="sibling_1_name"]').value.trim();
                                             const age = form.querySelector('[name="sibling_1_year"]').value.trim();
                                             const cls = input.value.trim();
                                             const inst = form.querySelector('[name="sibling_1_institution"]').value.trim();
                                             const rel = form.querySelector('[name="sibling_1_relationship"]').value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return cls !== '';
                                        }
                                   }
                              }
                         },
                         'sibling_1_institution': {
                              validators: {
                                   callback: {
                                        message: 'Institution is required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="sibling_1_name"]').value.trim();
                                             const age = form.querySelector('[name="sibling_1_year"]').value.trim();
                                             const cls = form.querySelector('[name="sibling_1_class"]').value.trim();
                                             const inst = input.value.trim();
                                             const rel = form.querySelector('[name="sibling_1_relationship"]').value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return inst !== '';
                                        }
                                   }
                              }
                         },
                         'sibling_1_relationship': {
                              validators: {
                                   callback: {
                                        message: 'Required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="sibling_1_name"]').value.trim();
                                             const age = form.querySelector('[name="sibling_1_year"]').value.trim();
                                             const cls = form.querySelector('[name="sibling_1_class"]').value.trim();
                                             const inst = form.querySelector('[name="sibling_1_institution"]').value.trim();
                                             const rel = input.value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return rel !== '';
                                        }
                                   }
                              }
                         },

                         // --- Sibling 2 --- (Same logic, different field names)
                         'sibling_2_name': {
                              validators: {
                                   callback: {
                                        message: 'Name is required',
                                        callback: function (input) {
                                             const name = input.value.trim();
                                             const age = form.querySelector('[name="sibling_2_year"]').value.trim();
                                             const cls = form.querySelector('[name="sibling_2_class"]').value.trim();
                                             const inst = form.querySelector('[name="sibling_2_institution"]').value.trim();
                                             const rel = form.querySelector('[name="sibling_2_relationship"]').value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return name !== '';
                                        }
                                   }
                              }
                         },
                         'sibling_2_year': {
                              validators: {
                                   callback: {
                                        message: 'Required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="sibling_2_name"]').value.trim();
                                             const age = input.value.trim();
                                             const cls = form.querySelector('[name="sibling_2_class"]').value.trim();
                                             const inst = form.querySelector('[name="sibling_2_institution"]').value.trim();
                                             const rel = form.querySelector('[name="sibling_2_relationship"]').value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return age !== '';
                                        }
                                   }
                              }
                         },
                         'sibling_2_class': {
                              validators: {
                                   callback: {
                                        message: 'Required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="sibling_2_name"]').value.trim();
                                             const age = form.querySelector('[name="sibling_2_year"]').value.trim();
                                             const cls = input.value.trim();
                                             const inst = form.querySelector('[name="sibling_2_institution"]').value.trim();
                                             const rel = form.querySelector('[name="sibling_2_relationship"]').value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return cls !== '';
                                        }
                                   }
                              }
                         },
                         'sibling_2_institution': {
                              validators: {
                                   callback: {
                                        message: 'Institution is required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="sibling_2_name"]').value.trim();
                                             const age = form.querySelector('[name="sibling_2_year"]').value.trim();
                                             const cls = form.querySelector('[name="sibling_2_class"]').value.trim();
                                             const inst = input.value.trim();
                                             const rel = form.querySelector('[name="sibling_2_relationship"]').value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return inst !== '';
                                        }
                                   }
                              }
                         },
                         'sibling_2_relationship': {
                              validators: {
                                   callback: {
                                        message: 'Required',
                                        callback: function (input) {
                                             const name = form.querySelector('[name="sibling_2_name"]').value.trim();
                                             const age = form.querySelector('[name="sibling_2_year"]').value.trim();
                                             const cls = form.querySelector('[name="sibling_2_class"]').value.trim();
                                             const inst = form.querySelector('[name="sibling_2_institution"]').value.trim();
                                             const rel = input.value.trim();
                                             if (name === '' && age === '' && cls === '' && inst === '' && rel === '') return true;
                                             return rel !== '';
                                        }
                                   }
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
               })
          );

          // Step 3
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'student_institution': {
                              validators: {
                                   notEmpty: {
                                        message: 'Please, select an institution'
                                   }
                              }
                         },
                         'student_class': {
                              validators: {
                                   notEmpty: {
                                        message: 'Please, assign this student to a class'
                                   }
                              }
                         },
                         'student_academic_group': {
                              validators: {
                                   notEmpty: {
                                        message: 'Select a group'
                                   }
                              }
                         },
                    },
                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         // Bootstrap Framework Integration
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: '.fv-row',
                              eleInvalidClass: '',
                              eleValidClass: ''
                         })
                    }
               }
          ));

          // Step 4
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'student_shift': {
                              validators: {
                                   notEmpty: {
                                        message: 'Select a shift'
                                   }
                              }
                         },
                         'student_tuition_fee': {
                              validators: {
                                   notEmpty: {
                                        message: 'Enter a tuition fee'
                                   },
                              }
                         },
                         'payment_style': {
                              validators: {
                                   notEmpty: {
                                        message: 'Select any payment style'
                                   }
                              }
                         },
                         'payment_due_date': {
                              validators: {
                                   notEmpty: {
                                        message: 'Select payment deadline'
                                   }
                              }
                         }
                    },

                    plugins: {
                         trigger: new FormValidation.plugins.Trigger(),
                         // Bootstrap Framework Integration
                         bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: '.fv-row',
                              eleInvalidClass: '',
                              eleValidClass: ''
                         })
                    }
               }
          ));
     };

     function toggleShiftsByBranch() {
          const branchRadios = document.querySelectorAll('.branch-radio');
          const shiftOptions = document.querySelectorAll('.shift-option');

          branchRadios.forEach(radio => {
               radio.addEventListener('change', () => {
                    const selectedBranch = radio.value;

                    // Hide all shifts first
                    shiftOptions.forEach(option => {
                         option.style.display = 'none';
                         const input = option.querySelector('input[type="radio"]');
                         if (input) input.checked = false;
                    });

                    // Show matching shifts
                    shiftOptions.forEach(option => {
                         if (option.dataset.branch === selectedBranch) {
                              option.style.display = 'block';
                         }
                    });

                    // Auto-check the first visible shift
                    const firstVisible = document.querySelector(`.shift-option[data-branch="${selectedBranch}"] input[type="radio"]`);
                    if (firstVisible) firstVisible.checked = true;
               });
          });

          // Trigger for first selected branch
          const checkedRadio = document.querySelector('.branch-radio:checked');
          if (checkedRadio) checkedRadio.dispatchEvent(new Event('change'));
     }



     return {
          // Public Functions
          init: function () {
               // Elements
               modalEl = document.querySelector('#kt_modal_create_account');

               if (modalEl) {
                    modal = new bootstrap.Modal(modalEl);
               }

               stepper = document.querySelector('#kt_create_student_stepper');

               if (!stepper) {
                    return;
               }

               form = stepper.querySelector('#kt_create_student_form');
               formSubmitButton = stepper.querySelector('[data-kt-stepper-action="submit"]');
               formContinueButton = stepper.querySelector('[data-kt-stepper-action="next"]');

               initStepper();
               toggleShiftsByBranch();
               initValidation();
               handleForm();

               $("#student_birth_date").flatpickr({
                    dateFormat: "d-m-Y",
               });

          }
     };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
     KTCreateStudent.init();
});
