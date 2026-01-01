"use strict";

/**
 * Bulk Notes Distribution
 * Handles bulk distribution of sheet topics to students
 * Cascading dropdowns: Sheet Group → Subject → Topic
 */
var KTBulkNotesDistribution = function () {
      // Define shared variables
      var sheetGroupSelect;
      var subjectSelect;
      var topicSelect;
      var loadStudentsBtn;
      var studentsGrid;
      var emptyState;
      var studentsList;
      var infoBanner;
      var infoCards;
      var distributeBtn;
      var csrfToken;

      // Current state
      var currentSheetId = null;
      var currentSubjectId = null;
      var currentTopicId = null;
      var pendingStudents = [];

      /**
       * Initialize Select2 dropdowns
       */
      var initSelect2 = function () {
            // Initialize all Select2 dropdowns
            if (typeof $ !== 'undefined' && $.fn.select2) {
                  $('[data-control="select2"]').select2();
            }
      };

      /**
       * Handle Sheet Group change - Load Subjects via AJAX
       */
      var handleSheetGroupChange = function () {
            if (!sheetGroupSelect) return;

            $(sheetGroupSelect).on('change', function () {
                  var sheetId = this.value;
                  currentSheetId = sheetId;

                  // Reset Subject and Topic selects
                  resetSelect(subjectSelect, true);
                  resetSelect(topicSelect, true);
                  loadStudentsBtn.disabled = true;
                  resetStudentsView();

                  if (!sheetId) return;

                  // Disable subject while loading
                  subjectSelect.disabled = true;

                  // Fetch subjects via AJAX
                  fetch('/sheets/' + sheetId + '/subjects-list', {
                        method: 'GET',
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  })
                        .then(function (response) {
                              if (!response.ok) throw new Error('Network response was not ok');
                              return response.json();
                        })
                        .then(function (data) {
                              if (data.success && data.subjects) {
                                    populateSubjectSelect(data.subjects);
                                    subjectSelect.disabled = false;
                                    // showToast('success', data.subjects.length + ' subjects loaded');
                              }
                        })
                        .catch(function (error) {
                              console.error('Error loading subjects:', error);
                              showToast('error', 'Failed to load subjects');
                              subjectSelect.disabled = true;
                        });
            });
      };

      /**
       * Populate Subject select with data
       */
      var populateSubjectSelect = function (subjects) {
            // Clear existing options
            subjectSelect.innerHTML = '<option></option>';

            subjects.forEach(function (subject) {
                  var option = document.createElement('option');
                  option.value = subject.id;
                  option.setAttribute('data-group', subject.academic_group);

                  var groupBadge = subject.academic_group !== 'General'
                        ? ' (' + subject.academic_group + ')'
                        : '';
                  option.textContent = subject.name + groupBadge;

                  subjectSelect.appendChild(option);
            });

            // Refresh Select2
            $(subjectSelect).trigger('change');
      };

      /**
       * Handle Subject change - Load Topics via AJAX
       */
      var handleSubjectChange = function () {
            if (!subjectSelect) return;

            $(subjectSelect).on('change', function () {
                  var subjectId = this.value;
                  currentSubjectId = subjectId;

                  // Reset Topic select
                  resetSelect(topicSelect, true);
                  loadStudentsBtn.disabled = true;
                  resetStudentsView();

                  if (!currentSheetId || !subjectId) return;

                  // Disable topic while loading
                  topicSelect.disabled = true;

                  // Fetch topics via AJAX
                  fetch('/sheets/' + currentSheetId + '/subjects/' + subjectId + '/topics', {
                        method: 'GET',
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  })
                        .then(function (response) {
                              if (!response.ok) throw new Error('Network response was not ok');
                              return response.json();
                        })
                        .then(function (data) {
                              if (data.success && data.topics) {
                                    populateTopicSelect(data.topics);
                                    topicSelect.disabled = false;
                                    // showToast('success', data.topics.length + ' topics loaded');
                              }
                        })
                        .catch(function (error) {
                              console.error('Error loading topics:', error);
                              showToast('error', 'Failed to load topics');
                              topicSelect.disabled = true;
                        });
            });
      };

      /**
       * Populate Topic select with data
       */
      var populateTopicSelect = function (topics) {
            // Clear existing options
            topicSelect.innerHTML = '<option></option>';

            topics.forEach(function (topic) {
                  var option = document.createElement('option');
                  option.value = topic.id;
                  option.setAttribute('data-name', topic.name);
                  option.textContent = topic.name;

                  topicSelect.appendChild(option);
            });

            // Refresh Select2
            $(topicSelect).trigger('change');
      };

      /**
       * Handle Topic change - Enable Load Button
       */
      var handleTopicChange = function () {
            if (!topicSelect) return;

            $(topicSelect).on('change', function () {
                  var topicId = this.value;
                  currentTopicId = topicId;
                  loadStudentsBtn.disabled = !topicId;
                  resetStudentsView();
            });
      };

      /**
       * Handle Load Students button click
       */
      var handleLoadStudents = function () {
            if (!loadStudentsBtn) return;

            loadStudentsBtn.addEventListener('click', function () {
                  if (!currentSheetId || !currentSubjectId || !currentTopicId) {
                        showToast('warning', 'Please select Sheet Group, Subject, and Topic');
                        return;
                  }

                  var btn = this;
                  var originalContent = btn.innerHTML;

                  // Get display values for banner
                  var subjectText = subjectSelect.options[subjectSelect.selectedIndex].text.trim();
                  var topicText = topicSelect.options[topicSelect.selectedIndex].text.trim();
                  var sheetText = sheetGroupSelect.options[sheetGroupSelect.selectedIndex].text.trim();
                  var subjectGroup = subjectSelect.options[subjectSelect.selectedIndex].getAttribute('data-group') || 'General';

                  // Show loading state
                  btn.disabled = true;
                  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Loading...';

                  // Fetch pending students via AJAX
                  fetch('/sheets/' + currentSheetId + '/topics/' + currentTopicId + '/pending-students', {
                        method: 'GET',
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  })
                        .then(function (response) {
                              if (!response.ok) throw new Error('Network response was not ok');
                              return response.json();
                        })
                        .then(function (data) {
                              if (data.success) {
                                    // Update topic banner
                                    updateTopicBanner(data.topic, data.sheet);

                                    // Update stats
                                    updateStats(data.stats);

                                    // Store students data
                                    pendingStudents = data.students;

                                    // Render students
                                    renderStudents(data.students);

                                    showToast('success', data.students.length + ' pending students loaded');
                              }
                        })
                        .catch(function (error) {
                              console.error('Error loading students:', error);
                              showToast('error', 'Failed to load students');
                        })
                        .finally(function () {
                              btn.disabled = false;
                              btn.innerHTML = originalContent;
                        });
            });
      };

      /**
       * Update topic banner with current selection info
       */
      var updateTopicBanner = function (topic, sheet) {
            var bannerTopicName = document.getElementById('banner_topic_name');
            var bannerSubjectName = document.getElementById('banner_subject_name');
            var bannerClassName = document.getElementById('banner_class_name');
            var bannerGroupBadge = document.getElementById('banner_group_badge');

            if (bannerTopicName) bannerTopicName.textContent = topic.name;
            if (bannerSubjectName) bannerSubjectName.textContent = topic.subject;
            if (bannerClassName) bannerClassName.textContent = sheet.class_name;

            if (bannerGroupBadge) {
                  bannerGroupBadge.textContent = topic.academic_group;
                  bannerGroupBadge.className = 'badge ms-3 ' + getGroupBadgeClass(topic.academic_group);
            }

            if (infoBanner) infoBanner.classList.remove('d-none');
      };

      /**
       * Update statistics cards
       */
      var updateStats = function (stats) {
            var statTotalPaid = document.getElementById('stat_total_paid');
            var statAlreadyDistributed = document.getElementById('stat_already_distributed');
            var statPending = document.getElementById('stat_pending');
            var statSelected = document.getElementById('stat_selected');

            if (statTotalPaid) statTotalPaid.textContent = stats.total_paid;
            if (statAlreadyDistributed) statAlreadyDistributed.textContent = stats.already_distributed;
            if (statPending) statPending.textContent = stats.pending;
            if (statSelected) statSelected.textContent = '0';

            if (infoCards) infoCards.classList.remove('d-none');
      };

      /**
       * Render students grid
       */
      var renderStudents = function (students) {
            if (!studentsGrid) return;

            studentsGrid.innerHTML = '';

            if (students.length === 0) {
                  emptyState.classList.remove('d-none');
                  emptyState.querySelector('h3').textContent = 'All Students Distributed!';
                  emptyState.querySelector('p').innerHTML = 'All students who paid for this sheet have already received this topic.';
                  studentsList.classList.add('d-none');
                  return;
            }

            emptyState.classList.add('d-none');
            studentsList.classList.remove('d-none');

            students.forEach(function (student) {
                  var initials = getInitials(student.name);
                  var avatarColor = getAvatarColor(student.id);

                  var col = document.createElement('div');
                  col.className = 'col-sm-6 col-lg-4 col-xl-3';

                  col.innerHTML =
                        '<div class="student-card card card-flush h-100" data-student-id="' + student.id + '">' +
                        '<div class="card-body d-flex align-items-center p-4">' +
                        '<div class="form-check form-check-custom form-check-solid me-4">' +
                        '<input type="checkbox" class="form-check-input student-checkbox" value="' + student.id + '">' +
                        '</div>' +
                        '<div class="symbol symbol-45px symbol-circle me-4">' +
                        '<span class="symbol-label bg-light-' + avatarColor + ' text-' + avatarColor + ' fs-5 fw-bold">' +
                        initials +
                        '</span>' +
                        '</div>' +
                        '<div class="flex-grow-1 overflow-hidden">' +
                        '<div class="fw-bold text-gray-800 text-truncate">' + student.name + '</div>' +
                        '<div class="text-gray-500 fs-7">' + student.student_unique_id + '</div>' +
                        '</div>' +
                        '</div>' +
                        '</div>';

                  studentsGrid.appendChild(col);
            });

            // Bind click events to student cards
            bindStudentCardEvents();
      };

      /**
       * Bind events to student cards
       */
      var bindStudentCardEvents = function () {
            var cards = document.querySelectorAll('.student-card');

            cards.forEach(function (card) {
                  card.addEventListener('click', function (e) {
                        // Don't toggle if clicking directly on checkbox
                        if (e.target.type === 'checkbox') return;

                        this.classList.toggle('selected');
                        var checkbox = this.querySelector('.student-checkbox');
                        if (checkbox) {
                              checkbox.checked = this.classList.contains('selected');
                        }
                        updateSelectedCount();
                  });

                  // Handle checkbox change directly
                  var checkbox = card.querySelector('.student-checkbox');
                  if (checkbox) {
                        checkbox.addEventListener('change', function () {
                              card.classList.toggle('selected', this.checked);
                              updateSelectedCount();
                        });
                  }
            });
      };

      /**
       * Handle Select All button
       */
      var handleSelectAll = function () {
            var selectAllBtn = document.getElementById('bulk_select_all_btn');
            if (!selectAllBtn) return;

            selectAllBtn.addEventListener('click', function () {
                  var visibleCards = document.querySelectorAll('.student-card:not([style*="display: none"])');

                  visibleCards.forEach(function (card) {
                        // Only select visible cards (in case of filtering)
                        if (card.offsetParent !== null || card.closest('.col-sm-6').style.display !== 'none') {
                              card.classList.add('selected');
                              var checkbox = card.querySelector('.student-checkbox');
                              if (checkbox) checkbox.checked = true;
                        }
                  });

                  updateSelectedCount();
                  showToast('info', 'All visible students selected');
            });
      };

      /**
       * Handle Clear Selection button
       */
      var handleClearSelection = function () {
            var clearBtn = document.getElementById('bulk_clear_selection_btn');
            var resetBtn = document.getElementById('bulk_reset_btn');

            var clearSelection = function () {
                  var cards = document.querySelectorAll('.student-card');

                  cards.forEach(function (card) {
                        card.classList.remove('selected');
                        var checkbox = card.querySelector('.student-checkbox');
                        if (checkbox) checkbox.checked = false;
                  });

                  updateSelectedCount();
                  showToast('info', 'Selection cleared');
            };

            if (clearBtn) clearBtn.addEventListener('click', clearSelection);
            if (resetBtn) resetBtn.addEventListener('click', clearSelection);
      };

      /**
       * Handle student search
       */
      var handleStudentSearch = function () {
            var searchInput = document.getElementById('bulk_student_search');
            if (!searchInput) return;

            searchInput.addEventListener('input', function () {
                  var query = this.value.toLowerCase();
                  var cards = document.querySelectorAll('.student-card');

                  cards.forEach(function (card) {
                        var name = card.querySelector('.text-gray-800').textContent.toLowerCase();
                        var id = card.querySelector('.text-gray-500').textContent.toLowerCase();
                        var col = card.closest('.col-sm-6');

                        if (name.includes(query) || id.includes(query)) {
                              col.style.display = '';
                        } else {
                              col.style.display = 'none';
                        }
                  });
            });
      };

      /**
       * Handle Distribute button
       */
      var handleDistribute = function () {
            distributeBtn = document.getElementById('bulk_distribute_btn');
            if (!distributeBtn) return;

            distributeBtn.addEventListener('click', function () {
                  var selectedCards = document.querySelectorAll('.student-card.selected');
                  var selectedIds = [];

                  selectedCards.forEach(function (card) {
                        selectedIds.push(card.getAttribute('data-student-id'));
                  });

                  if (selectedIds.length === 0) {
                        showToast('warning', 'Please select at least one student');
                        return;
                  }

                  var btn = this;

                  // Show confirmation dialog
                  Swal.fire({
                        title: 'Confirm Distribution',
                        html: 'Are you sure you want to distribute this topic to <strong>' + selectedIds.length + '</strong> student(s)?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Distribute',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#50cd89',
                        cancelButtonColor: '#f1416c'
                  }).then(function (result) {
                        if (result.isConfirmed) {
                              performDistribution(selectedIds, btn);
                        }
                  });
            });
      };

      /**
       * Perform the distribution AJAX request
       */
      var performDistribution = function (studentIds, btn) {
            var originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Distributing...';

            fetch('/sheet-topics/bulk-distribute', {
                  method: 'POST',
                  headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                  },
                  body: JSON.stringify({
                        sheet_id: currentSheetId,
                        topic_id: currentTopicId,
                        student_ids: studentIds
                  })
            })
                  .then(function (response) {
                        return response.json().then(function (data) {
                              return { ok: response.ok, data: data };
                        });
                  })
                  .then(function (result) {
                        if (result.ok && result.data.success) {
                              showToast('success', result.data.message);

                              // Remove distributed students from grid with animation
                              studentIds.forEach(function (id) {
                                    var card = document.querySelector('.student-card[data-student-id="' + id + '"]');
                                    if (card) {
                                          var col = card.closest('.col-sm-6');
                                          col.style.transition = 'opacity 0.3s ease';
                                          col.style.opacity = '0';

                                          setTimeout(function () {
                                                col.remove();

                                                // Update stats
                                                var currentPending = parseInt(document.getElementById('stat_pending').textContent);
                                                var currentDistributed = parseInt(document.getElementById('stat_already_distributed').textContent);
                                                document.getElementById('stat_pending').textContent = currentPending - 1;
                                                document.getElementById('stat_already_distributed').textContent = currentDistributed + 1;
                                          }, 300);
                                    }
                              });

                              updateSelectedCount();

                              // Check if no students left
                              setTimeout(function () {
                                    var remainingCards = document.querySelectorAll('#bulk_students_grid .student-card');
                                    if (remainingCards.length === 0) {
                                          studentsList.classList.add('d-none');
                                          emptyState.classList.remove('d-none');
                                          emptyState.querySelector('h3').textContent = 'All Students Distributed!';
                                          emptyState.querySelector('p').innerHTML = 'All students who paid for this sheet have already received this topic.';
                                    }
                              }, 400);
                        } else {
                              showToast('error', result.data.message || 'Distribution failed');
                        }
                  })
                  .catch(function (error) {
                        console.error('Error distributing:', error);
                        showToast('error', 'Failed to distribute');
                  })
                  .finally(function () {
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                  });
      };

      /**
       * Update selected count in UI
       */
      var updateSelectedCount = function () {
            var selectedCards = document.querySelectorAll('.student-card.selected');
            var count = selectedCards.length;

            var statSelected = document.getElementById('stat_selected');
            var selectionSummary = document.getElementById('bulk_selection_summary');
            var selectedCard = document.querySelector('.topic-count.selected');

            if (statSelected) statSelected.textContent = count;
            if (selectionSummary) {
                  selectionSummary.textContent = count + ' student' + (count !== 1 ? 's' : '') + ' selected';
            }
            if (distributeBtn) distributeBtn.disabled = count === 0;

            // Add/remove pulse animation
            if (selectedCard) {
                  if (count > 0) {
                        selectedCard.classList.add('pulse-animation');
                  } else {
                        selectedCard.classList.remove('pulse-animation');
                  }
            }
      };

      /**
       * Reset a select element
       */
      var resetSelect = function (selectElement, disable) {
            if (!selectElement) return;

            selectElement.innerHTML = '<option></option>';
            selectElement.disabled = disable;

            // Refresh Select2
            $(selectElement).trigger('change');
      };

      /**
       * Reset students view to empty state
       */
      var resetStudentsView = function () {
            if (emptyState) {
                  emptyState.classList.remove('d-none');
                  emptyState.querySelector('h3').textContent = 'No Students Loaded';
                  emptyState.querySelector('p').innerHTML =
                        'Select a Sheet Group, Subject, and Topic, then click "Load Students" to view<br>' +
                        'students who have paid but not yet received this topic.';
            }

            if (studentsList) studentsList.classList.add('d-none');
            if (infoCards) infoCards.classList.add('d-none');
            if (infoBanner) infoBanner.classList.add('d-none');
            if (studentsGrid) studentsGrid.innerHTML = '';

            pendingStudents = [];
      };

      /**
       * Get badge class based on academic group
       */
      var getGroupBadgeClass = function (group) {
            var classes = {
                  'Science': 'badge-light-info',
                  'Commerce': 'badge-light-primary',
                  'Arts': 'badge-light-warning',
                  'General': 'badge-light-secondary'
            };
            return classes[group] || 'badge-light-secondary';
      };

      /**
       * Get initials from name
       */
      var getInitials = function (name) {
            if (!name) return '?';
            var parts = name.split(' ');
            if (parts.length >= 2) {
                  return (parts[0][0] + parts[1][0]).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
      };

      /**
       * Get avatar color based on ID
       */
      var getAvatarColor = function (id) {
            var colors = ['primary', 'success', 'info', 'warning', 'danger'];
            return colors[id % colors.length];
      };

      /**
       * Show toast notification
       */
      var showToast = function (type, message) {
            if (typeof toastr !== 'undefined') {
                  toastr[type](message);
            } else {
                  console.log('[' + type.toUpperCase() + '] ' + message);
            }
      };

      return {
            // Public functions
            init: function () {
                  // Get DOM elements
                  sheetGroupSelect = document.getElementById('bulk_sheet_group_select');
                  subjectSelect = document.getElementById('bulk_subject_select');
                  topicSelect = document.getElementById('bulk_sheet_topic_select');
                  loadStudentsBtn = document.getElementById('bulk_load_students_btn');
                  studentsGrid = document.getElementById('bulk_students_grid');
                  emptyState = document.getElementById('bulk_empty_state');
                  studentsList = document.getElementById('bulk_students_list');
                  infoBanner = document.getElementById('bulk_topic_banner');
                  infoCards = document.getElementById('bulk_info_cards');

                  // Get CSRF token
                  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                  csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

                  // Check if required elements exist
                  if (!sheetGroupSelect || !subjectSelect || !topicSelect) {
                        return;
                  }

                  // Initialize components
                  initSelect2();

                  // Bind event handlers
                  handleSheetGroupChange();
                  handleSubjectChange();
                  handleTopicChange();
                  handleLoadStudents();
                  handleSelectAll();
                  handleClearSelection();
                  handleStudentSearch();
                  handleDistribute();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTBulkNotesDistribution.init();
});