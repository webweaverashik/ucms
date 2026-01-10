"use strict";

// ============================================================================
// Add Subject Modal Handler
// ============================================================================
var KTAddSubject = function () {
      const element = document.getElementById('kt_modal_add_subject');

      if (!element) {
            return { init: function () { } };
      }

      const form = element.querySelector('#kt_modal_add_subject_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);

      var initAddSubjectModal = () => {
            const cancelButton = element.querySelector('[data-kt-add-subject-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        $(form).find('select[data-control="select2"]').val(null).trigger('change');
                        modal.hide();
                  });
            }

            const closeButton = element.querySelector('[data-kt-add-subject-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        $(form).find('select[data-control="select2"]').val(null).trigger('change');
                        modal.hide();
                  });
            }
      }

      var initValidation = function () {
            if (!form) return;

            var validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'subject_name': {
                                    validators: {
                                          notEmpty: { message: 'Subject is required' }
                                    }
                              },
                              'subject_group': {
                                    validators: {
                                          notEmpty: { message: 'Group is required' },
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

            const submitButton = element.querySelector('[data-kt-add-subject-modal-action="submit"]');
            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        validator.validate().then(function (status) {
                              if (status == 'Valid') {
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    const formData = new FormData(form);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                                    fetch(`/subjects`, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => {
                                                            throw new Error(errorData.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Subject added successfully');
                                                      modal.hide();
                                                      setTimeout(() => { window.location.reload(); }, 1500);
                                                } else {
                                                      throw new Error(data.message || 'Add failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to add subject');
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields');
                              }
                        });
                  });
            }
      }

      return {
            init: function () {
                  initAddSubjectModal();
                  initValidation();
            }
      };
}();

// ============================================================================
// Edit Subject Handler
// ============================================================================
var KTEditSubject = function () {
      const setupSubjectEditing = () => {
            document.querySelectorAll('.subject-editable').forEach(card => {
                  const subjectText = card.querySelector('.subject-text');
                  const subjectInput = card.querySelector('.subject-input');
                  const editIcon = card.querySelector('.edit-icon');
                  const deleteIcon = card.querySelector('.delete-subject');
                  const checkIcon = card.querySelector('.check-icon');
                  const cancelIcon = card.querySelector('.cancel-icon');

                  if (!subjectText || !subjectInput) return;

                  const originalValue = subjectInput.value;

                  if (editIcon) {
                        editIcon.addEventListener('click', (e) => {
                              e.stopPropagation();
                              enterEditMode();
                        });
                  }

                  if (cancelIcon) {
                        cancelIcon.addEventListener('click', (e) => {
                              e.stopPropagation();
                              exitEditMode();
                              subjectInput.value = originalValue;
                        });
                  }

                  if (checkIcon) {
                        checkIcon.addEventListener('click', (e) => {
                              e.stopPropagation();
                              saveChanges();
                        });
                  }

                  subjectInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                              e.preventDefault();
                              saveChanges();
                        } else if (e.key === 'Escape') {
                              exitEditMode();
                              subjectInput.value = originalValue;
                        }
                  });

                  const enterEditMode = () => {
                        subjectText.classList.add('d-none');
                        if (editIcon) editIcon.classList.add('d-none');
                        if (deleteIcon) deleteIcon.classList.add('d-none');

                        subjectInput.classList.remove('d-none');
                        if (checkIcon) checkIcon.classList.remove('d-none');
                        if (cancelIcon) cancelIcon.classList.remove('d-none');

                        card.classList.add('is-editing');
                        subjectInput.focus();
                        subjectInput.select();
                  };

                  const exitEditMode = () => {
                        subjectText.classList.remove('d-none');
                        if (editIcon) editIcon.classList.remove('d-none');
                        if (deleteIcon) deleteIcon.classList.remove('d-none');

                        subjectInput.classList.add('d-none');
                        if (checkIcon) checkIcon.classList.add('d-none');
                        if (cancelIcon) cancelIcon.classList.add('d-none');

                        card.classList.remove('is-editing');
                  };

                  const saveChanges = async () => {
                        const updatedValue = subjectInput.value.trim();
                        const subjectId = card.dataset.id;

                        if (!updatedValue) {
                              toastr.error("Subject name cannot be empty");
                              subjectInput.focus();
                              return;
                        }

                        if (updatedValue === originalValue) {
                              exitEditMode();
                              return;
                        }

                        const saveIcon = checkIcon.querySelector('i');
                        if (saveIcon) {
                              saveIcon.classList.remove('ki-check');
                              saveIcon.classList.add('ki-arrows-circle', 'spinning');
                        }
                        checkIcon.disabled = true;

                        try {
                              const response = await fetch(`/subjects/${subjectId}`, {
                                    method: 'PUT',
                                    headers: {
                                          'Content-Type': 'application/json',
                                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    },
                                    body: JSON.stringify({ subject_name: updatedValue })
                              });

                              if (!response.ok) {
                                    const error = await response.json().catch(() => ({}));
                                    throw new Error(error.message || 'Update failed');
                              }

                              const data = await response.json();

                              if (data.success) {
                                    subjectText.textContent = updatedValue;
                                    exitEditMode();
                                    toastr.success("Subject updated successfully");
                              }
                        } catch (error) {
                              toastr.error(error.message || 'Failed to update subject');
                              subjectInput.value = originalValue;
                        } finally {
                              if (saveIcon) {
                                    saveIcon.classList.remove('ki-arrows-circle', 'spinning');
                                    saveIcon.classList.add('ki-check');
                              }
                              checkIcon.disabled = false;
                        }
                  };
            });
      };

      const handleSubjectDeletion = function () {
            document.querySelectorAll('.delete-subject').forEach(item => {
                  item.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        let subjectId = this.getAttribute('data-subject-id');
                        if (!subjectId) return;

                        let url = routeDeleteSubject.replace(':id', subjectId);

                        Swal.fire({
                              title: "Are you sure to delete this subject?",
                              text: "This action cannot be undone!",
                              icon: "warning",
                              showCancelButton: true,
                              confirmButtonColor: "#d33",
                              cancelButtonColor: "#3085d6",
                              confirmButtonText: "Yes, delete!",
                        }).then((result) => {
                              if (result.isConfirmed) {
                                    fetch(url, {
                                          method: "DELETE",
                                          headers: {
                                                "Content-Type": "application/json",
                                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                                          },
                                    })
                                          .then(response => response.json())
                                          .then(data => {
                                                if (data.success) {
                                                      toastr.success('Subject deleted successfully');
                                                      setTimeout(() => { window.location.reload(); }, 1500);
                                                } else {
                                                      toastr.error(data.message);
                                                }
                                          })
                                          .catch(error => {
                                                Swal.fire({
                                                      title: "Error!",
                                                      text: "Something went wrong. Please try again.",
                                                      icon: "error",
                                                });
                                          });
                              }
                        });
                  });
            });
      };

      return {
            init: function () {
                  setupSubjectEditing();
                  handleSubjectDeletion();
            }
      };
}();

