// ajax-reference.js
$(function () {
     let $referredBySelect = $('select[name="referred_by"]');

     // Initialize Select2
     $referredBySelect.select2({
          placeholder: "Select the person",
          allowClear: true
     });

     // Function to load data via AJAX and update the select field
     function loadReferredByData(type) {
          let url = type === 'teacher' ? ajaxTeacherRoute : ajaxStudentRoute;

          $.ajax({
               url: url,
               type: 'GET',
               dataType: 'json',
               beforeSend: function () {
                    $referredBySelect.prop('disabled', true); // Disable while loading
               },
               success: function (data) {
                    $referredBySelect.empty().append(
                         '<option value="" disabled selected>Select the person</option>');

                    $.each(data, function (index, person) {
                         let displayText = person.name;
                         if (person.student_unique_id) {
                              displayText += ` (${person.student_unique_id})`;
                         }
                         $referredBySelect.append(
                              `<option value="${person.id}">${displayText}</option>`);
                    });

                    $referredBySelect.prop('disabled', false).trigger('change');
               },
               error: function (xhr, status, error) {
                    console.error("Error loading referred by data:", error);
                    alert("Failed to load data. Please try again.");
                    $referredBySelect.prop('disabled', false);
               }
          });
     }

     // Auto-load teachers on page load (since teacher radio is preselected)
     loadReferredByData('teacher');

     // Event listener for radio button changes
     $('input[name="referer_type"]').on('change', function () {
          let selectedType = $(this).val();
          loadReferredByData(selectedType);
     });
});