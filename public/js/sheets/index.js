"use strict";

// Sheet Cards Manager
var KTSheetsCards = (function () {
      // Private variables
      let currentView = 'grid';
      let currentSort = 'recent';
      let searchQuery = '';
      let filteredSheets = [...sheetsData];

      // DOM Elements
      let sheetsContainer;
      let searchInput;
      let emptyState;
      let noResults;
      let activeFilters;
      let resultsCount;
      let clearFilters;
      let clearSearchBtn;

      // Debounce utility
      const debounce = (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                  const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                  };
                  clearTimeout(timeout);
                  timeout = setTimeout(later, wait);
            };
      };

      // Initialize DOM elements
      const initElements = () => {
            sheetsContainer = document.getElementById('sheets-container');
            searchInput = document.getElementById('sheet-search-input');
            emptyState = document.getElementById('empty-state');
            noResults = document.getElementById('no-results');
            activeFilters = document.getElementById('active-filters');
            resultsCount = document.getElementById('results-count');
            clearFilters = document.getElementById('clear-filters');
            clearSearchBtn = document.getElementById('clear-search-btn');
      };

      // Create card HTML for grid view
      const createGridCard = (sheet, index) => {
            const delay = index * 50;
            const showRoute = routeSheetShow.replace(':id', sheet.id);

            let actionsHtml = '';
            if (canEditSheet) {
                  actionsHtml = `
                <button class="btn btn-icon btn-sm btn-light-primary edit-sheet-btn"
                        data-sheet-id="${sheet.id}"
                        data-sheet-class="${sheet.className} (${sheet.classNumeral})"
                        data-sheet-price="${sheet.price}"
                        data-bs-toggle="tooltip"
                        title="Edit Price">
                    <i class="ki-outline ki-pencil fs-5"></i>
                </button>
            `;
            }

            return `
            <div class="col-md-6 col-lg-4 col-xxl-3" style="animation: fadeIn 0.3s ease-out ${delay}ms both">
                <div class="card card-flush card-bordered hover-elevate-up h-100">
                    <div class="card-header pt-6 pb-0 border-0">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-50px symbol-circle">
                                <div class="symbol-label fs-3 fw-bold bg-light-primary text-primary">
                                    ${sheet.classNumeral}
                                </div>
                            </div>
                            <div class="d-flex flex-column">
                                <a href="${showRoute}" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-0">
                                    ${sheet.className}
                                </a>
                                <span class="text-gray-500 fs-7">Sheet Group</span>
                            </div>
                        </div>
                        <div class="card-toolbar">
                            ${actionsHtml}
                        </div>
                    </div>
                    <div class="card-body pt-5 pb-4">
                        <div class="d-flex align-items-center justify-content-between border-top border-gray-200 pt-4">
                            <div>
                                <span class="text-gray-500 fs-8 fw-semibold d-block mb-1">Price</span>
                                <span class="fs-4 fw-bold text-success">৳${sheet.price.toLocaleString()}</span>
                            </div>
                            <div class="text-end">
                                <span class="text-gray-500 fs-8 fw-semibold d-block mb-1">Sales</span>
                                <span class="badge badge-light-info fs-7 fw-bold">${sheet.salesCount}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer py-4 border-top border-gray-200">
                        <a href="${showRoute}" class="btn btn-sm btn-light-primary w-100">
                            <span class="me-1">View Details</span>
                            <i class="ki-outline ki-arrow-right fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
        `;
      };

      // Create card HTML for list view
      const createListCard = (sheet, index) => {
            const delay = index * 30;
            const showRoute = routeSheetShow.replace(':id', sheet.id);

            let actionsHtml = '';
            if (canEditSheet) {
                  actionsHtml = `
                <button class="btn btn-icon btn-sm btn-light-primary me-2 edit-sheet-btn"
                        data-sheet-id="${sheet.id}"
                        data-sheet-class="${sheet.className} (${sheet.classNumeral})"
                        data-sheet-price="${sheet.price}"
                        data-bs-toggle="tooltip"
                        title="Edit Price">
                    <i class="ki-outline ki-pencil fs-5"></i>
                </button>
            `;
            }

            return `
            <div class="col-12" style="animation: fadeIn 0.3s ease-out ${delay}ms both">
                <div class="card card-flush card-bordered hover-elevate-up">
                    <div class="card-body py-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="d-flex align-items-center gap-4">
                                <div class="symbol symbol-45px symbol-circle flex-shrink-0">
                                    <div class="symbol-label fs-4 fw-bold bg-light-primary text-primary">
                                        ${sheet.classNumeral}
                                    </div>
                                </div>
                                <div>
                                    <a href="${showRoute}" class="fs-5 fw-bold text-gray-900 text-hover-primary mb-0">
                                        ${sheet.className}
                                    </a>
                                    <span class="text-gray-500 fs-7 d-block">Sheet Group</span>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center gap-6">
                                <div class="text-center d-none d-md-block" style="min-width: 80px;">
                                    <span class="text-gray-500 fs-8 fw-semibold d-block mb-1">Price</span>
                                    <span class="fs-5 fw-bold text-success">৳${sheet.price.toLocaleString()}</span>
                                </div>
                                <div class="text-center d-none d-md-block" style="min-width: 60px;">
                                    <span class="text-gray-500 fs-8 fw-semibold d-block mb-1">Sales</span>
                                    <span class="badge badge-light-info fs-7 fw-bold">${sheet.salesCount}</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    ${actionsHtml}
                                    <a href="${showRoute}" class="btn btn-icon btn-sm btn-light-primary" data-bs-toggle="tooltip" title="View Details">
                                        <i class="ki-outline ki-arrow-right fs-5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
      };

      // Render sheets
      const renderSheets = () => {
            if (!sheetsContainer) return;

            // Show empty state if no data at all
            if (sheetsData.length === 0) {
                  sheetsContainer.classList.add('d-none');
                  noResults.classList.add('d-none');
                  emptyState.classList.remove('d-none');
                  return;
            }

            // Show no results if filtered list is empty
            if (filteredSheets.length === 0) {
                  sheetsContainer.classList.add('d-none');
                  emptyState.classList.add('d-none');
                  noResults.classList.remove('d-none');
                  document.getElementById('no-results-text').textContent = `No sheet groups match "${searchQuery}"`;
                  return;
            }

            // Show sheets
            sheetsContainer.classList.remove('d-none');
            emptyState.classList.add('d-none');
            noResults.classList.add('d-none');

            const cardsHTML = filteredSheets.map((sheet, index) => {
                  return currentView === 'grid' ? createGridCard(sheet, index) : createListCard(sheet, index);
            }).join('');

            sheetsContainer.innerHTML = cardsHTML;

            // Update active filters display
            if (searchQuery) {
                  activeFilters.classList.remove('d-none');
                  activeFilters.classList.add('d-flex');
                  resultsCount.textContent = `${filteredSheets.length} of ${sheetsData.length} results for "${searchQuery}"`;
            } else {
                  activeFilters.classList.add('d-none');
                  activeFilters.classList.remove('d-flex');
            }

            // Re-attach edit button event listeners
            initEditButtonListeners();

            // Reinitialize tooltips
            initTooltips();
      };

      // Initialize tooltips
      const initTooltips = () => {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                  return new bootstrap.Tooltip(tooltipTriggerEl);
            });
      };

      // Initialize edit button listeners
      const initEditButtonListeners = () => {
            document.querySelectorAll('.edit-sheet-btn').forEach(btn => {
                  btn.addEventListener('click', function (e) {
                        e.preventDefault();

                        // Hide any existing tooltips
                        const tooltip = bootstrap.Tooltip.getInstance(this);
                        if (tooltip) tooltip.hide();

                        const sheetId = this.getAttribute('data-sheet-id');
                        const sheetClass = this.getAttribute('data-sheet-class');
                        const sheetPrice = this.getAttribute('data-sheet-price');

                        // Set modal values
                        document.getElementById('edit_sheet_id').value = sheetId;
                        document.getElementById('edit_class_name').value = sheetClass;
                        document.getElementById('edit_sheet_price').value = sheetPrice;
                        document.getElementById('kt_modal_edit_sheet_title').textContent = `Update - ${sheetClass}`;

                        // Show modal
                        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('kt_modal_edit_sheet'));
                        modal.show();
                  });
            });
      };

      // Handle search
      const handleSearch = (e) => {
            searchQuery = e.target.value.trim().toLowerCase();
            filterAndSort();
      };

      // Handle sort
      const handleSort = (sortType) => {
            currentSort = sortType;

            // Update UI - remove active from all
            document.querySelectorAll('.sort-option').forEach(btn => {
                  btn.classList.remove('active', 'bg-light-primary');
            });

            // Add active to selected
            const activeOption = document.querySelector(`.sort-option[data-sort="${sortType}"]`);
            if (activeOption) {
                  activeOption.classList.add('active', 'bg-light-primary');
            }

            const labels = {
                  'name-asc': 'Name A-Z',
                  'name-desc': 'Name Z-A',
                  'price-asc': 'Price ↑',
                  'price-desc': 'Price ↓',
                  'sales-desc': 'Most Sales',
                  'recent': 'Recent'
            };
            document.getElementById('sort-label').textContent = labels[sortType] || 'Sort by';

            filterAndSort();
      };

      // Handle view toggle
      const handleViewToggle = (view) => {
            currentView = view;

            document.querySelectorAll('.view-toggle-btn').forEach(btn => {
                  if (btn.dataset.view === view) {
                        btn.classList.remove('btn-light');
                        btn.classList.add('btn-light-primary', 'active');
                  } else {
                        btn.classList.remove('btn-light-primary', 'active');
                        btn.classList.add('btn-light');
                  }
            });

            // Container is always a row, renderSheets will handle column classes
            renderSheets();
      };

      // Filter and sort sheets
      const filterAndSort = () => {
            // Filter
            filteredSheets = sheetsData.filter(sheet => {
                  if (!searchQuery) return true;
                  return sheet.className.toLowerCase().includes(searchQuery) ||
                        sheet.classNumeral.toLowerCase().includes(searchQuery);
            });

            // Sort
            filteredSheets.sort((a, b) => {
                  switch (currentSort) {
                        case 'name-asc':
                              return a.className.localeCompare(b.className);
                        case 'name-desc':
                              return b.className.localeCompare(a.className);
                        case 'price-asc':
                              return a.price - b.price;
                        case 'price-desc':
                              return b.price - a.price;
                        case 'sales-desc':
                              return b.salesCount - a.salesCount;
                        case 'recent':
                        default:
                              return new Date(b.createdAt) - new Date(a.createdAt);
                  }
            });

            renderSheets();
      };

      // Clear all filters
      const clearAllFilters = () => {
            searchQuery = '';
            if (searchInput) searchInput.value = '';
            currentSort = 'recent';
            document.getElementById('sort-label').textContent = 'Sort by';
            document.querySelectorAll('.sort-option').forEach(btn => {
                  btn.classList.remove('active', 'bg-light-primary');
            });
            filteredSheets = [...sheetsData];
            renderSheets();
      };

      // Initialize event listeners
      const initEventListeners = () => {
            // Search with debounce
            if (searchInput) {
                  searchInput.addEventListener('input', debounce(handleSearch, 300));
            }

            // Sort options
            document.querySelectorAll('.sort-option').forEach(btn => {
                  btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        handleSort(this.dataset.sort);
                  });
            });

            // View toggle
            document.querySelectorAll('.view-toggle-btn').forEach(btn => {
                  btn.addEventListener('click', function () {
                        handleViewToggle(this.dataset.view);
                  });
            });

            // Clear filters
            if (clearFilters) {
                  clearFilters.addEventListener('click', clearAllFilters);
            }
            if (clearSearchBtn) {
                  clearSearchBtn.addEventListener('click', clearAllFilters);
            }
      };

      return {
            init: function () {
                  initElements();
                  initEventListeners();
                  renderSheets();
            },

            // Public method to refresh data
            refresh: function (newData) {
                  sheetsData.length = 0;
                  sheetsData.push(...newData);
                  filteredSheets = [...sheetsData];
                  renderSheets();
            }
      };
})();


// Edit Sheet Modal Handler
var KTEditSheet = (function () {
      const modalEl = document.getElementById('kt_modal_edit_sheet');
      if (!modalEl) return { init: function () { } };

      const form = modalEl.querySelector('#kt_modal_edit_sheet_form');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      let validator;

      // Close modal function
      const closeModal = () => {
            if (form) form.reset();
            modal.hide();
      };

      // Initialize modal events
      const initModal = () => {
            // Cancel button
            const cancelButton = modalEl.querySelector('[data-kt-sheet-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        closeModal();
                  });
            }

            // Close button
            const closeButton = modalEl.querySelector('[data-kt-sheet-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        closeModal();
                  });
            }

            // Escape key
            document.addEventListener('keydown', (e) => {
                  if (e.key === 'Escape' && modalEl.classList.contains('show')) {
                        closeModal();
                  }
            });
      };

      // Initialize form validation
      const initValidation = () => {
            if (!form) return;

            validator = FormValidation.formValidation(form, {
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
                                          message: 'The price must be at least ৳100'
                                    }
                              }
                        }
                  },
                  plugins: {
                        trigger: new FormValidation.plugins.Trigger(),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: '.fv-row',
                              eleInvalidClass: 'is-invalid',
                              eleValidClass: 'is-valid'
                        })
                  }
            });

            // Submit handler
            const submitButton = modalEl.querySelector('[data-kt-sheet-modal-action="submit"]');
            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();

                        validator.validate().then(function (status) {
                              if (status === 'Valid') {
                                    // Show loading
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    // Get form data
                                    const sheetId = document.getElementById('edit_sheet_id').value;
                                    const formData = new FormData(form);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                                    formData.append('_method', 'PUT');

                                    // Submit via AJAX
                                    const url = routeSheetUpdate.replace(':id', sheetId);

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
                                                      return response.json().then(err => {
                                                            throw new Error(err.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      Swal.fire({
                                                            text: data.message || "Sheet group updated successfully!",
                                                            icon: "success",
                                                            buttonsStyling: false,
                                                            confirmButtonText: "Ok, got it!",
                                                            customClass: {
                                                                  confirmButton: "btn btn-primary"
                                                            }
                                                      }).then(function () {
                                                            closeModal();
                                                            window.location.reload();
                                                      });
                                                } else {
                                                      throw new Error(data.message || 'Update failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                Swal.fire({
                                                      text: error.message || "Failed to update sheet group. Please try again.",
                                                      icon: "error",
                                                      buttonsStyling: false,
                                                      confirmButtonText: "Ok, got it!",
                                                      customClass: {
                                                            confirmButton: "btn btn-danger"
                                                      }
                                                });
                                                console.error('Error:', error);
                                          });
                              } else {
                                    Swal.fire({
                                          text: "Please fill all required fields correctly.",
                                          icon: "warning",
                                          buttonsStyling: false,
                                          confirmButtonText: "Ok, got it!",
                                          customClass: {
                                                confirmButton: "btn btn-warning"
                                          }
                                    });
                              }
                        });
                  });
            }
      };

      return {
            init: function () {
                  initModal();
                  initValidation();
            }
      };
})();


// Initialize on DOM ready
KTUtil.onDOMContentLoaded(function () {
      KTSheetsCards.init();
      KTEditSheet.init();
});