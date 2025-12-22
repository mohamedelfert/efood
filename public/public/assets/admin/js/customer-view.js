"use strict";

$(document).on('ready', function () {

    var datatable = $('.table').DataTable({
        "paging": false
    });

    $('#column1_search').on('keyup', function () {
        datatable
            .columns(1)
            .search(this.value)
            .draw();
    });

    $('#column3_search').on('change', function () {
        datatable
            .columns(2)
            .search(this.value)
            .draw();
    });
});

// Print QR Code
function printQRCode() {
    var printContents = document.getElementById('qrCodePrintArea').innerHTML;
    var originalContents = document.body.innerHTML;
    
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload(); // Reload to restore event listeners
}

// Generate QR Code for customer
function generateQRCode(customerId) {
    Swal.fire({
        title: 'Generate QR Code',
        text: 'Do you want to generate a QR code for this customer?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, generate it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Generating...',
                text: 'Please wait while we generate the QR code',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Make AJAX request
            $.ajax({
                url: '/admin/customer/generate-qr-code/' + customerId,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'QR code generated successfully',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Failed to generate QR code',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Regenerate QR Code
function regenerateQRCode(customerId) {
    Swal.fire({
        title: 'Regenerate QR Code',
        text: 'This will invalidate the old QR code. Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ff9800',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, regenerate it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Regenerating...',
                text: 'Please wait while we regenerate the QR code',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Make AJAX request
            $.ajax({
                url: '/admin/customer/regenerate-qr-code/' + customerId,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'QR code regenerated successfully',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Failed to regenerate QR code',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}
