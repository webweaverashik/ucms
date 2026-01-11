"use strict";

// ============================================
// Class Cards - Search, Filter & Batch Tabs
// ============================================
var KTClassCards = (function () {

    // Handle search functionality
    const handleSearch = function () {
        const searchInput = document.getElementById("class-search-input");
        if (!searchInput) return;

        searchInput.addEventListener("keyup", function (e) {
            filterCards();
            updateActiveFilters();
        });
    };

    // Handle numeral filter
    const handleNumeralFilter = function () {
        const selectEl = document.getElementById("numeral-filter");
        if (!selectEl) return;

        $(selectEl).on("change", function () {
            filterCards();
            updateActiveFilters();
        });
    };

    // Handle batch tab clicks
    const handleBatchTabs = function () {
        document.addEventListener("click", function (e) {
            const tab = e.target.closest(".batch-tab");
            if (!tab) return;

            e.preventDefault();

            const classId = tab.dataset.classId;
            const batchKey = tab.dataset.batch;

            // Update active state for tabs in this card only
            const tabContainer = tab.closest(".batch-tabs");
            if (tabContainer) {
                tabContainer.querySelectorAll(".batch-tab").forEach((t) => {
                    t.classList.remove("active");
                });
                tab.classList.add("active");
            }

            // Get branch data from hidden JSON script
            const dataScript = document.getElementById("branch-data-" + classId);
            if (!dataScript) {
                console.error("Branch data not found for class:", classId);
                return;
            }

            try {
                const branchData = JSON.parse(dataScript.textContent);
                const data = branchData[batchKey] || branchData["all"];

                if (!data) {
                    console.error("Data not found for batch:", batchKey);
                    return;
                }

                // Update stats display
                const contentEl = document.getElementById("batch-content-" + classId);
                if (contentEl) {
                    const activeCount = contentEl.querySelector(".active-count");
                    const inactiveCount = contentEl.querySelector(".inactive-count");
                    const totalCount = contentEl.querySelector(".total-count");

                    if (activeCount) activeCount.textContent = data.active;
                    if (inactiveCount) inactiveCount.textContent = data.inactive;
                    if (totalCount) totalCount.textContent = data.total;
                }
            } catch (error) {
                console.error("Error parsing branch data:", error);
            }
        });
    };

    // Filter cards based on search and numeral filter
    const filterCards = function () {
        const searchValue = (
            document.getElementById("class-search-input")?.value || ""
        ).toLowerCase().trim();
        const numeralValue = document.getElementById("numeral-filter")?.value || "";

        ["active_classes_container", "inactive_classes_container"].forEach(
            (containerId) => {
                const container = document.getElementById(containerId);
                if (!container) return;

                const cards = container.querySelectorAll(".class-item");
                let visibleCount = 0;

                cards.forEach((card) => {
                    const name = card.getAttribute("data-name") || "";
                    const numeral = card.getAttribute("data-numeral") || "";

                    const matchesSearch =
                        !searchValue ||
                        name.includes(searchValue) ||
                        numeral.includes(searchValue);
                    const matchesNumeral = !numeralValue || numeral === numeralValue;

                    if (matchesSearch && matchesNumeral) {
                        card.classList.remove("d-none");
                        visibleCount++;
                    } else {
                        card.classList.add("d-none");
                    }
                });

                updateEmptyState(container, visibleCount);
            }
        );

        updateTabCounts();
    };

    // Handle clear filters
    const handleClearFilters = function () {
        const clearBtn = document.getElementById("clear-filters");
        if (!clearBtn) return;

        clearBtn.addEventListener("click", function (e) {
            e.preventDefault();

            // Clear search
            const searchInput = document.getElementById("class-search-input");
            if (searchInput) searchInput.value = "";

            // Clear numeral filter
            const numeralFilter = document.getElementById("numeral-filter");
            if (numeralFilter) $(numeralFilter).val(null).trigger("change");

            filterCards();
            updateActiveFilters();
        });
    };

    // Update active filters display
    const updateActiveFilters = function () {
        const filtersContainer = document.getElementById("active-filters");
        const resultsCount = document.getElementById("results-count");

        if (!filtersContainer || !resultsCount) return;

        const searchValue = (
            document.getElementById("class-search-input")?.value || ""
        ).trim();
        const numeralValue = document.getElementById("numeral-filter")?.value || "";

        const hasFilters = searchValue || numeralValue;

        if (hasFilters) {
            // Count visible cards
            let totalVisible = 0;
            ["active_classes_container", "inactive_classes_container"].forEach(
                (containerId) => {
                    const container = document.getElementById(containerId);
                    if (container) {
                        totalVisible += container.querySelectorAll(
                            ".class-item:not(.d-none)"
                        ).length;
                    }
                }
            );

            resultsCount.textContent = totalVisible + " class" + (totalVisible !== 1 ? "es" : "");
            filtersContainer.classList.add("show");
        } else {
            filtersContainer.classList.remove("show");
        }
    };

    // Update tab counts based on visible cards
    const updateTabCounts = function () {
        const activeContainer = document.getElementById("active_classes_container");
        const inactiveContainer = document.getElementById("inactive_classes_container");

        if (activeContainer) {
            const activeCount = activeContainer.querySelectorAll(
                ".class-item:not(.d-none)"
            ).length;
            const activeTabCount = document.getElementById("active-tab-count");
            if (activeTabCount) activeTabCount.textContent = activeCount;
        }

        if (inactiveContainer) {
            const inactiveCount = inactiveContainer.querySelectorAll(
                ".class-item:not(.d-none)"
            ).length;
            const inactiveTabCount = document.getElementById("inactive-tab-count");
            if (inactiveTabCount) inactiveTabCount.textContent = inactiveCount;
        }
    };

    // Update empty state visibility
    const updateEmptyState = function (container, visibleCount) {
        let emptyStateEl = container.querySelector(".empty-state-dynamic");
        const originalEmpty = container.querySelector(".empty-state-original");

        if (visibleCount === 0) {
            // Hide original empty state if exists
            if (originalEmpty) originalEmpty.classList.add("d-none");

            if (!emptyStateEl) {
                const emptyHtml =
                    '<div class="col-12 empty-state-dynamic">' +
                    '<div class="card shadow-sm">' +
                    '<div class="card-body d-flex flex-column align-items-center justify-content-center py-15">' +
                    '<div class="empty-state-icon">' +
                    '<i class="ki-outline ki-search-list fs-3x text-gray-400"></i>' +
                    '</div>' +
                    '<h3 class="text-gray-700 fw-semibold mb-2">No Results Found</h3>' +
                    '<p class="text-gray-500 mb-0">Try adjusting your search or filter criteria.</p>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
                container.insertAdjacentHTML("beforeend", emptyHtml);
            }
        } else {
            if (emptyStateEl) emptyStateEl.remove();
            if (originalEmpty) originalEmpty.classList.add("d-none");
        }
    };

    // Initialize tooltips for branch tabs
    const initTooltips = function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    };

    return {
        init: function () {
            handleSearch();
            handleNumeralFilter();
            handleBatchTabs();
            handleClearFilters();
            initTooltips();
        },
    };
})();

