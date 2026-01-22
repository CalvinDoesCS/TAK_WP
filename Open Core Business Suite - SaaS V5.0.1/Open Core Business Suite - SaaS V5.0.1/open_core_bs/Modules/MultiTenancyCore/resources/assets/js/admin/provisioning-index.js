$(function () {
    // Set up AJAX defaults
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let historyTableInitialized = false;
    let historyDt = null;

    // Load statistics
    function loadStatistics() {
        $.get(pageData.urls.statistics, function(data) {
            $('#stat-pending').text(data.pending || 0);
            $('#stat-failed').text(data.failed || 0);
            $('#stat-today').text(data.provisioned_today || 0);
            $('#stat-active').text(data.total_active || 0);

            // Update badge on pending tab
            const pendingCount = (data.pending || 0) + (data.failed || 0);
            $('#badge-pending').text(pendingCount);
        });
    }

    // Initialize Pending DataTable
    const pendingDt = $('.datatables-provisioning').DataTable({
        ajax: pageData.urls.datatable,
        columns: [
            { data: 'tenant' },
            { data: 'plan' },
            { data: 'status' },
            { data: 'created_at' },
            { data: 'actions' }
        ],
        order: [[3, 'desc']],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            emptyTable: pageData.labels.noRecords
        }
    });

    // Initialize History DataTable (lazy load when tab is shown)
    function initHistoryTable() {
        if (historyTableInitialized) return;

        historyDt = $('.datatables-history').DataTable({
            ajax: pageData.urls.history,
            columns: [
                { data: 'tenant' },
                { data: 'plan' },
                { data: 'status' },
                { data: 'provisioned_at' },
                { data: 'actions' }
            ],
            order: [[3, 'desc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                emptyTable: pageData.labels.noRecords
            }
        });

        historyTableInitialized = true;
    }

    // Tab change handler - initialize history table on first view
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetId = $(e.target).data('bs-target');

        if (targetId === '#tab-history') {
            initHistoryTable();
            // Adjust columns when tab is shown
            if (historyDt) {
                historyDt.columns.adjust();
            }
        } else if (targetId === '#tab-pending') {
            pendingDt.columns.adjust();
        }
    });

    // Load initial statistics
    loadStatistics();

    // View provisioning details
    window.viewProvisioning = function(id) {
        window.location.href = pageData.urls.show.replace(':id', id);
    };

    // View tenant dashboard
    window.viewTenantDashboard = function(id) {
        window.location.href = pageData.urls.tenantDashboard.replace(':id', id);
    };

    // Refresh data
    window.refreshProvisioningData = function() {
        pendingDt.ajax.reload(null, false);
        if (historyDt) {
            historyDt.ajax.reload(null, false);
        }
        loadStatistics();
    };

    // Refresh every 30 seconds
    setInterval(function() {
        pendingDt.ajax.reload(null, false);
        loadStatistics();
    }, 30000);
});
