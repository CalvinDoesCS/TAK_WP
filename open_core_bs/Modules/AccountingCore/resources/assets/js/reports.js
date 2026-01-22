/**
 * AccountingCore Reports JavaScript
 */

// Global variables
let currentReportData = null;

// Initialize on DOM ready
$(document).ready(function() {
    console.log('AccountingCore Reports: Initializing...');
    
    // Initialize form elements
    initializeFormElements();
    
    // Setup event listeners
    setupEventListeners();
    
    // Initialize with default date range
    setDefaultDateRange();
});

/**
 * Initialize form elements
 */
function initializeFormElements() {
    // Initialize date range picker
    flatpickr('#dateRange', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        maxDate: 'today',
        locale: {
            rangeSeparator: ' to '
        }
    });
    
    // Initialize category select
    $('#categoryFilter').select2({
        placeholder: pageData.labels.selectCategory || 'Select Category',
        allowClear: true,
        width: '100%'
    });
}

/**
 * Set default date range (current month)
 */
function setDefaultDateRange() {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    
    const dateRangePicker = document.querySelector('#dateRange')._flatpickr;
    if (dateRangePicker) {
        dateRangePicker.setDate([firstDay, lastDay]);
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Handle report generation
    $('#reportFilters').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
    
    // Handle report type change
    $('#reportType').on('change', function() {
        const reportType = $(this).val();
        if (reportType === 'category') {
            $('#categoryFilter').closest('.col-md-3').show();
        } else {
            $('#categoryFilter').closest('.col-md-3').hide();
            $('#categoryFilter').val('').trigger('change');
        }
    });
    
    // Print and export buttons removed as per requirements
}

/**
 * Generate report
 */
function generateReport() {
    const dateRange = $('#dateRange').val();
    if (!dateRange) {
        showError(pageData.labels.selectDateRange || 'Please select a date range');
        return;
    }
    
    const formData = {
        dateRange: dateRange,
        reportType: $('#reportType').val(),
        categoryFilter: $('#categoryFilter').val()
    };
    
    // Show loading state
    showLoading();
    
    $.ajax({
        url: pageData.urls.generate,
        type: 'POST',
        data: {
            ...formData,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 'success') {
                currentReportData = response.data;
                displayReport(response.data);
            }
        },
        error: function(xhr) {
            hideLoading();
            showError(extractErrorMessage(xhr));
        }
    });
}

/**
 * Display report
 */
function displayReport(data) {
    // Hide loading
    hideLoading();
    
    // Show cards and table
    $('#summaryCards').fadeIn();
    $('#reportCard').fadeIn();
    
    // Update summary cards
    $('#totalIncome').text(formatCurrency(data.summary.income));
    $('#totalExpenses').text(formatCurrency(data.summary.expenses));
    $('#netBalance').text(formatCurrency(data.summary.balance));
    
    // Update balance color
    const $netBalance = $('#netBalance');
    $netBalance.removeClass('text-success text-danger text-muted');
    if (data.summary.balance > 0) {
        $netBalance.addClass('text-success');
    } else if (data.summary.balance < 0) {
        $netBalance.addClass('text-danger');
    } else {
        $netBalance.addClass('text-muted');
    }
    
    // Update report title
    $('#reportTitle').text(data.title);
    
    // Build table
    buildReportTable(data);
}

/**
 * Build report table
 */
