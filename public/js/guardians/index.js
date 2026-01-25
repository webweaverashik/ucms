"use strict";

var KTGuardiansList = function () {
    // Define shared variables
    var datatables = {};
    var currentFilters = {
        relationship: '',
        gender: ''
    };

    // Initialize DataTable for a specific table
    var initDatatable = function (tableId, branchId) {
        var table = document.getElementById(tableId);
        if (!table) {
            return null;
        }

        var datatable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routeGuardiansData,
                type: 'GET',
                data: function (d) {
                    d.branch_id = branchId;
                    d.relationship = currentFilters.relationship;
                    d.gender = currentFilters.gender;
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable AJAX Error:', error);
                    toastr.error('Failed to load data. Please try again.');
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'mobile', name: 'mobile_number' },
                { data: 'gender', name: 'gender' },
                { data: 'student', name: 'student', orderable: false },
                { data: 'relationship', name: 'relationship' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [],
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            autoWidth: false,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
            },
            drawCallback: function () {
                // Reinitialize tooltips after table redraw
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });

        return datatable;
    };

    // Initialize all datatables
    var initAllDatatables = function () {
        if (isAdmin) {
            branchIds.forEach(function (branchId) {
                var tableId = 'kt_guardians_table_branch_' + branchId;
                datatables[branchId] = initDatatable(tableId, branchId);
            });
        } else {
            datatables['single'] = initDatatable('kt_guardians_table', null);
        }
    };

    // Load badge counts for tabs
    var loadBadgeCounts = function () {
        if (!isAdmin) return;

        branchIds.forEach(function (branchId) {
            fetch(routeGuardiansCount + '?branch_id=' + branchId)
                .then(response => response.json())
                .then(data => {
                    var badge = document.querySelector('.guardian-count-badge[data-branch-id="' + branchId + '"]');
                    if (badge) {
                        badge.classList.remove('badge-loading');
                        badge.innerHTML = data.count;
                    }
                })
                .catch(error => {
                    console.error('Error loading count:', error);
                    var badge = document.querySelector('.guardian-count-badge[data-branch-id="' + branchId + '"]');
                    if (badge) {
                        badge.classList.remove('badge-loading');
                        badge.innerHTML = '?';
                    }
                });
        });
    };

    // Search Datatable
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-guardians-table-filter="search"]');
        if (!filterSearch) return;

        let debounceTimer;
        filterSearch.addEventListener('keyup', function (e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                // Reload all datatables with new search
                Object.values(datatables).forEach(function (dt) {
                    if (dt) {
                        dt.search(e.target.value).draw();
                    }
                });
            }, 300);
        });
    };

    // Filter Datatable
    var handleFilter = function () {
        const filterForm = document.querySelector('[data-kt-guardians-table-filter="form"]');
        if (!filterForm) return;

        const filterButton = filterForm.querySelector('[data-kt-guardians-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-guardians-table-filter="reset"]');
        const relationshipSelect = filterForm.querySelector('[data-kt-guardians-table-filter="relationship"]');
        const genderSelect = filterForm.querySelector('[data-kt-guardians-table-filter="gender"]');

        // Filter datatable on submit
        if (filterButton) {
            filterButton.addEventListener('click', function () {
                currentFilters.relationship = relationshipSelect ? relationshipSelect.value : '';
                currentFilters.gender = genderSelect ? genderSelect.value : '';

                // Reload all datatables
                Object.values(datatables).forEach(function (dt) {
                    if (dt) {
                        dt.ajax.reload();
                    }
                });

                // Reload badge counts
                loadBadgeCounts();
            });
        }

        // Reset datatable
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                // Reset filter values
                if (relationshipSelect) $(relationshipSelect).val(null).trigger('change');
                if (genderSelect) $(genderSelect).val(null).trigger('change');

                currentFilters.relationship = '';
                currentFilters.gender = '';

                // Reload all datatables
                Object.values(datatables).forEach(function (dt) {
                    if (dt) {
                        dt.ajax.reload();
                    }
                });

                // Reload badge counts
                loadBadgeCounts();
            });
        }
    };

    // Delete guardian using event delegation
    const handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-guardian');
            if (!deleteBtn) return;

            e.preventDefault();

            const guardianId = deleteBtn.getAttribute('data-guardian-id');
            const url = routeDeleteGuardian.replace(':id', guardianId);

            Swal.fire({
                title: "Are you sure to delete this guardian?",
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
                                Swal.fire({
                                    title: "Deleted!",
                                    text: data.message || "The guardian has been removed successfully.",
                                    icon: "success",
                                }).then(() => {
                                    // Reload all datatables
                                    Object.values(datatables).forEach(function (dt) {
                                        if (dt) {
                                            dt.ajax.reload(null, false);
                                        }
                                    });
                                    // Reload badge counts
                                    loadBadgeCounts();
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.message || "Failed to delete guardian.",
                                    icon: "error",
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Fetch Error:", error);
                            Swal.fire({
                                title: "Error!",
                                text: "Something went wrong. Please try again.",
                                icon: "error",
                            });
                        });
                }
            });
        });
    };

    // Handle tab change - reload data when tab is shown
    var handleTabChange = function () {
        if (!isAdmin) return;

        document.querySelectorAll('#guardianBranchTabs a[data-bs-toggle="tab"]').forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function (e) {
                var branchId = e.target.getAttribute('data-branch-id');
                if (datatables[branchId]) {
                    // Adjust columns when tab becomes visible
                    datatables[branchId].columns.adjust();
                }
            });
        });
    };

    return {
        // Public functions
        init: function () {
            initAllDatatables();
            loadBadgeCounts();
            handleSearch();
            handleDeletion();
            handleFilter();
            handleTabChange();
        },
        // Expose method to reload datatables
        reloadDatatables: function () {
            Object.values(datatables).forEach(function (dt) {
                if (dt) {
                    dt.ajax.reload(null, false);
                }
            });
            loadBadgeCounts();
        }
    }
}();

