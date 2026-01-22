{{-- Suspend Employee Modal --}}
<div class="modal fade" id="suspendEmployeeModal" tabindex="-1" aria-labelledby="suspendEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="suspendEmployeeModalLabel">
                    <i class="bx bx-pause-circle me-1"></i>{{ __('Suspend Employee') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="suspendEmployeeForm" method="POST" action="{{ route('employees.suspend', $user->id) }}">
                @csrf
                <input type="hidden" name="userId" value="{{ $user->id }}">

                <div class="modal-body">
                    <div class="alert alert-warning" role="alert">
                        <i class="bx bx-info-circle me-2"></i>
                        {{ __('Suspending an employee will temporarily restrict their access to the system.') }}
                    </div>

                    <div class="mb-4">
                        <label for="suspensionDate" class="form-label">{{ __('Suspension Date') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control flatpickr-input" id="suspensionDate" name="suspensionDate"
                               placeholder="{{ __('Select suspension date') }}" required readonly>
                        <div class="form-text">{{ __('The date when the suspension takes effect') }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="suspensionDuration" class="form-label">{{ __('Duration (days)') }}</label>
                        <input type="number" class="form-control" id="suspensionDuration" name="suspensionDuration"
                               placeholder="{{ __('Leave empty for indefinite') }}" min="1">
                        <div class="form-text">{{ __('Leave empty for indefinite suspension') }}</div>
                    </div>

                    <div class="mb-4">
                        <label for="suspensionReason" class="form-label">{{ __('Reason for Suspension') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="suspensionReason" name="suspensionReason" rows="4"
                                  placeholder="{{ __('Provide a detailed reason for the suspension') }}" required></textarea>
                        <div class="form-text">{{ __('This will be recorded in the employee timeline') }}</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notifyEmployee" name="notifyEmployee" checked>
                            <label class="form-check-label" for="notifyEmployee">
                                {{ __('Notify employee via email') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-pause-circle me-1"></i>{{ __('Suspend Employee') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize flatpickr for suspension date
    const suspensionDatePicker = flatpickr("#suspensionDate", {
        dateFormat: "Y-m-d",
        minDate: "today",
        defaultDate: "today"
    });

    // Handle suspend form submission
    const suspendForm = document.getElementById('suspendEmployeeForm');
    if (suspendForm) {
        suspendForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Disable button and show loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Processing...') }}';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.labels.success,
                        text: data.message || '{{ __('Employee suspended successfully') }}',
                        confirmButtonText: '{{ __('OK') }}'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: data.message || '{{ __('Failed to suspend employee') }}'
                    });
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: '{{ __('An error occurred. Please try again.') }}'
                });
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }

    // Reset form when modal is closed
    const suspendModal = document.getElementById('suspendEmployeeModal');
    if (suspendModal) {
        suspendModal.addEventListener('hidden.bs.modal', function () {
            suspendForm.reset();
            suspensionDatePicker.setDate(new Date());
        });
    }
});

// Function to open suspend modal
function openSuspendModal() {
    const modal = new bootstrap.Modal(document.getElementById('suspendEmployeeModal'));
    modal.show();
}
</script>
