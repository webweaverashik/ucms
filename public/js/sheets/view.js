"use strict";

var KTSheetPaymentsList = function () {
    // Define shared variables
    var table;
    var datatable;

    // Private functions
    var initDatatable = function () {
        // Init datatable --- more info on datatables: https://datatables.net/manual/
        datatable = $(table).DataTable({
            "info": true,
            'order': [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 10,
            "lengthChange": true,
            "autoWidth": false,
            'columnDefs': []
        });

        datatable.on('draw', function () {});
    }

    // Search Datatable
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-sheet-payments-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    // Filter Datatable
    var handleFilter = function () {
        const filterForm = document.querySelector('[data-sheet-payments-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-sheet-payments-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-sheet-payments-table-filter="reset"]');
        const selectOptions = filterForm.querySelectorAll('select');

        filterButton.addEventListener('click', function () {
            var filterString = '';

            selectOptions.forEach((item, index) => {
                if (item.value && item.value !== '') {
                    if (index !== 0) {
                        filterString += ' ';
                    }
                    filterString += item.value;
                }
            });

            datatable.search(filterString).draw();
        });

        resetButton.addEventListener('click', function () {
            selectOptions.forEach((item, index) => {
                $(item).val(null).trigger('change');
            });
            datatable.search('').draw();
        });
    }

    return {
        init: function () {
            table = document.getElementById('kt_sheet_payments_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleSearch();
            handleFilter();
        }
    }
}();

// File Upload Handler Helper
var FileUploadHandler = function (wrapperSelector, inputSelector) {
    const wrapper = document.querySelector(wrapperSelector);
    const input = document.querySelector(inputSelector);

    if (!wrapper || !input) return null;

    const contentDiv = wrapper.querySelector('.file-upload-content');
    const selectedDiv = wrapper.querySelector('.file-selected-info');
    const fileNameSpan = wrapper.querySelector('.file-name');
    const removeBtn = wrapper.querySelector('.remove-file-btn');

    const showSelected = (fileName) => {
        contentDiv.classList.add('d-none');
        selectedDiv.classList.remove('d-none');
        fileNameSpan.textContent = fileName;
        wrapper.classList.add('has-file');
    };

    const showDefault = () => {
        contentDiv.classList.remove('d-none');
        selectedDiv.classList.add('d-none');
        fileNameSpan.textContent = '';
        wrapper.classList.remove('has-file');
        input.value = '';
    };

    input.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Validate file type
            if (file.type !== 'application/pdf') {
                toastr.error('Please select a PDF file only');
                showDefault();
                return;
            }
            
            // Validate file size (10MB max)
            if (file.size > 10 * 1024 * 1024) {
                toastr.error('File size must be less than 10MB');
                showDefault();
                return;
            }
            
            showSelected(file.name);
        }
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            showDefault();
        });
    }

    return {
        reset: showDefault,
        showFile: showSelected
    };
};