function buildReportTable(data) {
    const $thead = $('#reportTableHead');
    const $tbody = $('#reportTableBody');
    const $tfoot = $('#reportTableFoot');
    
    // Clear existing content
    $thead.empty();
    $tbody.empty();
    $tfoot.empty();
    
    // Build header with proper alignment
    let headerRow = '<tr>';
    data.columns.forEach((col, index) => {
        // Apply alignment based on column position and type
        let thClass = '';
        if (index === 0) {
            // First column (usually Date or Category) - left aligned
            thClass = '';
        } else if (col.toLowerCase().includes('amount') || col.toLowerCase().includes('income') ||
                   col.toLowerCase().includes('expense') || col.toLowerCase().includes('balance') ||
                   col.toLowerCase().includes('total') || index === data.columns.length - 1) {
            // Numeric columns - right aligned
            thClass = 'text-end';
        }
        headerRow += `<th class="${thClass}">${col}</th>`;
    });
    headerRow += '</tr>';
    $thead.html(headerRow);
    
    // Build body with consistent alignment
    if (data.rows && data.rows.length > 0) {
        data.rows.forEach(row => {
            let rowHtml = '<tr>';
            row.forEach((cell, index) => {
                let tdClass = '';
                let cellContent = cell;

                // Check if it's a numeric value
                if (typeof cell === 'number') {
                    tdClass = 'text-end';

                    // Apply color for positive/negative values in amount columns
                    if (index > 0) { // Skip first column (usually date/category)
                        if (cell > 0) {
                            tdClass += ' text-success';
                        } else if (cell < 0) {
                            tdClass += ' text-danger';
                        }
                    }
                    cellContent = formatCurrency(Math.abs(cell));
                } else if (index === 0) {
                    // First column - usually date or category name
                    tdClass = '';
                    // If it looks like a date, format it nicely
                    if (cell && cell.match(/^\d{4}-\d{2}-\d{2}/)) {
                        const date = new Date(cell);
                        cellContent = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                    }
                } else {
                    // Check if column header suggests numeric data
                    const colName = data.columns[index];
                    if (colName && (colName.toLowerCase().includes('amount') ||
                                   colName.toLowerCase().includes('income') ||
                                   colName.toLowerCase().includes('expense') ||
                                   colName.toLowerCase().includes('balance'))) {
                        tdClass = 'text-end';
                    }
                }

                rowHtml += `<td class="${tdClass}">${cellContent}</td>`;
            });
            rowHtml += '</tr>';
            $tbody.append(rowHtml);
        });
    } else {
        $tbody.html(`<tr><td colspan="${data.columns.length}" class="text-center text-muted">${pageData.labels.noData || 'No data found'}</td></tr>`);
    }
    
    // Build footer if totals exist with proper alignment
    if (data.totals && data.totals.length > 0) {
        let footerRow = '<tr class="fw-bold table-active">';
        data.totals.forEach((total, index) => {
            let tdClass = '';
            let cellContent = total;

            if (typeof total === 'number') {
                tdClass = 'text-end';
                // Apply color for positive/negative values
                if (index > 0) { // Skip first column
                    if (total > 0) {
                        tdClass += ' text-success';
                    } else if (total < 0) {
                        tdClass += ' text-danger';
                    }
                }
                cellContent = formatCurrency(Math.abs(total));
            } else if (index === 0) {
                // First column (usually "Total" label)
                tdClass = 'fw-bold';
            } else {
                // Check if column header suggests numeric data
                const colName = data.columns[index];
                if (colName && (colName.toLowerCase().includes('amount') ||
                               colName.toLowerCase().includes('income') ||
                               colName.toLowerCase().includes('expense') ||
                               colName.toLowerCase().includes('balance'))) {
                    tdClass = 'text-end';
                }
            }

            footerRow += `<td class="${tdClass}">${cellContent}</td>`;
        });
        footerRow += '</tr>';
        $tfoot.html(footerRow);
    }
}

/**
 * Show loading state
 */
function showLoading() {
    // Hide results
    $('#summaryCards').hide();
    $('#reportCard').hide();
    
    // Show loading spinner
    Swal.fire({
        title: pageData.labels.generatingReport || 'Generating Report...',
        html: pageData.labels.pleaseWait || 'Please wait while we generate your report',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Hide loading state
 */
function hideLoading() {
    Swal.close();
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    // You can customize this based on your currency settings
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Print and export functions removed as per requirements

/**
 * Extract error message from response
 */
function extractErrorMessage(xhr) {
    const response = xhr.responseJSON;
    let errorMessage = pageData.labels.errorOccurred || 'Something went wrong';
    
    if (response) {
        if (response.errors) {
            errorMessage = '';
            Object.values(response.errors).forEach(errorArray => {
                errorArray.forEach(error => {
                    errorMessage += error + '<br>';
                });
            });
        } else if (response.data && typeof response.data === 'string') {
            errorMessage = response.data;
        } else if (response.message) {
            errorMessage = response.message;
        }
    }
    
    return errorMessage;
}

/**
 * Show success message
 */
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: pageData.labels.success || 'Success',
        text: message,
        timer: 2000,
        showConfirmButton: false
    });
}

/**
 * Show error message
 */
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: pageData.labels.error || 'Error',
        html: message
    });
}

// Export functions for external use
window.AccountingCoreReports = {
    generateReport
};