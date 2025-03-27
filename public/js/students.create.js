"use strict";

// Class definition
var KTCreateAccount = function () {
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
               var validator = validations[3]; // Final checking before submission
               validator.validate().then(function (status) {
                    console.log('Validation Status:', status);

                    if (status == 'Valid') {
                         e.preventDefault();
                         formSubmitButton.disabled = true;
                         formSubmitButton.setAttribute('data-kt-indicator', 'on');

                         setTimeout(function () {
                              formSubmitButton.removeAttribute('data-kt-indicator');
                              formSubmitButton.disabled = false;
                              stepperObj.goNext();
                         }, 2000);
                    } else {
                         toastr.options.progressBar = true;
                         toastr.error('Please, fill up the required fields.');
                         KTUtil.scrollTop();
                    }
               });
          });

          // // Expiry month. For more info, plase visit the official plugin site: https://select2.org/
          // $(form.querySelector('[name="card_expiry_month"]')).on('change', function () {
          // // Revalidate the field when an option is chosen
          // validations[4].revalidateField('card_expiry_month');
          // });

          // // Expiry year. For more info, plase visit the official plugin site: https://select2.org/
          // $(form.querySelector('[name="card_expiry_year"]')).on('change', function () {
          // // Revalidate the field when an option is chosen
          // validations[4].revalidateField('card_expiry_year');
          // });

          // // Expiry year. For more info, plase visit the official plugin site: https://select2.org/
          // $(form.querySelector('[name="student_name"]')).on('change', function () {
          // // Revalidate the field when an option is chosen
          // validations[2].revalidateField('student_name');
          // });
     };


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
                                   }
                              }
                         },
                         // 'avatar': {
                         // validators: {
                         // notEmpty: {
                         // message: 'Please select student photo'
                         // },
                         // file: {
                         // extension: 'jpg,jpeg,png',
                         // type: 'image/jpeg,image/png',
                         // maxSize: 204800, // 2048 * 1024
                         // message: 'The selected file type or size is not valid'
                         // },
                         // }
                         // },
                         'student_phone_sms': {
                              validators: {
                                   notEmpty: {
                                        message: 'SMS no. is required for result and notice'
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
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'account_team_size': {
                              validators: {
                                   notEmpty: {
                                        message: 'Time size is required'
                                   }
                              }
                         },
                         'account_name': {
                              validators: {
                                   notEmpty: {
                                        message: 'Account name is required'
                                   }
                              }
                         },
                         'account_plan': {
                              validators: {
                                   notEmpty: {
                                        message: 'Account plan is required'
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

          // Step 3
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'business_name': {
                              validators: {
                                   notEmpty: {
                                        message: 'Busines name is required'
                                   }
                              }
                         },
                         'business_descriptor': {
                              validators: {
                                   notEmpty: {
                                        message: 'Busines descriptor is required'
                                   }
                              }
                         },
                         'business_type': {
                              validators: {
                                   notEmpty: {
                                        message: 'Busines type is required'
                                   }
                              }
                         },
                         'business_email': {
                              validators: {
                                   notEmpty: {
                                        message: 'Busines email is required'
                                   },
                                   emailAddress: {
                                        message: 'The value is not a valid email address'
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

          // Step 4
          validations.push(FormValidation.formValidation(
               form,
               {
                    fields: {
                         'card_name': {
                              validators: {
                                   notEmpty: {
                                        message: 'Name on card is required'
                                   }
                              }
                         },
                         'card_number': {
                              validators: {
                                   notEmpty: {
                                        message: 'Card member is required'
                                   },
                                   creditCard: {
                                        message: 'Card number is not valid'
                                   }
                              }
                         },
                         'card_expiry_month': {
                              validators: {
                                   notEmpty: {
                                        message: 'Month is required'
                                   }
                              }
                         },
                         'card_expiry_year': {
                              validators: {
                                   notEmpty: {
                                        message: 'Year is required'
                                   }
                              }
                         },
                         'card_cvv': {
                              validators: {
                                   notEmpty: {
                                        message: 'CVV is required'
                                   },
                                   digits: {
                                        message: 'CVV must contain only digits'
                                   },
                                   stringLength: {
                                        min: 3,
                                        max: 4,
                                        message: 'CVV must contain 3 to 4 digits only'
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

     // Init form repeater --- more info: https://github.com/DubFriend/jquery.repeater
     const initFormRepeater = () => {
          $('#guardian_info_add_repeater').repeater({
               initEmpty: false,

               defaultValues: {
                    'text-input': 'foo'
               },

               show: function () {
                    $(this).slideDown();

                    // Init select2 on new repeated items
                    initGuardianFields();
               },

               hide: function (deleteElement) {
                    $(this).slideUp(deleteElement);
               }
          });
     }

     // Init condition select2
     const initGuardianFields = () => {
          // Re-init select2
          $(this).find('[data-kt-repeater="select2"]').select2();
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
               // initValidation();
               handleForm();
               initFormRepeater();

               $("#student_birth_date").flatpickr({
                    dateFormat: "d-m-Y",
               });

          }
     };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
     KTCreateAccount.init();
});