// ============================================================================
// Edit Class Name Modal Handler
// ============================================================================
var KTEditClassName = function () {
      const element = document.getElementById('kt_modal_edit_class');

      if (!element) {
            return { init: function () { } };
      }

      const form = element.querySelector('#kt_modal_edit_class_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);
      let classId = null;

      var initEditClass = () => {
            const cancelButton = element.querySelector('[data-kt-edit-class-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            const closeButton = element.querySelector('[data-kt-edit-class-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_class']");
            if (editButtons.length) {
                  editButtons.forEach((button) => {
                        button.addEventListener("click", function () {
                              classId = this.getAttribute("data-class-id");
                              if (!classId) return;

                              if (form) form.reset();

                              fetch(`/classnames/ajax-data/${classId}`)
                                    .then(response => {
                                          if (!response.ok) {
                                                return response.json().then(errorData => {
                                                      throw new Error(errorData.message || 'Network response was not ok');
                                                });
                                          }
                                          return response.json();
                                    })
                                    .then(data => {
                                          if (data.success && data.data) {
                                                const classname = data.data;

                                                const setValue = (selector, value) => {
                                                      const el = document.querySelector(selector);
                                                      if (el) el.value = value;
                                                };

                                                setValue("input[name='class_name_edit']", classname.class_name);
                                                setValue("select[name='class_numeral_edit']", classname.class_numeral);
                                                setValue("input[name='description_edit']", classname.class_description);

                                                const modalTitle = document.getElementById("kt_modal_edit_class_title");
                                                if (modalTitle) {
                                                      modalTitle.textContent = `Update - ${classname.class_name} (${classname.class_numeral})`;
                                                }

                                                const classNumeralSelect = document.querySelector("select[name='class_numeral_edit']");
                                                if (classNumeralSelect) classNumeralSelect.dispatchEvent(new Event("change"));

                                                modal.show();
                                          } else {
                                                throw new Error(data.message || 'Invalid response data');
                                          }
                                    })
                                    .catch(error => {
                                          toastr.error(error.message || "Failed to load class details");
                                    });
                        });
                  });
            }
      }

      var initValidation = function () {
            if (!form) return;

            var validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'class_name_edit': {
                                    validators: {
                                          notEmpty: { message: 'Name is required' }
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

            const submitButton = element.querySelector('[data-kt-edit-class-modal-action="submit"]');
            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        validator.validate().then(function (status) {
                              if (status == 'Valid') {
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    const formData = new FormData(form);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                                    formData.append('_method', 'PUT');

                                    fetch(`/classnames/${classId}`, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => {
                                                            throw new Error(errorData.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Class updated successfully');
                                                      modal.hide();
                                                      setTimeout(() => { window.location.reload(); }, 1500);
                                                } else {
                                                      throw new Error(data.message || 'Update failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to update class');
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields correctly');
                              }
                        });
                  });
            }
      }

      return {
            init: function () {
                  initEditClass();
                  initValidation();
            }
      };
}();

// ============================================================================
// Regular Students List with Branch Filtering
// ============================================================================
var KTRegularStudentsList = function () {
      var table;
      var datatable;
      var currentBranchFilter = null;

      var initDatatable = function () {
            datatable = $(table).DataTable({
                  "info": true,
                  'order': [],
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 10,
                  "lengthChange": true,
                  "autoWidth": false,
                  'columnDefs': [
                        { orderable: false, targets: 0 },
                  ]
            });

            datatable.on('draw', function () {
                  // Re-initialize tooltips after redraw
                  $('[data-bs-toggle="tooltip"]').tooltip();

                  // Update row numbers to start from 1 for filtered results
                  updateRowNumbers();
            });
      }

      // Function to update row numbers starting from 1 for each filtered view
      var updateRowNumbers = function () {
            var visibleIndex = 1;
            datatable.rows({ search: 'applied' }).every(function (rowIdx) {
                  var row = this.node();
                  if (row) {
                        var firstCell = row.querySelector('td:first-child');
                        if (firstCell) {
                              firstCell.textContent = visibleIndex++;
                        }
                  }
            });
      }

      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-enrolled-regular-students-table-filter="search"]');
            if (filterSearch) {
                  filterSearch.addEventListener('keyup', function (e) {
                        datatable.search(e.target.value).draw();
                  });
            }
      }

      var handleFilter = function () {
            const filterForm = document.querySelector('[data-enrolled-regular-students-table-filter="form"]');
            if (!filterForm) return;

            const filterButton = filterForm.querySelector('[data-enrolled-regular-students-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-enrolled-regular-students-table-filter="reset"]');
            const selectOptions = filterForm.querySelectorAll('select');

            if (filterButton) {
                  filterButton.addEventListener('click', function () {
                        var filterString = '';
                        selectOptions.forEach((item, index) => {
                              if (item.value && item.value !== '') {
                                    if (index !== 0) filterString += ' ';
                                    filterString += item.value;
                              }
                        });
                        datatable.search(filterString).draw();
                  });
            }

            if (resetButton) {
                  resetButton.addEventListener('click', function () {
                        selectOptions.forEach((item) => {
                              $(item).val(null).trigger('change');
                        });
                        datatable.search('').draw();
                  });
            }
      }

      var handleBranchTabs = function () {
            if (!isAdminUser) return;

            const branchTabs = document.querySelectorAll('#branchTabs button[data-branch-filter]');
            if (!branchTabs.length) return;

            // Set initial filter to first branch
            const firstTab = branchTabs[0];
            if (firstTab) {
                  currentBranchFilter = firstTab.getAttribute('data-branch-filter');

                  // Apply initial filter
                  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                        const row = datatable.row(dataIndex).node();
                        if (!row) return false;
                        const rowBranchId = row.getAttribute('data-branch-id');
                        return rowBranchId === currentBranchFilter;
                  });
                  datatable.draw();
            }

            branchTabs.forEach(tab => {
                  tab.addEventListener('click', function () {
                        currentBranchFilter = this.getAttribute('data-branch-filter');

                        // Custom filtering function - remove previous and add new
                        $.fn.dataTable.ext.search.pop();

                        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                              const row = datatable.row(dataIndex).node();
                              if (!row) return false;
                              const rowBranchId = row.getAttribute('data-branch-id');
                              return rowBranchId === currentBranchFilter;
                        });

                        datatable.draw();
                  });
            });
      }

      return {
            init: function () {
                  table = document.getElementById('kt_enrolled_regular_students_table');
                  if (!table) return;

                  initDatatable();
                  handleSearch();
                  handleFilter();
                  handleBranchTabs();
            }
      }
}();