// ============================================
// Toggle Status Handler
// ============================================
var KTToggleStatus = (function () {
    const handleToggle = function () {
        document.addEventListener("click", function (e) {
            const toggleBtn = e.target.closest(".toggle-status-btn");
            if (!toggleBtn) return;

            e.preventDefault();

            const classId = toggleBtn.getAttribute("data-class-id");
            const currentStatus = toggleBtn.getAttribute("data-current-status");
            const newStatus = currentStatus === "active" ? "inactive" : "active";
            const actionText = newStatus === "active" ? "activate" : "deactivate";

            const url = routeToggleStatus.replace(":id", classId);

            Swal.fire({
                title: actionText.charAt(0).toUpperCase() + actionText.slice(1) + " this class?",
                text: "This class will be moved to " + newStatus + " classes.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: newStatus === "active" ? "#50cd89" : "#f1416c",
                cancelButtonColor: "#7e8299",
                confirmButtonText: "Yes, " + actionText + "!",
                cancelButtonText: "Cancel",
            }).then(function (result) {
                if (result.isConfirmed) {
                    var formData = new FormData();
                    formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);
                    formData.append("_method", "PUT");
                    formData.append("activation_status", newStatus);
                    formData.append("toggle_only", "true");

                    fetch(url, {
                        method: "POST",
                        body: formData,
                        headers: {
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        },
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                Swal.fire({
                                    title: "Success!",
                                    text: data.message || "Class has been " + (newStatus === "active" ? "activated" : "deactivated") + " successfully.",
                                    icon: "success",
                                }).then(function () {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.message || "Something went wrong.",
                                    icon: "error",
                                });
                            }
                        })
                        .catch(function (error) {
                            console.error("Fetch Error:", error);
                            Swal.fire({
                                title: "Error!",
                                text: "Server error. Please try again later.",
                                icon: "error",
                            });
                        });
                }
            });
        });
    };

    return {
        init: function () {
            handleToggle();
        },
    };
})();

