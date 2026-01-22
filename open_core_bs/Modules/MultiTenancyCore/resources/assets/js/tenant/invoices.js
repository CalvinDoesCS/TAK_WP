$(function() {
    // Initialize DataTable with basic settings
    $('#invoicesTable').DataTable({
        paging: false, // We're using Laravel pagination
        searching: true,
        ordering: true,
        info: false,
        order: [[0, 'desc']], // Sort by date descending
        language: {
            search: "Search invoices:"
        }
    });
});