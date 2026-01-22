{{-- Confirm Probation Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="confirmProbationOffcanvas" aria-labelledby="confirmProbationOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="confirmProbationOffcanvasLabel">{{ __('Confirm Probation') }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="confirmProbationForm" action="{{ route('employees.confirmProbation', $user->id) }}" method="POST">
            @csrf
            <div class="mb-3">
                <p>{{ __('Are you sure you want to confirm the successful completion of probation for') }}
                    <strong>{{ $user->getFullName() }}</strong>?</p>
            </div>
            <div class="mb-3">
                <label for="confirmRemarks" class="form-label">{{ __('Remarks') }} ({{ __('Optional') }})</label>
                <textarea class="form-control" id="confirmRemarks" name="probationRemarks" rows="4"></textarea>
                <div class="invalid-feedback"></div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-success" id="confirmProbationSubmitBtn">
                    {{ __('Confirm Completion') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Extend Probation Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="extendProbationOffcanvas" aria-labelledby="extendProbationOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="extendProbationOffcanvasLabel">{{ __('Extend Probation') }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="extendProbationForm" action="{{ route('employees.extendProbation', $user->id) }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="newProbationEndDate" class="form-label">
                    {{ __('New Probation End Date') }} <span class="text-danger">*</span>
                </label>
                <input type="text" id="newProbationEndDate" name="newProbationEndDate"
                       class="form-control" placeholder="{{ __('Select new end date') }}" required readonly>
                <div class="invalid-feedback"></div>
                <div class="form-text">{{ __('Must be after current probation end date') }}</div>
            </div>
            <div class="mb-3">
                <label for="extendReason" class="form-label">
                    {{ __('Reason for Extension') }} <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="extendReason" name="probationRemarks" rows="4" required></textarea>
                <div class="invalid-feedback"></div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-warning" id="extendProbationSubmitBtn">
                    {{ __('Extend Probation') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Fail Probation Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="failProbationOffcanvas" aria-labelledby="failProbationOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="failProbationOffcanvasLabel">{{ __('Fail Probation') }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="failProbationForm" action="{{ route('employees.failProbation', $user->id) }}" method="POST">
            @csrf
            <div class="mb-3">
                <p>{{ __('Failing probation will initiate the termination process for') }}
                    <strong>{{ $user->getFullName() }}</strong>. {{ __('Please provide a reason.') }}</p>
            </div>
            <div class="mb-3">
                <label for="failReason" class="form-label">
                    {{ __('Reason for Failure') }} <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="failReason" name="probationRemarks" rows="4" required></textarea>
                <div class="invalid-feedback"></div>
            </div>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                <small>{{ __('Note: Further termination details (exit date, etc.) might be required in the next step or via the standard termination process.') }}</small>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-danger" id="failProbationSubmitBtn">
                    {{ __('Confirm Failure') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    console.log('Initializing probation forms...');

    // Initialize Flatpickr for extend probation date picker
    const newProbationEndDateInput = document.getElementById('newProbationEndDate');
    let newProbationEndDatePicker = null;

    if (newProbationEndDateInput) {
        const currentProbationEndDate = '{{ $user->probation_end_date?->toDateString() }}';

        newProbationEndDatePicker = flatpickr(newProbationEndDateInput, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'M j, Y',
            minDate: currentProbationEndDate ? new Date(new Date(currentProbationEndDate).setDate(new Date(currentProbationEndDate).getDate() + 1)) : "today",
            defaultDate: null,
            allowInput: false
        });

        console.log('Flatpickr initialized for extend probation date');
    }

    // Reset validation helper
    function resetOffcanvasValidation(formElement) {
        if (!formElement) return;
        formElement.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        formElement.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    // Generic offcanvas form submit handler
    function handleOffcanvasFormSubmit(formElement, submitButton, offcanvasElement) {
        if (!formElement || !submitButton) {
            console.error('Form or submit button not found:', {formElement, submitButton});
            return;
        }

        console.log('Attaching submit handler to:', formElement.id);

        formElement.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted:', formElement.id);
            resetOffcanvasValidation(formElement);

            const formData = new FormData(formElement);
            const url = formElement.action;

            console.log('Submitting to URL:', url);
            console.log('Form data:', Object.fromEntries(formData.entries()));

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> {{ __('Processing...') }}';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json().then(data => ({status: response.status, body: data})))
            .then(({status, body}) => {
                console.log('Response received:', {status, body});
                if (body.success) {
                    bootstrap.Offcanvas.getInstance(offcanvasElement).hide();
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('Success!') }}',
                        text: body.message,
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    if (status === 422 && body.errors) {
                        Object.keys(body.errors).forEach(key => {
                            const inputElement = formElement.querySelector(`[name="${key}"]`);
                            if (inputElement) {
                                inputElement.classList.add('is-invalid');
                                let feedbackElement = inputElement.nextElementSibling;
                                while (feedbackElement && !feedbackElement.classList.contains('invalid-feedback')) {
                                    feedbackElement = feedbackElement.nextElementSibling;
                                }
                                if (feedbackElement) feedbackElement.textContent = body.errors[key][0];
                            }
                        });
                        const firstInvalid = formElement.querySelector('.is-invalid');
                        if (firstInvalid) firstInvalid.focus();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Error') }}',
                            text: body.message || '{{ __('An unexpected error occurred.') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error submitting form:', error);
                Swal.fire({
                    icon: 'error',
                    title: '{{ __('Error') }}',
                    text: '{{ __('An unexpected network error occurred.') }}',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            })
            .finally(() => {
                submitButton.disabled = false;
                if (formElement.id === 'confirmProbationForm') submitButton.innerHTML = '{{ __('Confirm Completion') }}';
                else if (formElement.id === 'extendProbationForm') submitButton.innerHTML = '{{ __('Extend Probation') }}';
                else if (formElement.id === 'failProbationForm') submitButton.innerHTML = '{{ __('Confirm Failure') }}';
            });
        });
    }

    // Attach submit handlers
    handleOffcanvasFormSubmit(
        document.getElementById('confirmProbationForm'),
        document.getElementById('confirmProbationSubmitBtn'),
        document.getElementById('confirmProbationOffcanvas')
    );

    handleOffcanvasFormSubmit(
        document.getElementById('extendProbationForm'),
        document.getElementById('extendProbationSubmitBtn'),
        document.getElementById('extendProbationOffcanvas')
    );

    handleOffcanvasFormSubmit(
        document.getElementById('failProbationForm'),
        document.getElementById('failProbationSubmitBtn'),
        document.getElementById('failProbationOffcanvas')
    );

    // Reset forms when offcanvas are closed
    ['confirmProbationOffcanvas', 'extendProbationOffcanvas', 'failProbationOffcanvas'].forEach(offcanvasId => {
        const offcanvasEl = document.getElementById(offcanvasId);
        if (offcanvasEl) {
            offcanvasEl.addEventListener('hidden.bs.offcanvas', function() {
                const form = this.querySelector('form');
                if (form) {
                    resetOffcanvasValidation(form);
                    form.reset();

                    // Reset date picker for extend probation
                    if (offcanvasId === 'extendProbationOffcanvas' && newProbationEndDatePicker) {
                        newProbationEndDatePicker.clear();
                    }
                }
            });
        }
    });

    console.log('Probation forms initialization complete');
});
</script>
