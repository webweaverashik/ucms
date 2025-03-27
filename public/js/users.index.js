"use strict";

// Class definition
var KTUsersList = function () {
     // Define shared variables
     var table = document.getElementById('kt_table_users');
     var datatable;

     // Private functions
     var initUserTable = function () {

          // Init datatable --- more info on datatables: https://datatables.net/manual/
          datatable = $(table).DataTable({
               "info": true,
               'order': [],
               "pageLength": 10,
               "lengthChange": true,
               'columnDefs': [
                    { orderable: false, targets: 6 }, // Disable ordering on column 6 (actions)                
               ]
          });

          // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
          datatable.on('draw', function () {
               // initToggleToolbar();
               // handleDeleteRows();
               // toggleToolbars();
          });
     }

     // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
     var handleSearchDatatable = () => {
          const filterSearch = document.querySelector('[data-kt-user-table-filter="search"]');
          filterSearch.addEventListener('keyup', function (e) {
               datatable.search(e.target.value).draw();
          });
     }

     return {
          // Public functions  
          init: function () {
               if (!table) {
                    return;
               }

               initUserTable();
               handleSearchDatatable();
          }
     }
}();

var KTUsersAddUser = function () {
     // Shared variables
     const element = document.getElementById('kt_modal_add_user');
     const form = element.querySelector('#kt_modal_add_user_form');
     const modal = new bootstrap.Modal(element);

     // Init add schedule modal
     var initAddUser = () => {

          // Cancel button handler
          const cancelButton = element.querySelector('[data-kt-users-modal-action="cancel"]');
          cancelButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });

          // Close button handler
          const closeButton = element.querySelector('[data-kt-users-modal-action="close"]');
          closeButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });
     }

     return {
          // Public functions
          init: function () {
               initAddUser();
          }
     };
}();

var KTUsersEditUser = function () {
     // Shared variables
     const element = document.getElementById('kt_modal_edit_user');
     const form = element.querySelector('#kt_modal_edit_user_form');
     const modal = new bootstrap.Modal(element);

     // Init add schedule modal
     var initEditUser = () => {

          // Cancel button handler
          const cancelButton = element.querySelector('[data-kt-users-modal-action="cancel"]');
          cancelButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });

          // Close button handler
          const closeButton = element.querySelector('[data-kt-users-modal-action="close"]');
          closeButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });
     }

     return {
          // Public functions
          init: function () {
               initEditUser();
          }
     };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
     KTUsersList.init();
     KTUsersAddUser.init();
     KTUsersEditUser.init();
});