<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddLeave" aria-labelledby="offcanvasAddLeaveLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddLeaveLabel" class="offcanvas-title">@lang('Add Leave Request')</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form id="leaveRequestForm" enctype="multipart/form-data">
      @csrf

      {{-- Employee Selection (for HR/Admin) --}}
      {{-- @can('hrcore.create-leave-for-others') --}} {{-- PERMISSION TEMPORARILY DISABLED --}}
      <div class="mb-6">
        <label class="form-label" for="user_id">@lang('Employee')<span class="text-danger">*</span></label>
        <select class="form-select" id="user_id" name="user_id" required style="width: 100%;">
          <option value="">@lang('Select Employee')</option>
        </select>
      </div>
      {{-- @endcan --}}

      {{-- Leave Type --}}
      <div class="mb-6">
        <label class="form-label" for="leave_type_id">@lang('Leave Type')<span class="text-danger">*</span></label>
        <select class="form-select" id="leave_type_id" name="leave_type_id" required>
          <option value="">@lang('Select Leave Type')</option>
          @foreach($leaveTypes as $leaveType)
            @php
              $balance = auth()->check() ? auth()->user()->getLeaveBalance($leaveType->id) ?? 0 : 0;
            @endphp
            <option value="{{ $leaveType->id }}"
              data-proof-required="{{ $leaveType->is_proof_required }}"
              data-balance="{{ $balance }}">
              {{ $leaveType->name }} (@lang('Balance'): {{ $balance }} @lang('days'))
            </option>
          @endforeach
        </select>
        <div class="form-text" id="leave_balance_info"></div>
      </div>

      {{-- Leave Duration Type --}}
      <div class="mb-6">
        <label class="form-label">@lang('Leave Duration')</label>
        <div class="row">
          <div class="col-md-6">
            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content" for="full_day">
                <input class="form-check-input" type="radio" name="leave_duration" id="full_day" value="full" checked />
                <span class="custom-option-header">
                  <span class="h6 mb-0">@lang('Full Day(s)')</span>
                </span>
              </label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content" for="half_day">
                <input class="form-check-input" type="radio" name="leave_duration" id="half_day" value="half" />
                <span class="custom-option-header">
                  <span class="h6 mb-0">@lang('Half Day')</span>
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>

      {{-- Date Selection --}}
      <div id="full_day_dates">
        <div class="row mb-6">
          <div class="col-md-6">
            <label class="form-label" for="from_date">@lang('From Date')<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="from_date" name="from_date" placeholder="@lang('Select date')" required />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="to_date">@lang('To Date')<span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="to_date" name="to_date" placeholder="@lang('Select date')" required />
          </div>
        </div>
      </div>

      {{-- Half Day Options --}}
      <div id="half_day_options" style="display: none;">
        <div class="mb-6">
          <label class="form-label" for="half_day_date">@lang('Date')<span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="half_day_date" name="half_day_date" placeholder="@lang('Select date')" />
        </div>
        <div class="mb-6">
          <label class="form-label">@lang('Half Day Type')</label>
          <div class="row">
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="half_day_type" id="first_half" value="first_half" checked>
                <label class="form-check-label" for="first_half">@lang('First Half')</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="half_day_type" id="second_half" value="second_half">
                <label class="form-check-label" for="second_half">@lang('Second Half')</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Total Days Display --}}
      <div class="mb-6">
        <div class="alert alert-info">
          <i class="bx bx-info-circle me-2"></i>
          <span id="total_days_info">@lang('Total Days'): <strong>0</strong></span>
        </div>
      </div>

      {{-- Compensatory Off Section --}}
      <div class="mb-6" id="comp_off_section" style="display: none;">
        <div class="card bg-label-success">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h6 class="mb-1">@lang('Compensatory Off Available')</h6>
                <p class="mb-0 text-muted small">@lang('You have comp off balance that can be used')</p>
              </div>
              <div class="text-end">
                <h4 class="mb-0 text-success" id="comp_off_balance">0</h4>
                <small class="text-muted">@lang('days')</small>
              </div>
            </div>

            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="use_comp_off" name="use_comp_off" value="1">
              <label class="form-check-label" for="use_comp_off">
                @lang('Use Compensatory Off for this leave')
              </label>
            </div>

            <div id="comp_off_usage_info" class="mt-3" style="display: none;">
              <div class="alert alert-primary mb-0">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <i class="bx bx-info-circle me-2"></i>
                    <span>@lang('Comp Off Days to Use'): <strong id="comp_off_days_used">0</strong></span>
                  </div>
                  <div>
                    <span>@lang('Leave Balance Used'): <strong id="leave_balance_used">0</strong></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Reason --}}
      <div class="mb-6">
        <label class="form-label" for="user_notes">@lang('Reason')<span class="text-danger">*</span></label>
        <textarea class="form-control" id="user_notes" name="user_notes" rows="3" placeholder="@lang('Enter reason for leave')" required></textarea>
      </div>

      {{-- Emergency Contact (Optional) --}}
      <div class="mb-6">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="add_emergency_contact">
          <label class="form-check-label" for="add_emergency_contact">
            @lang('Add Emergency Contact Information')
          </label>
        </div>
      </div>

      <div id="emergency_contact_section" style="display: none;">
        <div class="row mb-6">
          <div class="col-md-6">
            <label class="form-label" for="emergency_contact">@lang('Contact Name')</label>
            <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" placeholder="@lang('Contact name')" />
          </div>
          <div class="col-md-6">
            <label class="form-label" for="emergency_phone">@lang('Contact Phone')</label>
            <input type="text" class="form-control" id="emergency_phone" name="emergency_phone" placeholder="@lang('Phone number')" />
          </div>
        </div>
      </div>

      {{-- Travel Abroad --}}
      <div class="mb-6">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="is_abroad" name="is_abroad" value="1">
          <label class="form-check-label" for="is_abroad">
            @lang('Traveling Abroad')
          </label>
        </div>
      </div>

      <div id="abroad_location_section" style="display: none;">
        <div class="mb-6">
          <label class="form-label" for="abroad_location">@lang('Location/Country')</label>
          <input type="text" class="form-control" id="abroad_location" name="abroad_location" placeholder="@lang('Enter location')" />
        </div>
      </div>

      {{-- Document Upload --}}
      <div class="mb-6" id="document_section" style="display: none;">
        <label class="form-label" for="document">@lang('Supporting Document')<span class="text-danger document-required" style="display: none;">*</span></label>
        <input type="file" class="form-control" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png" />
        <div class="form-text">@lang('Accepted formats: PDF, JPG, PNG (Max 2MB)')</div>
      </div>

      {{-- Submit Buttons --}}
      <button type="submit" class="btn btn-primary me-3">@lang('Submit Request')</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">@lang('Cancel')</button>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Store comp off data
  let availableCompOffs = [];
  let compOffBalance = 0;

  // Fetch available comp off balance
  function fetchCompOffBalance(userId = null) {
    const url = '/hrcore/my/compensatory-offs/available-balance';
    const data = {};

    // If userId is provided, pass it in the request
    if (userId) {
      data.user_id = userId;
    }

    $.ajax({
      url: url,
      method: 'GET',
      data: data,
      success: function(response) {
        if (response.status === 'success') {
          compOffBalance = parseFloat(response.data.balance) || 0;
          availableCompOffs = response.data.comp_offs || [];
          $('#comp_off_balance').text(compOffBalance);

          // Show comp off section if balance available
          if (compOffBalance > 0) {
            $('#comp_off_section').show();
          } else {
            $('#comp_off_section').hide();
          }

          updateCompOffUsage();
        }
      },
      error: function() {
        // Hide comp off section if error
        $('#comp_off_section').hide();
      }
    });
  }

  // Load comp off balance on form open
  // @can('hrcore.create-leave-for-others') // PERMISSION TEMPORARILY DISABLED
    // For HR/Admin, wait for employee selection before fetching comp off
    // Don't fetch on page load
  // @else
    // For regular users, fetch their own comp off balance
    // fetchCompOffBalance(); // Disabled for now
  // @endcan

  // Initialize date pickers
  flatpickr('#from_date, #to_date, #half_day_date', {
    dateFormat: 'Y-m-d',
    minDate: 'today',
    disable: [
      function(date) {
        // Disable weekends (customize based on your business rules)
        return (date.getDay() === 0 || date.getDay() === 6);
      }
    ]
  });

  // Toggle between full day and half day
  $('input[name="leave_duration"]').on('change', function() {
    if ($(this).val() === 'half') {
      $('#full_day_dates').hide();
      $('#half_day_options').show();
      $('#from_date, #to_date').prop('required', false);
      $('#half_day_date').prop('required', true);
      calculateTotalDays();
    } else {
      $('#full_day_dates').show();
      $('#half_day_options').hide();
      $('#from_date, #to_date').prop('required', true);
      $('#half_day_date').prop('required', false);
      calculateTotalDays();
    }
  });

  // Calculate total days
  function calculateTotalDays() {
    const leaveType = $('input[name="leave_duration"]:checked').val();

    if (leaveType === 'half') {
      $('#total_days_info').html('@lang("Total Days"): <strong>0.5</strong>');
    } else {
      const fromDate = $('#from_date').val();
      const toDate = $('#to_date').val();

      if (fromDate && toDate) {
        // Calculate working days between dates
        const start = new Date(fromDate);
        const end = new Date(toDate);
        let totalDays = 0;

        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
          // Skip weekends
          if (d.getDay() !== 0 && d.getDay() !== 6) {
            totalDays++;
          }
        }

        $('#total_days_info').html('@lang("Total Days"): <strong>' + totalDays + '</strong>');
      }
    }

    // Update comp off usage when total days changes
    updateCompOffUsage();
  }

  $('#from_date, #to_date').on('change', calculateTotalDays);

  // Update comp off usage calculation
  function updateCompOffUsage() {
    const useCompOff = $('#use_comp_off').is(':checked');
    const totalDaysText = $('#total_days_info strong').text();
    const totalDays = parseFloat(totalDaysText) || 0;

    if (useCompOff && totalDays > 0 && compOffBalance > 0) {
      // Calculate how much comp off to use (minimum of total days or available balance)
      const compOffDaysUsed = Math.min(totalDays, compOffBalance);
      const leaveBalanceUsed = totalDays - compOffDaysUsed;

      $('#comp_off_days_used').text(compOffDaysUsed.toFixed(1));
      $('#leave_balance_used').text(leaveBalanceUsed.toFixed(1));
      $('#comp_off_usage_info').show();
    } else {
      $('#comp_off_usage_info').hide();
    }
  }

  // Toggle comp off usage
  $('#use_comp_off').on('change', function() {
    updateCompOffUsage();
  });

  // Toggle emergency contact
  $('#add_emergency_contact').on('change', function() {
    $('#emergency_contact_section').toggle(this.checked);
  });

  // Toggle abroad location
  $('#is_abroad').on('change', function() {
    $('#abroad_location_section').toggle(this.checked);
    if (this.checked) {
      $('#abroad_location').prop('required', true);
    } else {
      $('#abroad_location').prop('required', false);
    }
  });

  // Handle employee selection change (for HR/Admin creating leave on behalf)
  // @can('hrcore.create-leave-for-others') // PERMISSION TEMPORARILY DISABLED
  $('#user_id').on('change', function() {
    const userId = $(this).val();

    if (userId) {
      // Fetch comp off balance for selected employee
      fetchCompOffBalance(userId);

      // Note: Leave type balance will need to be updated too
      // This requires fetching employee-specific leave balance from server
      // For now, leave type balance will update when leave type is selected
    } else {
      // Hide comp off section if no employee selected
      $('#comp_off_section').hide();
      compOffBalance = 0;
      availableCompOffs = [];
    }
  });
  // @endcan

  // Handle leave type change
  $('#leave_type_id').on('change', function() {
    const selected = $(this).find(':selected');
    const isProofRequired = selected.data('proof-required');
    const balance = selected.data('balance');

    if (isProofRequired) {
      $('#document_section').show();
      $('.document-required').show();
      $('#document').prop('required', true);
    } else {
      $('#document_section').show();
      $('.document-required').hide();
      $('#document').prop('required', false);
    }

    if (balance !== undefined) {
      $('#leave_balance_info').html('@lang("Available Balance"): <strong>' + balance + '</strong> @lang("days")');
    }
  });

  // Form submission
  $('#leaveRequestForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const mode = $(this).data('mode') || 'add';
    const leaveId = $(this).data('leaveId');

    // Add calculated values
    const leaveType = $('input[name="leave_duration"]:checked').val();
    formData.append('is_half_day', leaveType === 'half' ? '1' : '0');

    if (leaveType === 'half') {
      formData.append('from_date', $('#half_day_date').val());
      formData.append('to_date', $('#half_day_date').val());
      formData.append('total_days', '0.5');
    }

    // Add comp off data if using comp off
    const useCompOff = $('#use_comp_off').is(':checked');
    formData.set('use_comp_off', useCompOff ? '1' : '0');

    if (useCompOff) {
      const totalDaysText = $('#total_days_info strong').text();
      const totalDays = parseFloat(totalDaysText) || 0;
      const compOffDaysUsed = Math.min(totalDays, compOffBalance);

      formData.append('comp_off_days_used', compOffDaysUsed);

      // Select comp offs to use (FIFO - oldest first)
      const compOffIds = [];
      let remainingDays = compOffDaysUsed;

      for (let compOff of availableCompOffs) {
        if (remainingDays <= 0) break;

        compOffIds.push(compOff.id);
        remainingDays -= parseFloat(compOff.comp_off_days);
      }

      formData.append('comp_off_ids', JSON.stringify(compOffIds));
    }

    // Determine URL and method based on mode
    let url, method;
    if (mode === 'edit' && leaveId) {
      url = @json(route('hrcore.leaves.update', ':id')).replace(':id', leaveId);
      method = 'POST';
      formData.append('_method', 'PUT');
    } else {
      url = @json(route('hrcore.leaves.store'));
      method = 'POST';
    }

    // Submit via AJAX
    $.ajax({
      url: url,
      method: method,
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.status === 'success') {
          const message = mode === 'edit' ? 
            '@lang("Leave request updated successfully!")' : 
            '@lang("Leave request submitted successfully!")';
          
          Swal.fire({
            icon: 'success',
            title: '@lang("Success")',
            text: message
          });
          $('#offcanvasAddLeave').offcanvas('hide');
          $('#leaveRequestsTable').DataTable().ajax.reload();
          $('#leaveRequestForm')[0].reset();
        }
      },
      error: function(xhr) {
        let message = '@lang("An error occurred. Please try again.")';

        // Check for error message in different response formats
        if (xhr.responseJSON) {
          if (xhr.responseJSON.data && typeof xhr.responseJSON.data === 'string') {
            // Format: { "data": "Error message" }
            message = xhr.responseJSON.data;
          } else if (xhr.responseJSON.message) {
            // Format: { "message": "Error message" }
            message = xhr.responseJSON.message;
          } else if (xhr.responseJSON.errors) {
            // Format: { "errors": { "field": ["Error message"] } }
            const errors = Object.values(xhr.responseJSON.errors).flat();
            message = errors.join('\n');
          }
        }

        Swal.fire({
          icon: 'error',
          title: '@lang("Error")',
          text: message
        });
      }
    });
  });
});
</script>
