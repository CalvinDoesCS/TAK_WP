{{-- Generate Letter Modal --}}
<div class="modal fade" id="generateLetterModal" tabindex="-1" aria-labelledby="generateLetterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateLetterModalLabel">
                    <i class="bx bx-file-blank me-1"></i>{{ __('Generate Letter') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="generateLetterForm">
                <input type="hidden" id="letterType" name="letterType" value="">

                <div class="modal-body">
                    <div class="mb-4">
                        <label for="letterLanguage" class="form-label">{{ __('Language') }} <span class="text-danger">*</span></label>
                        <select class="form-select" id="letterLanguage" name="letterLanguage" required>
                            <option value="en">{{ __('English') }}</option>
                            <option value="ar">{{ __('Arabic') }}</option>
                        </select>
                    </div>

                    <div class="mb-4" id="effectiveDateContainer" style="display: none;">
                        <label for="effectiveDate" class="form-label">{{ __('Effective Date') }}</label>
                        <input type="text" class="form-control flatpickr-input" id="effectiveDate" name="effectiveDate"
                               placeholder="{{ __('Select effective date') }}" readonly>
                    </div>

                    <div class="mb-4">
                        <label for="letterRemarks" class="form-label">{{ __('Additional Remarks') }}</label>
                        <textarea class="form-control" id="letterRemarks" name="letterRemarks" rows="3"
                                  placeholder="{{ __('Optional remarks to include in the letter') }}"></textarea>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeCompanySeal" name="includeCompanySeal" checked>
                            <label class="form-check-label" for="includeCompanySeal">
                                {{ __('Include company seal') }}
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="bx bx-info-circle me-2"></i>
                        <span id="letterInfoText">{{ __('The letter will be generated in PDF format and automatically downloaded.') }}</span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-download me-1"></i>{{ __('Generate & Download') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize flatpickr for effective date
    const effectiveDatePicker = flatpickr("#effectiveDate", {
        dateFormat: "Y-m-d",
        maxDate: "today"
    });

    // Handle generate letter form submission
    const generateLetterForm = document.getElementById('generateLetterForm');
    if (generateLetterForm) {
        generateLetterForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const letterType = document.getElementById('letterType').value;
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // Disable button and show loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Generating...') }}';

            // Construct the API endpoint
            const endpoint = `/employees/${pageData.userId}/generate-letter/${letterType}`;

            fetch(endpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `${letterType}-letter-{{ $user->code }}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);

                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.success,
                    text: '{{ __('Letter generated successfully') }}',
                    timer: 2000,
                    showConfirmButton: false
                });

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('generateLetterModal'));
                modal.hide();

                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: '{{ __('Failed to generate letter. Please try again.') }}'
                });
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }

    // Reset form when modal is closed
    const generateLetterModal = document.getElementById('generateLetterModal');
    if (generateLetterModal) {
        generateLetterModal.addEventListener('hidden.bs.modal', function () {
            generateLetterForm.reset();
        });
    }
});

// Function to generate relieving letter
function generateRelievingLetter() {
    const modal = new bootstrap.Modal(document.getElementById('generateLetterModal'));
    document.getElementById('letterType').value = 'relieving';
    document.getElementById('generateLetterModalLabel').innerHTML = '<i class="bx bx-file-blank me-1"></i>{{ __('Generate Relieving Letter') }}';
    document.getElementById('effectiveDateContainer').style.display = 'block';
    document.getElementById('letterInfoText').textContent = '{{ __('A relieving letter confirms that the employee has been formally relieved from their duties.') }}';
    modal.show();
}
</script>
