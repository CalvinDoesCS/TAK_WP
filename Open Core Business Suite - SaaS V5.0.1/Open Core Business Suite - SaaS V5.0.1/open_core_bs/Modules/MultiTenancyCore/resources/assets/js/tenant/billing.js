$(function() {
    // CSRF setup
    $.ajaxSetup({
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
    });

    // Initialize DataTable with basic settings
    $('#paymentsTable').DataTable({
        paging: false, // We're using Laravel pagination
        searching: true,
        ordering: true,
        info: false,
        order: [[0, 'desc']]
    });
});

// View payment details
window.viewPaymentDetails = function(paymentId) {
    const modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
    const contentDiv = document.getElementById('paymentDetailsContent');

    // Show loading
    contentDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">${pageData.translations.loading}</span>
            </div>
        </div>
    `;

    modal.show();

    // Fetch payment details
    const url = pageData.paymentDetailsUrl.replace(':id', paymentId);
    $.get(url, function(response) {
        if (response.status === 'success') {
            const payment = response.data;
            
            contentDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">${pageData.translations.paymentInformation}</h6>
                        <dl class="row">
                            <dt class="col-sm-5">${pageData.translations.paymentId}:</dt>
                            <dd class="col-sm-7">#${String(payment.id).padStart(6, '0')}</dd>

                            <dt class="col-sm-5">${pageData.translations.referenceNumber}:</dt>
                            <dd class="col-sm-7">${payment.reference_number || '-'}</dd>

                            <dt class="col-sm-5">${pageData.translations.amount}:</dt>
                            <dd class="col-sm-7 h5 text-primary mb-0">${payment.formatted_amount}</dd>

                            <dt class="col-sm-5">${pageData.translations.paymentMethod}:</dt>
                            <dd class="col-sm-7">${payment.payment_method.charAt(0).toUpperCase() + payment.payment_method.slice(1)}</dd>

                            <dt class="col-sm-5">${pageData.translations.status}:</dt>
                            <dd class="col-sm-7">${getStatusBadge(payment.status)}</dd>

                            <dt class="col-sm-5">${pageData.translations.createdAt}:</dt>
                            <dd class="col-sm-7">${new Date(payment.created_at).toLocaleString()}</dd>
                        </dl>
                    </div>

                    <div class="col-md-6">
                        <h6 class="mb-3">${pageData.translations.subscriptionDetails}</h6>
                        <dl class="row">
                            <dt class="col-sm-5">${pageData.translations.description}:</dt>
                            <dd class="col-sm-7">${payment.description || 'Subscription payment'}</dd>

                            ${payment.subscription ? `
                                <dt class="col-sm-5">${pageData.translations.plan}:</dt>
                                <dd class="col-sm-7">${payment.subscription.plan.name}</dd>

                                <dt class="col-sm-5">${pageData.translations.billingPeriod}:</dt>
                                <dd class="col-sm-7">${payment.subscription.plan.billing_period}</dd>
                            ` : ''}

                            ${payment.approved_at ? `
                                <dt class="col-sm-5">${pageData.translations.approvedAt}:</dt>
                                <dd class="col-sm-7">${new Date(payment.approved_at).toLocaleString()}</dd>
                            ` : ''}

                            ${payment.rejection_reason ? `
                                <dt class="col-sm-5">${pageData.translations.rejectionReason}:</dt>
                                <dd class="col-sm-7 text-danger">${payment.rejection_reason}</dd>
                            ` : ''}
                        </dl>

                        ${payment.proof_document_path ? `
                            <div class="mt-3">
                                <a href="${pageData.paymentProofUrl.replace(':id', payment.id)}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                                    <i class="bx bx-file me-1"></i>${pageData.translations.viewProof}
                                </a>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        } else {
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    ${response.message || 'Failed to load payment details'}
                </div>
            `;
        }
    }).fail(function() {
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                ${pageData.translations.errorLoading}
            </div>
        `;
    });
};

// Helper function to get status badge
function getStatusBadge(status) {
    const badges = {
        'approved': `<span class="badge bg-label-success">${pageData.translations.paid}</span>`,
        'completed': `<span class="badge bg-label-success">${pageData.translations.completed || 'Completed'}</span>`,
        'pending': `<span class="badge bg-label-warning">${pageData.translations.pending}</span>`,
        'failed': `<span class="badge bg-label-danger">${pageData.translations.failed}</span>`,
        'rejected': `<span class="badge bg-label-danger">${pageData.translations.rejected}</span>`,
        'cancelled': `<span class="badge bg-label-secondary">${pageData.translations.cancelled || 'Cancelled'}</span>`
    };

    return badges[status] || `<span class="badge bg-label-secondary">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
}