{{-- Shift Form Offcanvas --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddOrUpdateShift" aria-labelledby="offcanvasShiftLabel">
    <div class="offcanvas-header">
        <h5 id="offcanvasShiftLabel" class="offcanvas-title">{{ __('Add Shift') }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body">
        <form id="shiftForm" class="needs-validation" novalidate>
            @csrf
            <input type="hidden" id="shift_id" value="">

            {{-- Name --}}
            <div class="mb-3">
                <label class="form-label" for="shiftName">{{ __('Shift Name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="shiftName" name="name" placeholder="{{ __('e.g., General Shift, Night Shift') }}" required />
                <div class="invalid-feedback"></div>
            </div>

            {{-- Code --}}
            <div class="mb-3">
                <label class="form-label" for="shiftCode">{{ __('Shift Code') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="shiftCode" name="code" placeholder="{{ __('e.g., GS01, NS02') }}" required />
                <div class="invalid-feedback"></div>
            </div>

            {{-- Hidden Type field - always set to 'regular' --}}
            <input type="hidden" name="shift_type" value="regular">

            {{-- Start & End Time --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="startTime" class="form-label">{{ __('Start Time') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control flatpickr-input" id="startTime" name="start_time" placeholder="HH:MM" required readonly="readonly" />
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-md-6">
                    <label for="endTime" class="form-label">{{ __('End Time') }} <span class="text-danger">*</span></label>
                    <input type="text" class="form-control flatpickr-input" id="endTime" name="end_time" placeholder="HH:MM" required readonly="readonly" />
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            {{-- Working Days --}}
            <div class="mb-3">
                <label class="form-label d-block">{{ __('Working Days') }} <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                        <div class="form-check">
                            <input class="form-check-input working-day-check" type="checkbox" value="1" id="{{ $day }}Toggle" name="{{ $day }}">
                            <label class="form-check-label" for="{{ $day }}Toggle">
                                {{ __(ucfirst($day)) }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="invalid-feedback d-block" id="workingDaysError"></div>
            </div>

            {{-- Notes --}}
            <div class="mb-3">
                <label class="form-label" for="shiftNotes">{{ __('Notes') }}</label>
                <textarea class="form-control" id="shiftNotes" name="notes" rows="3" placeholder="{{ __('Optional notes about this shift...') }}"></textarea>
                <div class="invalid-feedback"></div>
            </div>

            {{-- General Error Message --}}
            <div class="mb-3">
                <div class="alert alert-danger d-none" id="generalErrorMessage"></div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill" id="submitShiftBtn">
                    {{ __('Save') }}
                </button>
                <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">
                    {{ __('Cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>