// ============================================
// Delete Class Handler
// ============================================
var KTDeleteClass = (function () {
    const handleDeletion = function () {
        document.addEventListener("click", function (e) {
            const deleteBtn = e.target.closest(".class-delete-button");
            if (!deleteBtn) return;

            e.preventDefault();

            const classId = deleteBtn.getAttribute("data-class-id");
            const url = routeDeleteClass.replace(":id", classId);

            Swal.fire({
                title: "Delete this class?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#f1416c",
                cancelButtonColor: "#7e8299",
                confirmButtonText: "Yes, delete!",
                cancelButtonText: "Cancel",
            }).then(function (result) {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                        },
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                // Remove card from DOM with animation
                                var cardWrapper = deleteBtn.closest(".class-item");
                                if (cardWrapper) {
                                    cardWrapper.style.transition = "opacity 0.3s, transform 0.3s";
                                    cardWrapper.style.opacity = "0";
                                    cardWrapper.style.transform = "scale(0.9)";
                                    setTimeout(function () {
                                        cardWrapper.remove();
                                        updateTabCounts();
                                    }, 300);
                                }

                                Swal.fire({
                                    title: "Deleted!",
                                    text: data.message || "Class has been deleted successfully.",
                                    icon: "success",
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.message || "Something went wrong.",
                                    icon: "error",
                                });
                            }
                        })
                        .catch(function (error) {
                            console.error("Fetch Error:", error);
                            Swal.fire({
                                title: "Error!",
                                text: "Server error. Please try again later.",
                                icon: "error",
                            });
                        });
                }
            });
        });
    };

    const updateTabCounts = function () {
        var activeContainer = document.getElementById("active_classes_container");
        var inactiveContainer = document.getElementById("inactive_classes_container");

        if (activeContainer) {
            var count = activeContainer.querySelectorAll(".class-item").length;
            var badge = document.getElementById("active-tab-count");
            if (badge) badge.textContent = count;
        }

        if (inactiveContainer) {
            var count = inactiveContainer.querySelectorAll(".class-item").length;
            var badge = document.getElementById("inactive-tab-count");
            if (badge) badge.textContent = count;
        }
    };

    return {
        init: function () {
            handleDeletion();
        },
    };
})();

