"use strict";

var KTInvoiceWithTransactionsList = function () {
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
                  "autoWidth": false,  // Disable auto width
                  'columnDefs': [
                        { orderable: false, targets: 8 }, // Disable ordering on column Actions                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Delete pending students
      const handleDeletion = function () {
            document.querySelectorAll('.delete-invoice').forEach(item => {
                  item.addEventListener('click', function (e) {
                        e.preventDefault();

                        let invoiceId = this.getAttribute('data-invoice-id');
                        let url = routeDeleteInvoice.replace(':id', invoiceId);  // Replace ':id' with actual invoice ID

                        Swal.fire({
                              title: "Are you sure to delete this invoice?",
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
                                                            text: "The invoice has been deleted successfully.",
                                                            icon: "success",
                                                      }).then(() => {
                                                            window.location.href = '/invoices';
                                                      });
                                                } else {
                                                      Swal.fire({
                                                            title: "Error!",
                                                            text: data.error,
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
            });
      };

      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_invoice_transactions_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleDeletion();
            }
      }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTInvoiceWithTransactionsList.init();
});