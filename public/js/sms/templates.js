document.addEventListener('DOMContentLoaded', function () {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      // Toggle is_active
      document.querySelectorAll('.toggle-status').forEach(toggle => {
            toggle.addEventListener('change', function () {
                  const id = this.dataset.id;
                  fetch(`/sms/templates/${id}/toggle`, {
                        method: 'PATCH',
                        headers: {
                              'X-CSRF-TOKEN': csrf,
                              'Accept': 'application/json'
                        }
                  }).then(res => res.json()).then(data => {
                        if (data.success) {
                              toastr.success(`SMS Template ${data.is_active ? 'activated' : 'deactivated'}`);
                        } else {
                              toastr.error('Failed to update status');
                        }
                  });
            });
      });

      // Body edit: counter + show save button
      document.querySelectorAll('.template-body').forEach(textarea => {
            const counter = textarea.parentElement.querySelector('.char-counter');
            const saveBtn = textarea.parentElement.querySelector('.save-body');
            const max = textarea.getAttribute('maxlength') || 500;

            function updateCounter() {
                  counter.textContent = `${textarea.value.length}/${max}`;
            }
            updateCounter();

            textarea.addEventListener('input', function () {
                  updateCounter();
                  saveBtn.classList.remove('d-none'); // show save button on change
            });
      });

      // Save body via AJAX
      document.querySelectorAll('.save-body').forEach(button => {
            button.addEventListener('click', function () {
                  const id = this.dataset.id;
                  const textarea = document.querySelector(`.template-body[data-id="${id}"]`);
                  fetch(`/sms/templates/${id}/update-body`, {
                        method: 'PATCH',
                        headers: {
                              'X-CSRF-TOKEN': csrf,
                              'Accept': 'application/json',
                              'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ body: textarea.value })
                  }).then(res => res.json()).then(data => {
                        if (data.success) {
                              toastr.success('SMS Template updated');
                              button.classList.add('d-none');
                        } else {
                              toastr.error('Template Update failed');
                        }
                  });
            });
      });
});
