@php use App\Enums\Gender;use Carbon\Carbon; @endphp
  <!-- Edit Basic Info Modal -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditBasicInfo"
     aria-labelledby="offcanvasEditBasicInfoLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditBasicInfoLabel" class="offcanvas-title">@lang('Edit Basic Information')</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form class="pt-0" id="basicInfoForm" action="{{route('employees.updateBasicInfo')}}" method="POST">
      @csrf

      <input type="hidden" name="id" id="id" value="{{ $user->id }}">
      <div class="mb-6">
        <label class="form-label" for="firstName">@lang('First Name')<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="firstName" name="firstName"
               value="{{ $user->first_name }}"
               placeholder="@lang('Enter first name')" />
      </div>

      <div class="mb-6">
        <label class="form-label" for="lastName">@lang('Last Name')<span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="lastName" name="lastName" value="{{ $user->last_name }}"
               placeholder="@lang('Enter last name')" />
      </div>
      <div class="mb-6">
        <label class="form-label" for="dob">Date of Birth <span class="text-danger">*</span></label>
        <input type="date" name="dob" id="dob" class="form-control"
               value="{{ Carbon::parse($user->dob)->format('Y-m-d') }}" />
      </div>
      <div class="mb-6">
        <label class="form-label" for="gender">Gender <span class="text-danger">*</span></label>
        <select class="form-control select2" id="gender" data-style="btn-transparent"
                data-icon-base="bx" data-tick-icon="bx-check text-white" name="gender">
          <option value="" selected>Select Gender</option>
          @foreach(Gender::cases() as $gender)
            <option
              value="{{$gender->value}}" {{$user->gender == $gender->value ? 'selected':''}} >{{$gender->value}}</option>
          @endforeach
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="bloodGroup">@lang('Blood Group')</label>
        <select class="form-control select2" id="bloodGroup" name="bloodGroup">
          <option value="">@lang('Select Blood Group')</option>
          <option value="A+" {{ $user->blood_group == 'A+' ? 'selected' : '' }}>A+</option>
          <option value="A-" {{ $user->blood_group == 'A-' ? 'selected' : '' }}>A-</option>
          <option value="B+" {{ $user->blood_group == 'B+' ? 'selected' : '' }}>B+</option>
          <option value="B-" {{ $user->blood_group == 'B-' ? 'selected' : '' }}>B-</option>
          <option value="AB+" {{ $user->blood_group == 'AB+' ? 'selected' : '' }}>AB+</option>
          <option value="AB-" {{ $user->blood_group == 'AB-' ? 'selected' : '' }}>AB-</option>
          <option value="O+" {{ $user->blood_group == 'O+' ? 'selected' : '' }}>O+</option>
          <option value="O-" {{ $user->blood_group == 'O-' ? 'selected' : '' }}>O-</option>
        </select>
      </div>
      <div class="mb-6">
        <label class="form-label" for="email">@lang('Email')<span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email"
               value="{{ $user->email }}"
               placeholder="@lang('Enter email')" />
      </div>
      <div class="mb-6">
        <label class="form-label" for="phone">Phone Number <span class="text-danger">*</span></label>
        <input type="number" name="phone" id="phone" class="form-control"
               value="{{$user->phone}}"
               placeholder="Enter phone number" />
      </div>
      <div class="mb-6">
        <label class="form-label" for="altPhone">Alternative Mobile No</label>
        <input type="number" name="altPhone" id="altPhone" class="form-control"
               value="{{$user->alternate_number}}"
               placeholder="Enter alternate number" />
      </div>
      <div class="mb-6">
        <label class="form-label" for="address">@lang('Address')</label>
        <textarea class="form-control" id="address" name="address"
                  placeholder="@lang('Enter address')">{{ $user->address }}</textarea>
      </div>

      {{-- Emergency Contact Section --}}
      <div class="mb-4">
        <h6 class="text-muted mb-3">
          <i class='bx bx-phone-call me-2'></i>@lang('Emergency Contact Information')
        </h6>
      </div>

      <div class="mb-6">
        <label class="form-label" for="emergencyContactName">@lang('Contact Name')</label>
        <input type="text" class="form-control" id="emergencyContactName" name="emergencyContactName"
               value="{{ $user->emergency_contact_name }}"
               placeholder="@lang('Enter emergency contact name')" />
      </div>

      <div class="mb-6">
        <label class="form-label" for="emergencyContactRelationship">@lang('Relationship')</label>
        <select class="form-control select2" id="emergencyContactRelationship" name="emergencyContactRelationship">
          <option value="">@lang('Select Relationship')</option>
          <option value="Spouse" {{ $user->emergency_contact_relationship == 'Spouse' ? 'selected' : '' }}>@lang('Spouse')</option>
          <option value="Parent" {{ $user->emergency_contact_relationship == 'Parent' ? 'selected' : '' }}>@lang('Parent')</option>
          <option value="Sibling" {{ $user->emergency_contact_relationship == 'Sibling' ? 'selected' : '' }}>@lang('Sibling')</option>
          <option value="Child" {{ $user->emergency_contact_relationship == 'Child' ? 'selected' : '' }}>@lang('Child')</option>
          <option value="Friend" {{ $user->emergency_contact_relationship == 'Friend' ? 'selected' : '' }}>@lang('Friend')</option>
          <option value="Other" {{ $user->emergency_contact_relationship == 'Other' ? 'selected' : '' }}>@lang('Other')</option>
        </select>
      </div>

      <div class="mb-6">
        <label class="form-label" for="emergencyContactPhone">@lang('Contact Phone')</label>
        <input type="number" class="form-control" id="emergencyContactPhone" name="emergencyContactPhone"
               value="{{ $user->emergency_contact_phone }}"
               placeholder="@lang('Enter emergency contact phone')" />
      </div>

      <div class="mb-6">
        <label class="form-label" for="emergencyContactAddress">@lang('Contact Address')</label>
        <textarea class="form-control" id="emergencyContactAddress" name="emergencyContactAddress"
                  placeholder="@lang('Enter emergency contact address')">{{ $user->emergency_contact_address }}</textarea>
      </div>

      <button type="submit" class="btn btn-primary me-3 data-submit">@lang('Save Changes')</button>
      <button type="reset" class="btn btn-label-danger" data-bs-dismiss="offcanvas">@lang('Cancel')</button>
    </form>
  </div>
</div>
<!-- /Edit Basic Info Modal -->
