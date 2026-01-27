{{-- Terminate Employee Modal --}}
<div class="modal fade" id="terminateEmployeeModal" tabindex="-1" aria-labelledby="terminateEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminateEmployeeModalLabel">
                    {{ __('Initiate Termination for') }} {{ $user->getFullName() }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="terminateEmployeeForm" action="{{ route('employees.terminate', $user->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="terminationType" class="form-label">
                                {{ __('Termination Type') }} <span class="text-danger">*</span>
                            </label>
                            <select id="terminationType" name="terminationType" class="select2 form-select" required>
                                <option value="">{{ __('Select Type') }}</option>
                                @foreach (\App\Enums\TerminationType::cases() as $type)
                                    <option value="{{ $type->value }}">
                                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $type->value)) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="isEligibleForRehire"
                                       name="isEligibleForRehire" value="1" checked>
                                <label class="form-check-label" for="isEligibleForRehire">
                                    {{ __('Eligible for Re-hire?') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="exitDate" class="form-label">
                                {{ __('Exit Date') }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="exitDate" name="exitDate"
                                   class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="lastWorkingDay" class="form-label">
                                {{ __('Last Working Day') }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="lastWorkingDay" name="lastWorkingDay"
                                   class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label for="exitReason" class="form-label">
                                {{ __('Reason') }} <span class="text-danger">*</span>
                            </label>
                            <textarea id="exitReason" name="exitReason" class="form-control" rows="3"
                                      placeholder="{{ __('Reason for termination/exit...') }}" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger" id="terminateSubmitBtn">
                        {{ __('Confirm Termination') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const terminateModalElement = document.getElementById('terminateEmployeeModal');
    const terminateForm = document.getElementById('terminateEmployeeForm');
    const terminateSubmitBtn = document.getElementById('terminateSubmitBtn');

    if (!terminateModalElement || !terminateForm || !terminateSubmitBtn) {
        return;
    }

    // Initialize Select2 for Termination Modal
    const modalSelects = terminateModalElement.querySelectorAll('.select2');
    modalSelects.forEach(select => {
        $(select).select2({
            dropdownParent: $(terminateModalElement)
        });
    });

    // Set minimum date for Last Working Day based on Exit Date
    const exitDateInput = terminateModalElement.querySelector('#exitDate');
    const lwdInput = terminateModalElement.querySelector('#lastWorkingDay');

    if (exitDateInput && lwdInput) {
        exitDateInput.addEventListener('change', function() {
            lwdInput.min = this.value;
            if (lwdInput.value && lwdInput.value < this.value) {
                lwdInput.value = this.value;
            }
        });
    }

    // Handle terminate form submission
    if (terminateForm) {
        terminateForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(terminateForm);
            const submitButton = terminateForm.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Confirmation before submitting
            Swal.fire({
                title: '{{ __('Confirm Termination?') }}',
                text: "{{ __('This action cannot be undone easily. Are you sure?') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, Confirm Termination') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function(result) {
                if (result.isConfirmed) {
                    // Disable button and show loading
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Processing...') }}';

                    fetch(terminateForm.action, {
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
                            bootstrap.Modal.getInstance(terminateModalElement).hide();
                            Swal.fire({
                                icon: 'success',
                                title: pageData.labels.success,
                                text: data.message || '{{ __('Employee termination initiated successfully') }}',
                                confirmButtonText: '{{ __('OK') }}'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: pageData.labels.error,
                                text: data.message || '{{ __('Failed to initiate termination') }}'
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
                }
            });
        });
    }

    // Reset form when modal is closed
    const terminateModal = document.getElementById('terminateEmployeeModal');
    if (terminateModal) {
        terminateModal.addEventListener('hidden.bs.modal', function() {
            terminateForm.reset();
            // Clear validation
            terminateForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            terminateForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        });
    }
});
</script>