// ============================================================================
// Add Special Class Modal Handler
// ============================================================================
var KTAddSpecialClass = function () {
      const element = document.getElementById('kt_modal_add_special_class');

      if (!element) {
            return { init: function () { } };
      }

      const form = element.querySelector('#kt_modal_add_special_class_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);

      var initModal = () => {
            const cancelButton = element.querySelector('[data-kt-add-special-class-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        $(form).find('select[data-control="select2"]').val(null).trigger('change');
                        modal.hide();
                  });
            }

            const closeButton = element.querySelector('[data-kt-add-special-class-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        $(form).find('select[data-control="select2"]').val(null).trigger('change');
                        modal.hide();
                  });
            }
      }

      var initValidation = function () {
            if (!form) return;

            var validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'name': {
                                    validators: {
                                          notEmpty: { message: 'Name is required' }
                                    }
                              },
                              'payment_type': {
                                    validators: {
                                          notEmpty: { message: 'Payment type is required' }
                                    }
                              },
                              'fee_amount': {
                                    validators: {
                                          notEmpty: { message: 'Fee amount is required' },
                                          numeric: { message: 'Fee must be a number' }
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

            const submitButton = element.querySelector('[data-kt-add-special-class-modal-action="submit"]');
            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        validator.validate().then(function (status) {
                              if (status == 'Valid') {
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    const formData = new FormData(form);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                                    fetch(routeSecondaryClasses, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => {
                                                            throw new Error(errorData.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Special class added successfully');
                                                      modal.hide();
                                                      form.reset();
                                                      $(form).find('select[data-control="select2"]').val(null).trigger('change');

                                                      // Add new card to the container
                                                      addSecondaryClassCard(data.data);
                                                } else {
                                                      throw new Error(data.message || 'Add failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to add special class');
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields');
                              }
                        });
                  });
            }
      }

      return {
            init: function () {
                  initModal();
                  initValidation();
            }
      };
}();

// ============================================================================
// Edit Special Class Modal Handler
// ============================================================================
var KTEditSpecialClass = function () {
      const element = document.getElementById('kt_modal_edit_special_class');

      if (!element) {
            return { init: function () { } };
      }

      const form = element.querySelector('#kt_modal_edit_special_class_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);
      let secondaryClassId = null;

      var initModal = () => {
            const cancelButton = element.querySelector('[data-kt-edit-special-class-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        $(form).find('select[data-control="select2"]').val(null).trigger('change');
                        modal.hide();
                  });
            }

            const closeButton = element.querySelector('[data-kt-edit-special-class-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        $(form).find('select[data-control="select2"]').val(null).trigger('change');
                        modal.hide();
                  });
            }
      }

      var initEditButtons = () => {
            document.addEventListener('click', function (e) {
                  const editBtn = e.target.closest('.edit-secondary-class');
                  if (!editBtn) return;

                  // Check if button is disabled (inactive secondary class)
                  if (editBtn.classList.contains('disabled') || editBtn.getAttribute('data-is-active') === '0') {
                        e.preventDefault();
                        e.stopPropagation();
                        toastr.warning('Please activate this special class first before editing.');
                        return;
                  }

                  secondaryClassId = editBtn.getAttribute('data-secondary-class-id');
                  if (!secondaryClassId) return;

                  // Fetch secondary class data
                  const url = routeSecondaryClassShow.replace(':id', secondaryClassId);

                  fetch(url, {
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  })
                        .then(response => {
                              if (!response.ok) {
                                    return response.json().then(errorData => {
                                          throw new Error(errorData.message || 'Failed to load data');
                                    });
                              }
                              return response.json();
                        })
                        .then(data => {
                              if (data.success && data.data) {
                                    const classData = data.data;

                                    document.getElementById('edit_secondary_class_id').value = classData.id;
                                    document.getElementById('edit_special_class_name').value = classData.name;
                                    document.getElementById('edit_fee_amount').value = classData.fee_amount;

                                    // Set payment type display (read-only) and hidden value
                                    const paymentTypeDisplay = classData.payment_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    document.getElementById('edit_payment_type_display').value = paymentTypeDisplay;
                                    document.getElementById('edit_payment_type').value = classData.payment_type;

                                    // Update modal title
                                    document.getElementById('kt_modal_edit_special_class_title').textContent = `Edit - ${classData.name}`;

                                    modal.show();
                              } else {
                                    throw new Error(data.message || 'Invalid response');
                              }
                        })
                        .catch(error => {
                              toastr.error(error.message || 'Failed to load special class details');
                        });
            });
      }

      var initValidation = function () {
            if (!form) return;

            var validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'name': {
                                    validators: {
                                          notEmpty: { message: 'Name is required' }
                                    }
                              },
                              'fee_amount': {
                                    validators: {
                                          notEmpty: { message: 'Fee amount is required' },
                                          numeric: { message: 'Fee must be a number' }
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

            const submitButton = element.querySelector('[data-kt-edit-special-class-modal-action="submit"]');
            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        validator.validate().then(function (status) {
                              if (status == 'Valid') {
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    const formData = new FormData(form);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                                    formData.append('_method', 'PUT');

                                    const classId = document.getElementById('edit_secondary_class_id').value;
                                    const url = routeSecondaryClassUpdate.replace(':id', classId);

                                    fetch(url, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => {
                                                            throw new Error(errorData.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Special class updated successfully');
                                                      modal.hide();

                                                      // Update the card in DOM
                                                      updateSecondaryClassCard(classId, data.data);
                                                } else {
                                                      throw new Error(data.message || 'Update failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to update special class');
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields correctly');
                              }
                        });
                  });
            }
      }

      return {
            init: function () {
                  initModal();
                  initEditButtons();
                  initValidation();
            }
      };
}();

// ============================================================================
// Secondary Class Actions Handler
// ============================================================================
var KTSecondaryClassActions = function () {
      var handleToggleActivation = function () {
            document.addEventListener('change', function (e) {
                  if (!e.target.classList.contains('toggle-secondary-activation')) return;

                  const checkbox = e.target;
                  const secondaryClassId = checkbox.getAttribute('data-secondary-class-id');
                  const url = routeSecondaryClassUpdate.replace(':id', secondaryClassId);

                  const formData = new FormData();
                  formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                  formData.append('_method', 'PUT');
                  formData.append('toggle_only', 'true');

                  fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  })
                        .then(response => {
                              if (!response.ok) {
                                    return response.json().then(errorData => {
                                          throw new Error(errorData.message || 'Failed to toggle status');
                                    });
                              }
                              return response.json();
                        })
                        .then(data => {
                              if (data.success) {
                                    toastr.success(data.message || 'Status updated');

                                    // Update card styling
                                    const card = document.querySelector(`[data-secondary-class-id="${secondaryClassId}"]`);
                                    if (card) {
                                          const cardInner = card.querySelector('.secondary-class-card');
                                          const statusBadge = card.querySelector('.badge');
                                          const editBtn = card.querySelector('.edit-secondary-class');
                                          const deleteBtn = card.querySelector('.delete-secondary-class');

                                          if (data.is_active) {
                                                cardInner.classList.remove('inactive');
                                                statusBadge.classList.remove('badge-light-danger');
                                                statusBadge.classList.add('badge-light-success');
                                                statusBadge.innerHTML = '<i class="ki-outline ki-check-circle fs-6 me-1"></i>Active';

                                                // Enable edit/delete buttons
                                                if (editBtn) {
                                                      editBtn.classList.remove('disabled');
                                                      editBtn.setAttribute('data-is-active', '1');
                                                      editBtn.setAttribute('title', 'Edit');
                                                }
                                                if (deleteBtn) {
                                                      deleteBtn.classList.remove('disabled');
                                                      deleteBtn.setAttribute('data-is-active', '1');
                                                      deleteBtn.setAttribute('title', 'Delete');
                                                }
                                          } else {
                                                cardInner.classList.add('inactive');
                                                statusBadge.classList.remove('badge-light-success');
                                                statusBadge.classList.add('badge-light-danger');
                                                statusBadge.innerHTML = '<i class="ki-outline ki-cross-circle fs-6 me-1"></i>Inactive';

                                                // Disable edit/delete buttons
                                                if (editBtn) {
                                                      editBtn.classList.add('disabled');
                                                      editBtn.setAttribute('data-is-active', '0');
                                                      editBtn.setAttribute('title', 'Activate first to edit');
                                                }
                                                if (deleteBtn) {
                                                      deleteBtn.classList.add('disabled');
                                                      deleteBtn.setAttribute('data-is-active', '0');
                                                      deleteBtn.setAttribute('title', 'Activate first to delete');
                                                }
                                          }

                                          // Reinitialize tooltips
                                          $('[data-bs-toggle="tooltip"]').tooltip('dispose').tooltip();
                                    }
                              } else {
                                    checkbox.checked = !checkbox.checked;
                                    throw new Error(data.message || 'Toggle failed');
                              }
                        })
                        .catch(error => {
                              checkbox.checked = !checkbox.checked;
                              toastr.error(error.message || 'Failed to update status');
                        });
            });
      }

      var handleDelete = function () {
            document.addEventListener('click', function (e) {
                  const deleteBtn = e.target.closest('.delete-secondary-class');
                  if (!deleteBtn) return;

                  e.preventDefault();

                  // Check if button is disabled (inactive secondary class)
                  if (deleteBtn.classList.contains('disabled') || deleteBtn.getAttribute('data-is-active') === '0') {
                        e.stopPropagation();
                        toastr.warning('Please activate this special class first before deleting.');
                        return;
                  }

                  const secondaryClassId = deleteBtn.getAttribute('data-secondary-class-id');
                  const url = routeSecondaryClassDestroy.replace(':id', secondaryClassId);

                  Swal.fire({
                        title: "Are you sure?",
                        text: "This special class will be permanently deleted!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#3085d6",
                        confirmButtonText: "Yes, delete it!",
                  }).then((result) => {
                        if (result.isConfirmed) {
                              fetch(url, {
                                    method: "DELETE",
                                    headers: {
                                          "Content-Type": "application/json",
                                          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                                    },
                              })
                                    .then(response => response.json())
                                    .then(data => {
                                          if (data.success) {
                                                toastr.success(data.message || 'Special class deleted successfully');

                                                // Remove card from DOM
                                                const card = document.querySelector(`[data-secondary-class-id="${secondaryClassId}"]`);
                                                if (card) {
                                                      card.remove();

                                                      // Check if empty and show empty state
                                                      const container = document.getElementById('secondary-classes-container');
                                                      if (container && container.querySelectorAll('[data-secondary-class-id]').length === 0) {
                                                            showEmptyState();
                                                      }
                                                }
                                          } else {
                                                toastr.error(data.message || 'Delete failed');
                                          }
                                    })
                                    .catch(error => {
                                          Swal.fire({
                                                title: "Error!",
                                                text: "Something went wrong. Please try again.",
                                                icon: "error",
                                          });
                                    });
                        }
                  });
            });
      }

      return {
            init: function () {
                  handleToggleActivation();
                  handleDelete();
            }
      };
}();