// ============================================
// Add Class Modal
// ============================================
var KTAddClassName = (function () {
    var element = document.getElementById("kt_modal_add_class");
    if (!element) return { init: function () { } };

    var form = element.querySelector("#kt_modal_add_class_form");
    var modal = new bootstrap.Modal(element);
    var validator = null;

    // Check if year prefix should be visible for given numeral
    const isYearPrefixRequired = function (numeral) {
        return ["10", "11", "12"].includes(numeral);
    };

    // Toggle year prefix visibility and grid columns
    const toggleYearPrefixVisibility = function (numeral) {
        var numeralCol = document.getElementById("class_numeral_add_col");
        var yearPrefixCol = document.getElementById("year_prefix_add_col");
        var yearPrefixHelp = document.getElementById("year_prefix_add_help");
        var yearPrefixSelect = document.getElementById("year_prefix_add_select");

        if (!numeralCol || !yearPrefixCol || !yearPrefixSelect) return;

        if (isYearPrefixRequired(numeral)) {
            // Show year prefix: numeral col-8, year prefix col-4
            numeralCol.classList.remove("col-12");
            numeralCol.classList.add("col-8");
            yearPrefixCol.classList.remove("d-none");
            if (yearPrefixHelp) yearPrefixHelp.classList.remove("d-none");
        } else {
            // Hide year prefix: numeral col-12
            numeralCol.classList.remove("col-8");
            numeralCol.classList.add("col-12");
            yearPrefixCol.classList.add("d-none");
            if (yearPrefixHelp) yearPrefixHelp.classList.add("d-none");
            $(yearPrefixSelect).val(null).trigger("change"); // Clear value when hidden
        }

        // Revalidate if validator exists
        if (validator) {
            validator.revalidateField("year_prefix_add");
        }
    };

    const resetForm = function () {
        if (form) {
            form.reset();
            $(form).find("select").val(null).trigger("change");

            // Reset grid columns
            var numeralCol = document.getElementById("class_numeral_add_col");
            var yearPrefixCol = document.getElementById("year_prefix_add_col");
            var yearPrefixHelp = document.getElementById("year_prefix_add_help");

            if (numeralCol) {
                numeralCol.classList.remove("col-8");
                numeralCol.classList.add("col-12");
            }
            if (yearPrefixCol) yearPrefixCol.classList.add("d-none");
            if (yearPrefixHelp) yearPrefixHelp.classList.add("d-none");

            // Clear validation states
            form.querySelectorAll(".is-invalid").forEach(function (el) {
                el.classList.remove("is-invalid");
            });
            form.querySelectorAll(".invalid-feedback").forEach(function (el) {
                el.remove();
            });
        }
    };

    const initAddClass = function () {
        // Cancel button
        var cancelButton = element.querySelector('[data-kt-add-class-modal-action="cancel"]');
        if (cancelButton) {
            cancelButton.addEventListener("click", function (e) {
                e.preventDefault();
                resetForm();
                modal.hide();
            });
        }

        // Close button
        var closeButton = element.querySelector('[data-kt-add-class-modal-action="close"]');
        if (closeButton) {
            closeButton.addEventListener("click", function (e) {
                e.preventDefault();
                resetForm();
                modal.hide();
            });
        }
    };

    // Handle class numeral change to show/hide year prefix
    const initNumeralChangeHandler = function () {
        var numeralSelect = document.getElementById("class_numeral_add_select");
        if (!numeralSelect) return;

        $(numeralSelect).on("change", function () {
            var selectedValue = $(this).val();
            toggleYearPrefixVisibility(selectedValue);
        });
    };

    const initValidation = function () {
        if (!form) return;

        validator = FormValidation.formValidation(form, {
            fields: {
                class_name_add: {
                    validators: {
                        notEmpty: {
                            message: "Class name is required",
                        },
                        stringLength: {
                            max: 255,
                            message: "Class name must be less than 255 characters",
                        },
                    },
                },
                class_numeral_add: {
                    validators: {
                        notEmpty: {
                            message: "Class numeral is required",
                        },
                    },
                },
                year_prefix_add: {
                    validators: {
                        callback: {
                            message: "Year prefix is required for Class 10, 11, and 12",
                            callback: function (input) {
                                var numeralSelect = document.getElementById("class_numeral_add_select");
                                var numeral = numeralSelect ? $(numeralSelect).val() : "";

                                // If numeral is 10, 11, or 12, year_prefix is required
                                if (isYearPrefixRequired(numeral)) {
                                    return input.value.trim() !== "";
                                }
                                return true;
                            },
                        },
                    },
                },
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".fv-row",
                    eleInvalidClass: "is-invalid",
                    eleValidClass: "",
                }),
            },
        });

        var submitButton = element.querySelector('[data-kt-add-class-modal-action="submit"]');

        if (submitButton && validator) {
            submitButton.addEventListener("click", function (e) {
                e.preventDefault();

                validator.validate().then(function (status) {
                    if (status === "Valid") {
                        submitButton.setAttribute("data-kt-indicator", "on");
                        submitButton.disabled = true;

                        var formData = new FormData(form);
                        formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);

                        fetch(routeStoreClass, {
                            method: "POST",
                            body: formData,
                            headers: {
                                "Accept": "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        })
                            .then(function (response) {
                                if (!response.ok) {
                                    return response.json().then(function (errorData) {
                                        throw new Error(errorData.message || "Network response was not ok");
                                    });
                                }
                                return response.json();
                            })
                            .then(function (data) {
                                submitButton.removeAttribute("data-kt-indicator");
                                submitButton.disabled = false;

                                if (data.success) {
                                    toastr.success(data.message || "Class added successfully");
                                    resetForm();
                                    modal.hide();
                                    setTimeout(function () { window.location.reload(); }, 1500);
                                } else {
                                    throw new Error(data.message || "Failed to add class");
                                }
                            })
                            .catch(function (error) {
                                submitButton.removeAttribute("data-kt-indicator");
                                submitButton.disabled = false;
                                toastr.error(error.message || "Failed to add class");
                                console.error("Error:", error);
                            });
                    } else {
                        toastr.warning("Please fill all required fields");
                    }
                });
            });
        }
    };

    return {
        init: function () {
            initAddClass();
            initNumeralChangeHandler();
            initValidation();
        },
    };
})();

