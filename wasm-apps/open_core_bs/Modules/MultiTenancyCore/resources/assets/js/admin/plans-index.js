$(function () {
    // Page data
    const pageData = window.pageData;

    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Initialize DataTable
    const dt = $('.dt-plans').DataTable({
        processing: true,
        serverSide: true,
        ajax: pageData.urls.datatable,
        columns: [
            {data: 'name_display', name: 'name'},
            {data: 'price_display', name: 'price'},
            {data: 'restrictions_summary', name: 'restrictions', orderable: false, searchable: false},
            {data: 'subscribers', name: 'subscriptions_count', searchable: false},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[0, 'asc']],
        displayLength: 25,
        lengthMenu: [10, 25, 50, 100],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end mt-n6 mt-md-0"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            paginate: {
                next: '<i class="bx bx-chevron-right bx-18px"></i>',
                previous: '<i class="bx bx-chevron-left bx-18px"></i>'
            }
        }
    });

    // Edit plan
    window.editPlan = function(id) {
        window.location.href = pageData.urls.edit.replace(':id', id);
    };

    // Delete plan
    window.deletePlan = function(id) {
        Swal.fire({
            title: pageData.labels.confirmDelete,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: pageData.labels.yesDelete,
            cancelButtonText: pageData.labels.cancel
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: pageData.urls.destroy.replace(':id', id),
                    method: 'DELETE',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: pageData.labels.success,
                                text: response.data?.message || pageData.labels.deleted,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            dt.ajax.reload();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: pageData.labels.error,
                                text: response.data?.message || response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        // Error response has message in data field (string) or data.message (object)
                        let response = xhr.responseJSON;
                        let message = response?.data?.message || response?.data || response?.message || 'An error occurred';
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: message
                        });
                    }
                });
            }
        });
    };

});