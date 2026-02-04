document.addEventListener('DOMContentLoaded', function () {

      // Create Backup Button
      const createBtn = document.getElementById('btn-create-backup');
      if (createBtn) {
            createBtn.addEventListener('click', function () {
                  Swal.fire({
                        title: 'Create New Backup?',
                        html: `
                    <p class="mb-4">This will create a new database backup.</p>
                    <div class="form-check form-check-custom form-check-solid justify-content-center">
                        <input class="form-check-input" type="checkbox" id="notify-checkbox" checked />
                        <label class="form-check-label" for="notify-checkbox">
                            Send notification to admin users
                        </label>
                    </div>
                `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Create Backup',
                        cancelButtonText: 'Cancel',
                        customClass: {
                              confirmButton: 'btn btn-primary',
                              cancelButton: 'btn btn-light'
                        },
                        buttonsStyling: false,
                        preConfirm: () => {
                              return document.getElementById('notify-checkbox').checked;
                        }
                  }).then((result) => {
                        if (result.isConfirmed) {
                              const notify = result.value;

                              Swal.fire({
                                    title: 'Creating Backup...',
                                    html: 'Please wait while the backup is being created.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => {
                                          Swal.showLoading();
                                    }
                              });

                              fetch(backupRoutes.create, {
                                    method: 'POST',
                                    headers: {
                                          'Content-Type': 'application/json',
                                          'X-CSRF-TOKEN': csrfToken,
                                          'X-Requested-With': 'XMLHttpRequest',
                                          'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ notify: notify })
                              })
                                    .then(response => response.json())
                                    .then(data => {
                                          if (data.success) {
                                                updateTable(data.backups);
                                                updateStats(data.total_size, data.last_backup, data.backups.length);

                                                toastr.success('Backup created successfully!');
                                          } else {
                                                Swal.fire({
                                                      title: 'Error!',
                                                      text: data.message || 'Backup creation failed.',
                                                      icon: 'error',
                                                      confirmButtonText: 'OK',
                                                      customClass: {
                                                            confirmButton: 'btn btn-danger'
                                                      },
                                                      buttonsStyling: false
                                                });
                                          }
                                    })
                                    .catch(error => {
                                          console.error('Error:', error);
                                          Swal.fire({
                                                title: 'Error!',
                                                text: 'An unexpected error occurred.',
                                                icon: 'error',
                                                confirmButtonText: 'OK',
                                                customClass: {
                                                      confirmButton: 'btn btn-danger'
                                                },
                                                buttonsStyling: false
                                          });
                                    });
                        }
                  });
            });
      }

      // Delete Backup Buttons (Event Delegation)
      document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.btn-delete-backup');
            if (deleteBtn) {
                  const filename = deleteBtn.getAttribute('data-filename');

                  Swal.fire({
                        title: 'Delete Backup?',
                        html: `<p>Are you sure you want to delete:</p><p class="fw-bold text-danger">${filename}</p><p class="text-muted">This action cannot be undone.</p>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Delete',
                        cancelButtonText: 'Cancel',
                        customClass: {
                              confirmButton: 'btn btn-danger',
                              cancelButton: 'btn btn-light'
                        },
                        buttonsStyling: false
                  }).then((result) => {
                        if (result.isConfirmed) {
                              Swal.fire({
                                    title: 'Deleting...',
                                    html: 'Please wait while the backup is being deleted.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: () => {
                                          Swal.showLoading();
                                    }
                              });

                              const deleteUrl = backupRoutes.destroy.replace(':filename', filename);

                              fetch(deleteUrl, {
                                    method: 'DELETE',
                                    headers: {
                                          'X-CSRF-TOKEN': csrfToken,
                                          'X-Requested-With': 'XMLHttpRequest',
                                          'Accept': 'application/json'
                                    }
                              })
                                    .then(response => response.json())
                                    .then(data => {
                                          if (data.success) {
                                                updateTable(data.backups);
                                                updateStats(data.total_size, data.last_backup, data.backups.length);

                                                toastr.success('Backup deleted successfully!');
                                          } else {
                                                Swal.fire({
                                                      title: 'Error!',
                                                      text: data.message || 'Delete failed.',
                                                      icon: 'error',
                                                      confirmButtonText: 'OK',
                                                      customClass: {
                                                            confirmButton: 'btn btn-danger'
                                                      },
                                                      buttonsStyling: false
                                                });
                                          }
                                    })
                                    .catch(error => {
                                          console.error('Error:', error);
                                          Swal.fire({
                                                title: 'Error!',
                                                text: 'An unexpected error occurred.',
                                                icon: 'error',
                                                confirmButtonText: 'OK',
                                                customClass: {
                                                      confirmButton: 'btn btn-danger'
                                                },
                                                buttonsStyling: false
                                          });
                                    });
                        }
                  });
            }
      });

      // Update Table with New Data
      function updateTable(backups) {
            const tbody = document.getElementById('backup-table-body');
            if (!tbody) return;

            if (!backups || backups.length === 0) {
                  tbody.innerHTML = `
                <tr id="no-backups-row">
                    <td colspan="5" class="text-center py-10">
                        <i class="ki-outline ki-file-deleted fs-3x text-gray-400 mb-5"></i>
                        <p class="text-gray-500 fs-5 mb-0">No backups found</p>
                        <p class="text-gray-400 fs-7">Click "Create Backup" to generate your first backup</p>
                    </td>
                </tr>
            `;
                  return;
            }

            let html = '';
            backups.forEach((backup, index) => {
                  const downloadUrl = backupRoutes.download.replace(':filename', backup.filename);
                  html += `
                <tr data-filename="${backup.filename}">
                    <td class="ps-4">${index + 1}</td>
                    <td>
                        <i class="ki-outline ki-file-added fs-4 text-primary me-2"></i>
                        ${backup.filename}
                    </td>
                    <td>${backup.size_formatted}</td>
                    <td>${backup.date_formatted}</td>
                    <td class="pe-4 text-end">
                        <a href="${downloadUrl}" class="btn btn-sm btn-light-success me-2" title="Download">
                            <i class="ki-outline ki-file-down fs-4"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-light-danger btn-delete-backup" data-filename="${backup.filename}" title="Delete">
                            <i class="ki-outline ki-trash fs-4"></i>
                        </button>
                    </td>
                </tr>
            `;
            });

            tbody.innerHTML = html;
      }

      // Update Stats
      function updateStats(totalSize, lastBackup, totalCount) {
            const statsTotal = document.getElementById('stats-total');
            const statsSize = document.getElementById('stats-size');
            const statsLast = document.getElementById('stats-last');

            if (statsTotal) statsTotal.textContent = totalCount;
            if (statsSize) statsSize.textContent = totalSize;
            if (statsLast) statsLast.textContent = lastBackup;
      }

});