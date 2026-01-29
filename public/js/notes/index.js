"use strict";

/**
 * Notes Distribution Index JavaScript
 * Handles AJAX DataTable loading, search, and filters for Sheet Group, Subject, and Topic
 * Similar structure to transactions/index.js
 */

var KTNotesDistributionList = (function () {
    // Define shared variables
    var datatables = {};
    var activeDatatable = null;
    var activeBranchId = null;
    var initializedTabs = {};
    var currentSearchValue = "";
    var searchDebounceTimer = null;

    // Filter values
    var currentSheetGroupFilter = "";
    var currentSubjectFilter = "";
    var currentTopicFilter = "";

    // Get DataTable config for AJAX loading
    var getDataTableConfig = function (branchId) {
        return {
            processing: true,
            serverSide: true,
            ajax: {
                url: routeAjaxData,
                type: "GET",
                data: function (d) {
                    d.branch_id = branchId;
                    d.sheet_group_filter = currentSheetGroupFilter;
                    d.subject_filter = currentSubjectFilter;
                    d.topic_filter = currentTopicFilter;
                },
                error: function (xhr, error, thrown) {
                    console.error("DataTables AJAX error:", error, thrown);
                    toastr.error("Failed to load distributions. Please refresh the page.");
                },
            },
            columns: [
                { data: "sl", orderable: false, searchable: false },
                { data: "topic_name", name: "topic_name" },
                { data: "subject", name: "subject" },
                { data: "sheet_group", name: "sheet_group" },
                { data: "student", name: "student" },
                { data: "distributed_by", name: "distributed_by", orderable: false },
                { data: "distributed_at", name: "distributed_at" },
            ],
            order: [],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                processing:
                    '<div class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading...</div>',
                emptyTable: "No distributions found",
                zeroRecords: "No matching distributions found",
            },
            drawCallback: function () {
                KTMenu.init();
                initTooltips();
            },
        };
    };

    // Initialize tooltips
    var initTooltips = function () {
        var tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    };

    // Initialize a single datatable
    var initSingleDatatable = function (tableId, branchId) {
        var table = document.getElementById(tableId);
        if (!table) {
            return null;
        }

        var config = getDataTableConfig(branchId);
        var datatable = $(table).DataTable(config);

        return datatable;
    };

    // Initialize datatables for admin (multiple tabs)
    var initAdminDatatables = function () {
        if (branchIds && branchIds.length > 0) {
            var firstBranchId = branchIds[0];
            var firstTableId = "kt_notes_distribution_table_branch_" + firstBranchId;

            datatables[firstBranchId] = initSingleDatatable(firstTableId, firstBranchId);
            activeDatatable = datatables[firstBranchId];
            activeBranchId = firstBranchId;
            initializedTabs[firstBranchId] = true;
        }

        // Setup tab change event listener for lazy loading
        var tabLinks = document.querySelectorAll(
            '#distributionBranchTabs a[data-bs-toggle="tab"]'
        );
        tabLinks.forEach(function (tabLink) {
            tabLink.addEventListener("shown.bs.tab", function (event) {
                var branchId = event.target.getAttribute("data-branch-id");
                var tableId = "kt_notes_distribution_table_branch_" + branchId;

                activeBranchId = branchId;

                // Initialize datatable for this tab if not already done
                if (!initializedTabs[branchId]) {
                    datatables[branchId] = initSingleDatatable(tableId, branchId);
                    initializedTabs[branchId] = true;
                }

                // Set active datatable
                activeDatatable = datatables[branchId];

                // Adjust columns for responsive display
                if (activeDatatable) {
                    activeDatatable.columns.adjust().draw(false);
                }
            });
        });
    };

    // Initialize datatable for non-admin (single table)
    var initNonAdminDatatable = function () {
        var table = document.getElementById("kt_notes_distribution_table");
        if (!table) {
            return;
        }

        var branchId = table.getAttribute("data-branch-id") || "";
        datatables["single"] = initSingleDatatable("kt_notes_distribution_table", branchId);
        activeDatatable = datatables["single"];
        activeBranchId = branchId;
    };

    // Search Handler with debounce
    var handleSearch = function () {
        const filterSearch = document.querySelector(
            '[data-kt-notes-distribution-table-filter="search"]'
        );
        if (!filterSearch) return;

        filterSearch.addEventListener("keyup", function (e) {
            clearTimeout(searchDebounceTimer);
            currentSearchValue = e.target.value;

            searchDebounceTimer = setTimeout(function () {
                if (activeDatatable) {
                    activeDatatable.search(currentSearchValue).draw();
                }
            }, 400);
        });
    };

    // Handle Sheet Group change to load Subjects via AJAX
    var handleSheetGroupChange = function () {
        const sheetGroupSelect = document.getElementById("filter_sheet_group");
        const subjectSelect = document.getElementById("filter_subject");
        const topicSelect = document.getElementById("filter_topic");

        if (!sheetGroupSelect || !subjectSelect || !topicSelect) return;

        $(sheetGroupSelect).on("change", function () {
            const sheetId = $(this).val();

            // Reset subject and topic selects
            $(subjectSelect)
                .empty()
                .append("<option></option>")
                .prop("disabled", true)
                .trigger("change");
            $(topicSelect)
                .empty()
                .append("<option></option>")
                .prop("disabled", true)
                .trigger("change");

            // Reset filter values
            currentSubjectFilter = "";
            currentTopicFilter = "";

            if (!sheetId) return;

            // Fetch subjects via AJAX
            $.ajax({
                url: `/sheets/${sheetId}/subjects-list`,
                method: "GET",
                beforeSend: function () {
                    $(subjectSelect).prop("disabled", true);
                },
                success: function (response) {
                    if (response.success && response.subjects) {
                        response.subjects.forEach(function (subject) {
                            const groupBadge =
                                subject.academic_group && subject.academic_group !== "General"
                                    ? ` (${subject.academic_group})`
                                    : "";
                            $(subjectSelect).append(
                                `<option value="${subject.id}">${subject.name}${groupBadge}</option>`
                            );
                        });
                        $(subjectSelect).prop("disabled", false);
                    }
                },
                error: function (xhr) {
                    console.error("Failed to load subjects:", xhr);
                    toastr.error("Failed to load subjects");
                },
                complete: function () {
                    if (!sheetId) {
                        $(subjectSelect).prop("disabled", true);
                    }
                },
            });
        });
    };

    // Handle Subject change to load Topics via AJAX
    var handleSubjectChange = function () {
        const sheetGroupSelect = document.getElementById("filter_sheet_group");
        const subjectSelect = document.getElementById("filter_subject");
        const topicSelect = document.getElementById("filter_topic");

        if (!subjectSelect || !topicSelect) return;

        $(subjectSelect).on("change", function () {
            const sheetId = $(sheetGroupSelect).val();
            const subjectId = $(this).val();

            // Reset topic select
            $(topicSelect)
                .empty()
                .append("<option></option>")
                .prop("disabled", true)
                .trigger("change");

            // Reset topic filter
            currentTopicFilter = "";

            if (!sheetId || !subjectId) return;

            // Fetch topics via AJAX
            $.ajax({
                url: `/sheets/${sheetId}/subjects/${subjectId}/topics`,
                method: "GET",
                beforeSend: function () {
                    $(topicSelect).prop("disabled", true);
                },
                success: function (response) {
                    if (response.success && response.topics) {
                        response.topics.forEach(function (topic) {
                            // Handle both 'name' and 'topic_name' field names
                            const topicName = topic.name || topic.topic_name || '';
                            $(topicSelect).append(
                                `<option value="${topic.id}">${topicName}</option>`
                            );
                        });
                        $(topicSelect).prop("disabled", false);
                    }
                },
                error: function (xhr) {
                    console.error("Failed to load topics:", xhr);
                    toastr.error("Failed to load topics");
                },
                complete: function () {
                    if (!subjectId) {
                        $(topicSelect).prop("disabled", true);
                    }
                },
            });
        });
    };

    // Filter Handler
    var handleFilter = function () {
        const filterForm = document.querySelector(
            '[data-kt-notes-distribution-table-filter="form"]'
        );
        if (!filterForm) return;

        const filterButton = filterForm.querySelector(
            '[data-kt-notes-distribution-table-filter="filter"]'
        );
        const resetButton = filterForm.querySelector(
            '[data-kt-notes-distribution-table-filter="reset"]'
        );
        const sheetGroupSelect = document.getElementById("filter_sheet_group");
        const subjectSelect = document.getElementById("filter_subject");
        const topicSelect = document.getElementById("filter_topic");

        // Filter datatable on submit
        if (filterButton) {
            filterButton.addEventListener("click", function () {
                // Get filter values (use IDs for server-side filtering)
                currentSheetGroupFilter = sheetGroupSelect ? sheetGroupSelect.value : "";
                currentSubjectFilter = subjectSelect ? subjectSelect.value : "";
                currentTopicFilter = topicSelect ? topicSelect.value : "";

                // Reload the datatable with new filter values
                if (activeDatatable) {
                    activeDatatable.ajax.reload();
                }
            });
        }

        // Reset datatable
        if (resetButton) {
            resetButton.addEventListener("click", function () {
                // Reset all select2 dropdowns
                $(sheetGroupSelect).val(null).trigger("change");
                $(subjectSelect)
                    .empty()
                    .append("<option></option>")
                    .prop("disabled", true)
                    .trigger("change");
                $(topicSelect)
                    .empty()
                    .append("<option></option>")
                    .prop("disabled", true)
                    .trigger("change");

                // Reset filter values
                currentSheetGroupFilter = "";
                currentSubjectFilter = "";
                currentTopicFilter = "";

                // Reload the datatable
                if (activeDatatable) {
                    activeDatatable.ajax.reload();
                }
            });
        }
    };

    // Export handlers using SheetJS and jsPDF
    var handleExport = function () {
        const exportItems = document.querySelectorAll(
            "#kt_notes_export_dropdown_menu [data-row-export]"
        );

        exportItems.forEach((exportItem) => {
            exportItem.addEventListener("click", function (e) {
                e.preventDefault();

                const exportType = this.getAttribute("data-row-export");
                const exportBtn = document.getElementById("export_dropdown_btn");

                // Show loading state
                if (exportBtn) {
                    exportBtn.classList.add("export-loading");
                    exportBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Exporting...';
                }

                // Fetch all data for export
                fetchExportData()
                    .then((data) => {
                        switch (exportType) {
                            case "copy":
                                copyToClipboard(data);
                                break;
                            case "excel":
                                exportToExcel(data);
                                break;
                            case "csv":
                                exportToCSV(data);
                                break;
                            case "pdf":
                                exportToPDF(data);
                                break;
                        }
                    })
                    .catch((error) => {
                        console.error("Export error:", error);
                        toastr.error("Failed to export data. Please try again.");
                    })
                    .finally(() => {
                        // Reset button state
                        if (exportBtn) {
                            exportBtn.classList.remove("export-loading");
                            exportBtn.innerHTML =
                                '<i class="ki-outline ki-exit-up fs-2"></i>Export';
                        }
                    });
            });
        });
    };

    // Fetch export data from server
    var fetchExportData = function () {
        return new Promise((resolve, reject) => {
            const params = new URLSearchParams({
                branch_id: activeBranchId || "",
                search: currentSearchValue,
                sheet_group_filter: currentSheetGroupFilter,
                subject_filter: currentSubjectFilter,
                topic_filter: currentTopicFilter,
            });

            fetch(`${routeExportData}?${params.toString()}`, {
                method: "GET",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
            })
                .then((response) => {
                    if (!response.ok) throw new Error("Network response was not ok");
                    return response.json();
                })
                .then((data) => resolve(data.data))
                .catch((error) => reject(error));
        });
    };

    // Copy to clipboard
    var copyToClipboard = function (data) {
        const headers = [
            "SL",
            "Topic Name",
            "Subject",
            "Sheet Group",
            "Student",
            "Distributed By",
            "Distributed At",
        ];

        let text = headers.join("\t") + "\n";

        data.forEach((row) => {
            text +=
                [
                    row.sl,
                    row.topic_name,
                    row.subject,
                    row.sheet_group,
                    row.student,
                    row.distributed_by,
                    row.distributed_at,
                ].join("\t") + "\n";
        });

        navigator.clipboard
            .writeText(text)
            .then(() => {
                toastr.success("Data copied to clipboard!");
            })
            .catch((err) => {
                console.error("Copy failed:", err);
                // Fallback for older browsers
                const textarea = document.createElement("textarea");
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand("copy");
                document.body.removeChild(textarea);
                toastr.success("Data copied to clipboard!");
            });
    };

    // Export to Excel using SheetJS
    var exportToExcel = function (data) {
        const headers = [
            "SL",
            "Topic Name",
            "Subject",
            "Sheet Group",
            "Student",
            "Distributed By",
            "Distributed At",
        ];

        const wsData = [headers];

        data.forEach((row) => {
            wsData.push([
                row.sl,
                row.topic_name,
                row.subject,
                row.sheet_group,
                row.student,
                row.distributed_by,
                row.distributed_at,
            ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Notes Distribution");

        // Set column widths
        ws["!cols"] = [
            { wch: 5 },
            { wch: 30 },
            { wch: 20 },
            { wch: 25 },
            { wch: 35 },
            { wch: 15 },
            { wch: 20 },
        ];

        const fileName = `Notes_Distribution_Report_${new Date().toISOString().slice(0, 10)}.xlsx`;
        XLSX.writeFile(wb, fileName);
        toastr.success("Excel file downloaded successfully!");
    };

    // Export to CSV using SheetJS
    var exportToCSV = function (data) {
        const headers = [
            "SL",
            "Topic Name",
            "Subject",
            "Sheet Group",
            "Student",
            "Distributed By",
            "Distributed At",
        ];

        const wsData = [headers];

        data.forEach((row) => {
            wsData.push([
                row.sl,
                row.topic_name,
                row.subject,
                row.sheet_group,
                row.student,
                row.distributed_by,
                row.distributed_at,
            ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const csv = XLSX.utils.sheet_to_csv(ws);

        const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        const fileName = `Notes_Distribution_Report_${new Date().toISOString().slice(0, 10)}.csv`;

        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.click();
        URL.revokeObjectURL(link.href);

        toastr.success("CSV file downloaded successfully!");
    };

    // Export to PDF using jsPDF
    var exportToPDF = function (data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF("l", "mm", "a4"); // Landscape orientation

        // A4 landscape: 297mm x 210mm
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const marginLeft = 14;
        const marginRight = 14;
        const usableWidth = pageWidth - marginLeft - marginRight; // ~269mm

        const headers = [
            [
                "SL",
                "Topic Name",
                "Subject",
                "Sheet Group",
                "Student",
                "Distributed By",
                "Distributed At",
            ],
        ];

        const rows = data.map((row) => [
            row.sl,
            row.topic_name,
            row.subject,
            row.sheet_group,
            row.student,
            row.distributed_by,
            row.distributed_at,
        ]);

        // Title
        doc.setFontSize(16);
        doc.text("Notes Distribution Report", marginLeft, 15);

        // Date
        doc.setFontSize(10);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, marginLeft, 22);

        // Calculate proportional column widths to fill usable width
        // Proportions: SL=1, Topic=4, Subject=3, Sheet=4, Student=5, By=2.5, At=3 = 22.5 total
        const totalParts = 22.5;
        const unit = usableWidth / totalParts;

        // Table with equal margins
        doc.autoTable({
            head: headers,
            body: rows,
            startY: 28,
            tableWidth: usableWidth,
            styles: {
                fontSize: 8,
                cellPadding: 2,
                overflow: "linebreak",
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: "bold",
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245],
            },
            columnStyles: {
                0: { cellWidth: Math.floor(unit * 1) },      // SL: ~12mm
                1: { cellWidth: Math.floor(unit * 4) },      // Topic Name: ~48mm
                2: { cellWidth: Math.floor(unit * 3) },      // Subject: ~36mm
                3: { cellWidth: Math.floor(unit * 4) },      // Sheet Group: ~48mm
                4: { cellWidth: Math.floor(unit * 5) },      // Student: ~60mm
                5: { cellWidth: Math.floor(unit * 2.5) },    // Distributed By: ~30mm
                6: { cellWidth: "auto" },                     // Distributed At: fill remaining
            },
            margin: { top: 28, left: marginLeft, right: marginRight, bottom: 15 },
            didDrawPage: function (data) {
                // Footer - page number on right side with proper margin
                doc.setFontSize(8);
                const pageText = `Page ${doc.internal.getNumberOfPages()}`;
                const textWidth = doc.getTextWidth(pageText);
                doc.text(pageText, pageWidth - marginRight - textWidth, pageHeight - 10);
            },
        });

        const fileName = `Notes_Distribution_Report_${new Date().toISOString().slice(0, 10)}.pdf`;
        doc.save(fileName);
        toastr.success("PDF file downloaded successfully!");
    };

    // Refresh current datatable (can be called after creating new distribution)
    var refreshTable = function () {
        if (activeDatatable) {
            activeDatatable.ajax.reload(null, false);
        }
    };

    // Refresh all initialized datatables
    var refreshAllTables = function () {
        Object.keys(datatables).forEach(function (key) {
            if (datatables[key]) {
                datatables[key].ajax.reload(null, false);
            }
        });
    };

    return {
        init: function () {
            // Check if admin or non-admin based on the presence of tabs
            if (typeof isAdmin !== "undefined" && isAdmin) {
                initAdminDatatables();
            } else {
                initNonAdminDatatable();
            }

            handleSearch();
            handleSheetGroupChange();
            handleSubjectChange();
            handleFilter();
            handleExport();
        },

        getActiveDatatable: function () {
            return activeDatatable;
        },

        refreshTable: refreshTable,
        refreshAllTables: refreshAllTables,
    };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTNotesDistributionList.init();
});