// ============================================
// Edit Class Modal
// ============================================
var KTEditClassName = (function () {
    var element = document.getElementById("kt_modal_edit_class");
    if (!element) return { init: function () { } };

    var form = element.querySelector("#kt_modal_edit_class_form");
    var modal = new bootstrap.Modal(element);
    var classId = null;
    var originalNumeral = null;
    var validator = null;

    // Check if year prefix should be visible for given numeral
    const isYearPrefixRequired = function (numeral) {
        return ["10", "11", "12"].includes(numeral);
    };

    // Toggle year prefix visibility and grid columns
    const toggleYearPrefixVisibility = function (numeral, yearPrefixValue) {
        var numeralCol = document.getElementById("class_numeral_edit_col");
        var yearPrefixCol = document.getElementById("year_prefix_edit_col");
        var yearPrefixHelp = document.getElementById("year_prefix_edit_help");
        var yearPrefixSelect = document.getElementById("year_prefix_edit_select");

        if (!numeralCol || !yearPrefixCol || !yearPrefixSelect) return;

        if (isYearPrefixRequired(numeral)) {
            // Show year prefix: numeral col-8, year prefix col-4
            numeralCol.classList.remove("col-12");
            numeralCol.classList.add("col-8");
            yearPrefixCol.classList.remove("d-none");
            if (yearPrefixHelp) yearPrefixHelp.classList.remove("d-none");
            if (yearPrefixValue) {
                $(yearPrefixSelect).val(yearPrefixValue).trigger("change");
            }
        } else {
            // Hide year prefix: numeral col-12
            numeralCol.classList.remove("col-8");
            numeralCol.classList.add("col-12");
            yearPrefixCol.classList.add("d-none");
            if (yearPrefixHelp) yearPrefixHelp.classList.add("d-none");
            $(yearPrefixSelect).val(null).trigger("change");
        }
    };

    const resetForm = function () {
        if (form) {
            form.reset();
            $(form).find("select").val(null).trigger("change");

            // Reset grid columns
            var numeralCol = document.getElementById("class_numeral_edit_col");
            var yearPrefixCol = document.getElementById("year_prefix_edit_col");
            var yearPrefixHelp = document.getElementById("year_prefix_edit_help");

            if (numeralCol) {
                numeralCol.classList.remove("col-8");
                numeralCol.classList.add("col-12");
            }
            if (yearPrefixCol) yearPrefixCol.classList.add("d-none");
            if (yearPrefixHelp) yearPrefixHelp.classList.add("d-none");

            // Clear validation states
            form.querySelectorAll(".is-invalid").forEach(function (el) {
                el.classList.remove("is-invalid");
            });
            form.querySelectorAll(".invalid-feedback").forEach(function (el) {
                el.remove();
            });

            // Reset numeral select to disabled state
            var numeralSelect = form.querySelector("select[name='class_numeral_edit']");
            if (numeralSelect) {
                $(numeralSelect).prop("disabled", true).trigger("change");
            }

            // Reset notices
            var editNotice = document.getElementById("numeral_edit_notice");
            var lockedNotice = document.getElementById("numeral_locked_notice");
            if (editNotice) editNotice.classList.add("d-none");
            if (lockedNotice) lockedNotice.classList.add("d-none");
        }
    };

    // Configure numeral select based on current value
    const configureNumeralSelect = function (currentNumeral) {
        var numeralSelect = form.querySelector("select[name='class_numeral_edit']");
        var editNotice = document.getElementById("numeral_edit_notice");
        var lockedNotice = document.getElementById("numeral_locked_notice");

        if (!numeralSelect) return;

        originalNumeral = currentNumeral;

        // If current numeral is 11, allow changing to 12 only
        if (currentNumeral === "11") {
            // Enable select but limit options
            $(numeralSelect).prop("disabled", false);

            // Hide all options except 11 and 12
            $(numeralSelect).find("option").each(function () {
                var val = $(this).val();
                if (val && val !== "11" && val !== "12") {
                    $(this).prop("disabled", true);
                } else {
                    $(this).prop("disabled", false);
                }
            });

            // Show edit notice, hide locked notice
            if (editNotice) editNotice.classList.remove("d-none");
            if (lockedNotice) lockedNotice.classList.add("d-none");
        } else {
            // Disable select for all other numerals
            $(numeralSelect).prop("disabled", true);

            // Show locked notice only for class 10 and 12 (not for 04-09)
            if (currentNumeral === "10" || currentNumeral === "12") {
                if (editNotice) editNotice.classList.add("d-none");
                if (lockedNotice) lockedNotice.classList.remove("d-none");
            } else {
                // For classes 04-09, hide both notices
                if (editNotice) editNotice.classList.add("d-none");
                if (lockedNotice) lockedNotice.classList.add("d-none");
            }
        }

        // Set current value
        $(numeralSelect).val(currentNumeral).trigger("change");
    };

    // Fetch and populate modal
    const handleEditClick = function (button) {
        classId = button.getAttribute("data-class-id");
        if (!classId) return;

        resetForm();

        var url = routeGetClassData.replace(":class", classId);

        fetch(url)
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (errorData) {
                        throw new Error(errorData.message || "Network response was not ok");
                    });
                }
                return response.json();
            })
            .then(function (data) {
                if (data.success && data.data) {
                    var classData = data.data;

                    // Populate form fields
                    var nameInput = form.querySelector("input[name='class_name_edit']");
                    if (nameInput) nameInput.value = classData.class_name;

                    var descInput = form.querySelector("textarea[name='description_edit']");
                    if (descInput) descInput.value = classData.class_description || "";

                    // Configure numeral select (special handling for class 11)
                    configureNumeralSelect(classData.class_numeral);

                    // Toggle year prefix visibility and set value
                    toggleYearPrefixVisibility(classData.class_numeral, classData.year_prefix);

                    // Update modal title
                    var modalTitle = document.getElementById("kt_modal_edit_class_title");
                    if (modalTitle) {
                        modalTitle.textContent = "Edit - " + classData.class_name;
                    }

                    modal.show();
                } else {
                    throw new Error(data.message || "Invalid response data");
                }
            })
            .catch(function (error) {
                console.error("Error:", error);
                toastr.error(error.message || "Failed to load class details");
            });
    };

    // Event delegation for edit buttons
    const initEditListeners = function () {
        document.addEventListener("click", function (e) {
            var button = e.target.closest("[data-bs-target='#kt_modal_edit_class']");
            if (button) {
                e.preventDefault();
                handleEditClick(button);
            }
        });
    };

    // Cancel & Close buttons
    const initModalControls = function () {
        ["cancel", "close"].forEach(function (action) {
            var btn = element.querySelector('[data-kt-edit-class-modal-action="' + action + '"]');
            if (btn) {
                btn.addEventListener("click", function (e) {
                    e.preventDefault();
                    resetForm();
                    modal.hide();
                });
            }
        });
    };

    // Form validation & submit
    const initValidation = function () {
        if (!form) return;

        validator = FormValidation.formValidation(form, {
            fields: {
                class_name_edit: {
                    validators: {
                        notEmpty: {
                            message: "Class name is required",
                        },
                        stringLength: {
                            max: 255,
                            message: "Class name must be less than 255 characters",
                        },
                    },
                },
                year_prefix_edit: {
                    validators: {
                        callback: {
                            message: "Year prefix is required for Class 10, 11, and 12",
                            callback: function (input) {
                                var numeralSelect = document.getElementById("class_numeral_edit_select");
                                var numeral = numeralSelect ? $(numeralSelect).val() : "";

                                // If numeral is 10, 11, or 12, year_prefix is required
                                if (isYearPrefixRequired(numeral)) {
                                    return input.value.trim() !== "";
                                }
                                return true;
                            },
                        },
                    },
                },
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({
                    rowSelector: ".fv-row",
                    eleInvalidClass: "is-invalid",
                    eleValidClass: "",
                }),
            },
        });

        var submitButton = element.querySelector('[data-kt-edit-class-modal-action="submit"]');

        if (submitButton && validator) {
            submitButton.addEventListener("click", function (e) {
                e.preventDefault();

                validator.validate().then(function (status) {
                    if (status === "Valid") {
                        submitButton.setAttribute("data-kt-indicator", "on");
                        submitButton.disabled = true;

                        var formData = new FormData(form);
                        formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);
                        formData.append("_method", "PUT");

                        // Get numeral value (might be disabled, so get it directly)
                        var numeralSelect = document.getElementById("class_numeral_edit_select");
                        if (numeralSelect) {
                            var numeralValue = $(numeralSelect).val();
                            // Only include if it changed from 11 to 12
                            if (originalNumeral === "11" && numeralValue === "12") {
                                formData.append("class_numeral_edit", numeralValue);
                            }
                        }

                        var updateUrl = routeToggleStatus.replace(":id", classId);

                        fetch(updateUrl, {
                            method: "POST",
                            body: formData,
                            headers: {
                                "Accept": "application/json",
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        })
                            .then(function (response) {
                                if (!response.ok) {
                                    return response.json().then(function (errorData) {
                                        throw new Error(errorData.message || "Network response was not ok");
                                    });
                                }
                                return response.json();
                            })
                            .then(function (data) {
                                submitButton.removeAttribute("data-kt-indicator");
                                submitButton.disabled = false;

                                if (data.success) {
                                    toastr.success(data.message || "Class updated successfully");
                                    resetForm();
                                    modal.hide();
                                    setTimeout(function () { window.location.reload(); }, 1500);
                                } else {
                                    throw new Error(data.message || "Update failed");
                                }
                            })
                            .catch(function (error) {
                                submitButton.removeAttribute("data-kt-indicator");
                                submitButton.disabled = false;
                                toastr.error(error.message || "Failed to update class");
                                console.error("Error:", error);
                            });
                    } else {
                        toastr.warning("Please fill all required fields correctly");
                    }
                });
            });
        }
    };

    return {
        init: function () {
            initEditListeners();
            initModalControls();
            initValidation();
        },
    };
})();

// ============================================
// Initialize on DOM Ready
// ============================================
KTUtil.onDOMContentLoaded(function () {
    KTClassCards.init();
    KTToggleStatus.init();
    KTDeleteClass.init();
    KTAddClassName.init();
    KTEditClassName.init();
});