var KTAddNotes = function () {
    const element = document.getElementById('kt_modal_add_notes');

    if (!element) {
        console.error('Modal element not found');
        return { init: function () { } };
    }

    const form = element.querySelector('#kt_modal_add_notes_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);
    let fileHandler = null;

    var initAddNote = () => {
        // Initialize file upload handler
        fileHandler = FileUploadHandler('#add_notes_file_wrapper', '#add_notes_pdf_file');

        const cancelButton = element.querySelector('[data-kt-add-note-modal-action="cancel"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                if (fileHandler) fileHandler.reset();
                // Reset select2
                $(form).find('select[data-control="select2"]').val(null).trigger('change');
                modal.hide();
            });
        }

        const closeButton = element.querySelector('[data-kt-add-note-modal-action="close"]');
        if (closeButton) {
            closeButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                if (fileHandler) fileHandler.reset();
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
                    'sheet_subject_id': {
                        validators: {
                            notEmpty: {
                                message: 'Subject is required'
                            }
                        }
                    },
                    'notes_name': {
                        validators: {
                            notEmpty: {
                                message: 'Note name is required'
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
        );

        const submitButton = element.querySelector('[data-kt-add-note-modal-action="submit"]');
        if (submitButton && validator) {
            submitButton.addEventListener('click', function (e) {
                e.preventDefault();

                validator.validate().then(function (status) {
                    if (status == 'Valid') {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        const formData = new FormData(form);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                        fetch(`/notes`, {
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
                                    toastr.success(data.message || 'Note added successfully');
                                    modal.hide();

                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    throw new Error(data.message || 'Add failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to add note');
                                console.error('Error:', error);
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
            initAddNote();
            initValidation();
        }
    };
}();


var KTEditNotes = function () {
    const element = document.getElementById('kt_modal_edit_notes');

    if (!element) {
        return { init: function () { } };
    }

    const form = element.querySelector('#kt_modal_edit_notes_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);
    let fileHandler = null;
    let currentTopicId = null;
    let currentPdfPath = null;

    var initEditNote = () => {
        // Initialize file upload handler
        fileHandler = FileUploadHandler('#edit_notes_file_wrapper', '#edit_notes_pdf_file');

        // Handle edit button clicks
        document.querySelectorAll('.edit-note-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                currentTopicId = this.getAttribute('data-topic-id');
                const topicName = this.getAttribute('data-topic-name');
                currentPdfPath = this.getAttribute('data-topic-pdf');

                // Set form values
                document.getElementById('edit_topic_id').value = currentTopicId;
                document.getElementById('edit_topic_name').value = topicName;
                document.getElementById('remove_pdf_flag').value = '0';

                // Handle current PDF section
                const currentPdfSection = document.getElementById('current_pdf_section');
                const downloadPdfBtn = document.getElementById('download_current_pdf');
                const currentPdfName = document.getElementById('current_pdf_name');
                const editPdfLabel = document.getElementById('edit_pdf_label');

                if (currentPdfPath && currentPdfPath !== 'null' && currentPdfPath !== '') {
                    currentPdfSection.classList.remove('d-none');
                    const fileName = currentPdfPath.split('/').pop();
                    currentPdfName.textContent = fileName;
                    if (downloadPdfBtn) {
                        downloadPdfBtn.href = '/' + currentPdfPath;
                        downloadPdfBtn.setAttribute('download', fileName);
                    }
                    editPdfLabel.textContent = 'Upload New PDF (Replace existing)';
                } else {
                    currentPdfSection.classList.add('d-none');
                    editPdfLabel.textContent = 'Upload PDF';
                }

                // Reset file input
                if (fileHandler) fileHandler.reset();
            });
        });

        // Handle remove current PDF button
        const removePdfBtn = document.getElementById('remove_current_pdf');
        if (removePdfBtn) {
            removePdfBtn.addEventListener('click', function () {
                Swal.fire({
                    title: 'Remove PDF?',
                    text: 'This will remove the current PDF file when you save.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, remove it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('remove_pdf_flag').value = '1';
                        document.getElementById('current_pdf_section').classList.add('d-none');
                        document.getElementById('edit_pdf_label').textContent = 'Upload PDF';
                        toastr.info('PDF will be removed when you save');
                    }
                });
            });
        }

        // Cancel button handler
        const cancelButton = element.querySelector('[data-kt-edit-note-modal-action="cancel"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                if (fileHandler) fileHandler.reset();
                modal.hide();
            });
        }

        // Close button handler
        const closeButton = element.querySelector('[data-kt-edit-note-modal-action="close"]');
        if (closeButton) {
            closeButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                if (fileHandler) fileHandler.reset();
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
                    'topic_name': {
                        validators: {
                            notEmpty: {
                                message: 'Note name is required'
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
        );

        const submitButton = element.querySelector('[data-kt-edit-note-modal-action="submit"]');
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

                        const topicId = document.getElementById('edit_topic_id').value;
                        const url = routeUpdateNote.replace(':id', topicId);

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
                                    toastr.success(data.message || 'Note updated successfully');
                                    modal.hide();

                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    throw new Error(data.message || 'Update failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to update note');
                                console.error('Error:', error);
                            });

                    } else {
                        toastr.warning('Please fill all required fields');
                    }
                });
            });
        }
    }

    // Status toggle handler
    const handleStatusToggle = function () {
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', async function (e) {
                e.stopPropagation();
                const topicId = this.getAttribute('data-id');
                const newStatus = this.checked ? 'active' : 'inactive';
                const originalState = !this.checked;
                
                this.disabled = true;

                try {
                    const response = await fetch(`/notes/${topicId}/status`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ status: newStatus })
                    });

                    if (!response.ok) {
                        throw new Error('Status update failed');
                    }

                    const data = await response.json();
                    if (data.success) {
                        const noteCard = this.closest('.note-card');
                        const statusBadge = noteCard.querySelector('.status-badge');
                        
                        if (newStatus === 'inactive') {
                            noteCard.classList.add('note-inactive');
                        } else {
                            noteCard.classList.remove('note-inactive');
                        }
                        
                        if (statusBadge) {
                            statusBadge.classList.remove('active', 'inactive');
                            statusBadge.classList.add(newStatus);
                            statusBadge.innerHTML = `<i class="ki-outline ki-${newStatus === 'active' ? 'check-circle' : 'cross-circle'} fs-7 me-1"></i>${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}`;
                        }
                        
                        toastr.success(`Note marked as ${newStatus}`);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.checked = !originalState;
                    toastr.error('Failed to update status');
                } finally {
                    this.disabled = false;
                }
            });
        });
    };

    // Delete note handler
    const handleNoteDeletion = function () {
        document.querySelectorAll('.delete-note').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                let noteId = this.getAttribute('data-topic-id');
                let url = routeDeleteNote.replace(':id', noteId);

                Swal.fire({
                    title: "Are you sure to delete this note?",
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
                                    toastr.success('Note deleted successfully');

                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    toastr.error(data.message);
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
        });
    };

    return {
        init: function () {
            initEditNote();
            initValidation();
            handleStatusToggle();
            handleNoteDeletion();
        }
    };
}();


