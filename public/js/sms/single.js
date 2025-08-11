document.addEventListener("DOMContentLoaded", function () {
      const form = document.getElementById("kt_send_single_sms_form");
      const maxChars = 500;

      if (!form) return;

      // Message field setup
      const messageInput = form.querySelector('textarea[name="message_body"]');
      messageInput.setAttribute("maxlength", maxChars); // Prevent typing more than 500

      // Live character counter
      const counter = document.createElement("small");
      counter.style.display = "block";
      counter.style.marginTop = "5px";
      counter.style.color = "#555";
      messageInput.parentNode.appendChild(counter);

      function updateCounter() {
            let val = messageInput.value;
            if (val.length > maxChars) {
                  val = val.substring(0, maxChars); // Truncate pasted text
                  messageInput.value = val;
            }
            counter.textContent = `${val.length}/${maxChars} characters`;
            counter.style.color = val.length > maxChars ? "red" : "#555";
      }
      messageInput.addEventListener("input", updateCounter);
      updateCounter();

      // Init FormValidation
      FormValidation.formValidation(form, {
            fields: {
                  mobile: {
                        validators: {
                              notEmpty: {
                                    message: 'Mobile number is required'
                              },
                              regexp: {
                                    regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                    message: 'Please enter a valid 11 digit mobile number'
                              }
                        }
                  },
                  message_body: {
                        validators: {
                              notEmpty: {
                                    message: 'Message cannot be empty'
                              },
                              stringLength: {
                                    max: maxChars,
                                    message: `Message cannot exceed ${maxChars} characters`
                              }
                        }
                  }
            },

            plugins: {
                  trigger: new FormValidation.plugins.Trigger(),
                  bootstrap5: new FormValidation.plugins.Bootstrap5({
                        rowSelector: '.fv-row',
                        eleInvalidClass: '',
                        eleValidClass: ''
                  }),
                  submitButton: new FormValidation.plugins.SubmitButton(),
                  defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
            },
      });
});