var KTGuardiansEditGuardian = function () {
    // Shared variables
    const element = document.getElementById('kt_modal_edit_guardian');

    // Early return if element doesn't exist
    if (!element) {
        console.error('Modal element not found');
        return {
            init: function () { }
        };
    }

    const form = element.querySelector('#kt_modal_edit_guardian_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);
    let guardianId = null;

    // Init edit guardian modal
    var initEditGuardian = () => {
        // Cancel button handler
        const cancelButton = element.querySelector('[data-kt-guardians-modal-action="cancel"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        // Close button handler
        const closeButton = element.querySelector('[data-kt-guardians-modal-action="close"]');
        if (closeButton) {
            closeButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        // Delegate edit button click
        document.addEventListener("click", function (e) {
            const button = e.target.closest("[data-bs-target='#kt_modal_edit_guardian']");
            if (!button) return;

            guardianId = button.getAttribute("data-guardian-id");
            if (!guardianId) return;

            // Clear form
            if (form) form.reset();

            fetch(`/guardians/${guardianId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.data) {
                        const guardian = data.data;

                        // Helper function to safely set values
                        const setValue = (selector, value) => {
                            const el = document.querySelector(selector);
                            if (el) el.value = value;
                        };

                        const checkRadio = (name, value) => {
                            const radio = document.querySelector(`input[name='${name}'][value='${value}']`);
                            if (radio) radio.checked = true;
                        };

                        // Populate form fields
                        setValue("select[name='guardian_student']", guardian.student_id);
                        setValue("input[name='guardian_name']", guardian.name);
                        setValue("input[name='guardian_mobile_number']", guardian.mobile_number);
                        checkRadio('guardian_gender', guardian.gender);
                        setValue("select[name='guardian_relationship']", guardian.relationship);

                        // Dispatch change events for Select2
                        const studentSelect = document.querySelector("select[name='guardian_student']");
                        const relationshipSelect = document.querySelector("select[name='guardian_relationship']");
                        if (studentSelect) $(studentSelect).trigger("change");
                        if (relationshipSelect) $(relationshipSelect).trigger("change");

                        // Show modal
                        modal.show();
                    } else {
                        throw new Error(data.message || 'Invalid response data');
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    toastr.error(error.message || "Failed to load guardian details");
                });
        });
    };

    // Form validation
    var initValidation = function () {
        if (!form) return;

        var validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'guardian_name': {
                        validators: {
                            notEmpty: {
                                message: 'Name is required'
                            }
                        }
                    },
                    'guardian_mobile_number': {
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
                    'guardian_gender': {
                        validators: {
                            notEmpty: {
                                message: 'Please, select a gender'
                            }
                        }
                    },
                    'guardian_relationship': {
                        validators: {
                            notEmpty: {
                                message: 'Select a relationship'
                            }
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

        const submitButton = element.querySelector('[data-kt-guardians-modal-action="submit"]');
        if (submitButton && validator) {
            submitButton.addEventListener('click', function (e) {
                e.preventDefault();

                validator.validate().then(function (status) {
                    if (status == 'Valid') {
                        // Show loading indication
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        // Prepare form data
                        const formData = new FormData(form);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        formData.append('_method', 'PUT');

                        // Submit via AJAX
                        fetch(`/guardians/${guardianId}`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    return response.json().then(err => { throw err; });
                                }
                                return response.json();
                            })
                            .then(data => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (data.success) {
                                    toastr.success(data.message || 'Guardian updated successfully');
                                    modal.hide();
                                    form.reset();

                                    // Reload datatables
                                    KTGuardiansList.reloadDatatables();
                                } else {
                                    throw new Error(data.message || 'Update failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (error.errors) {
                                    // Handle validation errors
                                    Object.keys(error.errors).forEach(function (key) {
                                        toastr.error(error.errors[key][0]);
                                    });
                                } else {
                                    toastr.error(error.message || 'Failed to update guardian');
                                }
                                console.error('Error:', error);
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
            initEditGuardian();
            initValidation();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTGuardiansList.init();
    KTGuardiansEditGuardian.init();
});