var KTEditSheet = function () {
    const element = document.getElementById('kt_modal_edit_sheet');

    if (!element) {
        console.error('Modal element not found');
        return { init: function () { } };
    }

    const form = element.querySelector('#kt_modal_edit_sheet_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);
    let sheetId = null;

    var initEditSheet = () => {
        const cancelButton = element.querySelector('[data-kt-sheet-modal-action="cancel"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        const closeButton = element.querySelector('[data-kt-sheet-modal-action="close"]');
        if (closeButton) {
            closeButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_sheet']");
        if (editButtons.length) {
            editButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    sheetId = this.getAttribute("data-sheet-id");
                    const sheetClass = this.getAttribute("data-sheet-class");
                    const sheetPrice = this.getAttribute("data-sheet-price");

                    if (form) form.reset();

                    const modalTitle = document.getElementById("kt_modal_edit_sheet_title");
                    if (modalTitle) {
                        modalTitle.textContent = `Update - ${sheetClass} - sheet group`;
                    }

                    const priceInput = document.querySelector("input[name='sheet_price_edit']");
                    if (priceInput) {
                        priceInput.value = sheetPrice;
                    }
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
                    'sheet_price_edit': {
                        validators: {
                            notEmpty: {
                                message: 'Price is required'
                            },
                            numeric: {
                                message: 'The value must be a number'
                            },
                            greaterThan: {
                                min: 100,
                                message: 'The price must be at least 100'
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

        const submitButton = element.querySelector('[data-kt-sheet-modal-action="submit"]');
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

                        fetch(`/sheets/${sheetId}`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => {
                                if (!response.ok) throw new Error('Network response was not ok');
                                return response.json();
                            })
                            .then(data => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (data.success) {
                                    toastr.success(data.message || 'Sheet group updated successfully');
                                    modal.hide();

                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);

                                } else {
                                    throw new Error(data.message || 'Update failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to update sheet');
                                console.error('Error:', error);
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
            initEditSheet();
            initValidation();
        }
    };
}();


// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTSheetPaymentsList.init();
    KTAddNotes.init();
    KTEditNotes.init();
    KTEditSheet.init();
});