// ============================================================================
// Helper Functions
// ============================================================================
function addSecondaryClassCard(data) {
      const container = document.getElementById('secondary-classes-container');
      if (!container) return;

      // Remove empty state if exists
      const emptyState = document.getElementById('secondary-classes-empty');
      if (emptyState) {
            emptyState.closest('.col-12').remove();
      }

      const paymentTypeText = data.payment_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());

      const cardHtml = `
        <div class="col-md-6" data-secondary-class-id="${data.id}">
            <div class="secondary-class-card">
                <div class="secondary-class-header">
                    <div class="d-flex align-items-center">
                        <div class="secondary-class-icon">
                            <i class="ki-outline ki-abstract-26"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="secondary-class-title mb-0">${data.name}</h5>
                            <span class="text-muted fs-7">${paymentTypeText}</span>
                        </div>
                    </div>
                    <div class="secondary-class-actions">
                        <div class="form-check form-switch form-check-solid form-check-success">
                            <input class="form-check-input toggle-secondary-activation" type="checkbox"
                                value="${data.id}"
                                data-secondary-class-id="${data.id}"
                                data-bs-toggle="tooltip" title="Change status"
                                checked>
                        </div>
                    </div>
                </div>
                <div class="secondary-class-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="secondary-class-stat">
                            <span class="stat-label">Fee Amount</span>
                            <span class="stat-value text-primary">৳${parseFloat(data.fee_amount).toLocaleString()}</span>
                        </div>
                        <div class="secondary-class-stat text-end">
                            <span class="stat-label">Students</span>
                            <span class="stat-value text-info">0</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="badge badge-light-success">
                            <i class="ki-outline ki-check-circle fs-6 me-1"></i>
                            Active
                        </span>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-light-primary edit-secondary-class"
                                data-secondary-class-id="${data.id}"
                                data-is-active="1"
                                data-bs-toggle="tooltip" title="Edit">
                                <i class="ki-outline ki-pencil fs-5"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light-danger delete-secondary-class"
                                data-secondary-class-id="${data.id}"
                                data-is-active="1"
                                data-bs-toggle="tooltip" title="Delete">
                                <i class="ki-outline ki-trash fs-5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

      container.insertAdjacentHTML('beforeend', cardHtml);

      // Reinitialize tooltips
      $('[data-bs-toggle="tooltip"]').tooltip();

      // Update badge count
      updateSecondaryClassCount(1);
}

function updateSecondaryClassCard(id, data) {
      const card = document.querySelector(`[data-secondary-class-id="${id}"]`);
      if (!card) return;

      const paymentTypeText = data.payment_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());

      card.querySelector('.secondary-class-title').textContent = data.name;
      card.querySelector('.secondary-class-header .text-muted').textContent = paymentTypeText;
      card.querySelector('.stat-value.text-primary').textContent = '৳' + parseFloat(data.fee_amount).toLocaleString();
}

function showEmptyState() {
      const container = document.getElementById('secondary-classes-container');
      if (!container) return;

      const emptyHtml = `
        <div class="col-12">
            <div class="text-center py-15" id="secondary-classes-empty">
                <div class="empty-state-icon">
                    <i class="ki-outline ki-abstract-26"></i>
                </div>
                <h4 class="text-gray-800 fw-bold mb-3">No Special Classes Yet</h4>
                <p class="text-muted fs-6 mb-6 mw-400px mx-auto">
                    Create special classes for additional courses or programs under this class.
                </p>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#kt_modal_add_special_class">
                    <i class="ki-outline ki-plus fs-3 me-1"></i> Add First Special Class
                </a>
            </div>
        </div>
    `;

      container.innerHTML = emptyHtml;
}

function updateSecondaryClassCount(delta) {
      const badge = document.querySelector('#kt_secondary_classnames_tab .badge');
      if (badge) {
            const countText = badge.textContent.trim();
            const currentCount = parseInt(countText.match(/\d+/)[0]) || 0;
            const newCount = currentCount + delta;
            badge.innerHTML = `<i class="ki-outline ki-abstract-26 fs-6 me-1"></i>${newCount} Classes`;
      }
}

// ============================================================================
// Initialize on DOM Ready
// ============================================================================
KTUtil.onDOMContentLoaded(function () {
      KTAddSubject.init();
      KTEditSubject.init();
      KTEditClassName.init();
      KTRegularStudentsList.init();
      KTAddSpecialClass.init();
      KTEditSpecialClass.init();
      KTSecondaryClassActions.init();
});