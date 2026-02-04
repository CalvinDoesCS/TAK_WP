$(function () {
    // Page data
    const pageData = window.pageData;

    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Initialize Flatpickr
    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d'
    });

    // Status filter
    $('.payment_status').html(`
        <select class="form-select" id="status_filter">
            <option value="">${pageData.labels.allStatus}</option>
            <option value="pending">${pageData.labels.pending}</option>
            <option value="approved">${pageData.labels.approved}</option>
            <option value="completed">${pageData.labels.completed}</option>
            <option value="rejected">${pageData.labels.rejected}</option>
            <option value="failed">${pageData.labels.failed}</option>
            <option value="cancelled">${pageData.labels.cancelled}</option>
        </select>
    `);

    // Payment method filter
    $('.payment_method').html(`
        <select class="form-select" id="payment_method_filter">
            <option value="">${pageData.labels.allMethods}</option>
            <option value="offline">${pageData.labels.offline}</option>
            <option value="stripe">${pageData.labels.stripe}</option>
            <option value="paypal">${pageData.labels.paypal}</option>
            <option value="razorpay">${pageData.labels.razorpay}</option>
        </select>
    `);

    // Initialize DataTable
    const dt = $('.dt-payment-history').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            data: function (d) {
                const status = $('#status_filter').val();
                const paymentMethod = $('#payment_method_filter').val();
                const dateFrom = $('#date_from').val();
                const dateTo = $('#date_to').val();
                
                if (status) d.status = status;
                if (paymentMethod) d.payment_method = paymentMethod;
                if (dateFrom) d.date_from = dateFrom;
                if (dateTo) d.date_to = dateTo;
            }
        },
        columns: [
            {data: 'tenant_info', name: 'tenant_id'},
            {data: 'amount_display', name: 'amount'},
            {data: 'payment_info', name: 'payment_method'},
            {data: 'status_display', name: 'status'},
            {data: 'approved_by_display', name: 'approved_by_id'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[0, 'desc']],
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

    // Filter change events
    $('#status_filter, #payment_method_filter').on('change', function () {
        dt.ajax.reload();
    });

    $('#date_from, #date_to').on('change', function () {
        dt.ajax.reload();
    });
});