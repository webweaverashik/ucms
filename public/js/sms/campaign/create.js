"use strict";

var KTAddCampaign = function () {
      const form = document.querySelector('#kt_send_campaign_sms_form');
      const submitButton = form.querySelector('button[type="submit"]');
      const maxChars = 500;

      // Form validation
      var initValidation = function () {
            if (!form) return;

            var validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'campaign_title': {
                                    validators: {
                                          notEmpty: { message: 'Campaign title is required' }
                                    }
                              },
                              'branch_id': {
                                    validators: {
                                          notEmpty: { message: 'Branch is required' }
                                    }
                              },
                              'class_id': {
                                    validators: {
                                          notEmpty: { message: 'Class is required' }
                                    }
                              },
                              'recipients_select': {
                                    validators: {
                                          choice: {
                                                min: 1,
                                                message: 'Select at least one recipient type'
                                          }
                                    }
                              },
                              'message_type': {
                                    validators: {
                                          notEmpty: { message: 'Select SMS language' }
                                    }
                              },
                              'message_body': {
                                    validators: {
                                          notEmpty: { message: 'Message body is required' }
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
            );

            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();

                        validator.validate().then(function (status) {
                              if (status == 'Valid') {
                                    // Show loading
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    // Prepare form data
                                    const formData = new FormData(form);

                                    // Gather multiple checkbox values for recipients
                                    let recipients = [];
                                    form.querySelectorAll('input[name="recipients_select"]:checked').forEach(cb => {
                                          recipients.push(cb.value);
                                    });
                                    formData.set('recipients', JSON.stringify(recipients));

                                    // Add CSRF token
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                                    // Debug: Log all form data to console
                                    // for (let [key, value] of formData.entries()) {
                                    //       console.log(key, value);
                                    // }

                                    // Submit via AJAX
                                    fetch(`/sms/send-campaign`, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => response.json())
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Campaign created successfully');

                                                      setTimeout(() => {
                                                            window.location.href = '/sms/send-campaign';
                                                      }, 2000);
                                                } else {
                                                      toastr.error(data.message || 'Failed to save campaign');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Something went wrong');
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields');
                              }
                        });
                  });
            }
      };

      // Message field setup
      const messageInput = form.querySelector('textarea[name="message_body"]');
      messageInput.setAttribute("maxlength", maxChars);

      const counter = document.createElement("small");
      counter.style.display = "block";
      counter.style.marginTop = "5px";
      counter.style.color = "#555";
      messageInput.parentNode.appendChild(counter);

      function updateCounter() {
            let val = messageInput.value;
            if (val.length > maxChars) {
                  val = val.substring(0, maxChars);
                  messageInput.value = val;
            }
            counter.textContent = `${val.length}/${maxChars} characters`;
            counter.style.color = val.length > maxChars ? "red" : "#555";
      }
      messageInput.addEventListener("input", updateCounter);
      updateCounter();

      return {
            init: function () {
                  initValidation();
            }
      };
}();

KTUtil.onDOMContentLoaded(function () {
      KTAddCampaign.init();
});
