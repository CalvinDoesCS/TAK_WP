<!-- Add/Update Role Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="addOrUpdateRoleOffcanvas" aria-labelledby="addOrUpdateRoleOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title role-title" id="addOrUpdateRoleOffcanvasLabel">{{ __('Add New Role') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <!-- Add role form -->
    <form id="addRoleForm" class="row g-4" onsubmit="return false">
      <input type="hidden" name="id" id="id" />

      <div class="col-12">
        <label class="form-label" for="name">{{ __('Role Name') }}</label>
        <input type="text" id="name" name="name" class="form-control"
               placeholder="{{ __('Enter a role name') }}" autofocus />
      </div>

      <div class="col-12">
        <label class="form-label" for="isMultiCheckInEnabled">{{ __('Multi Check-In/Out') }}</label>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" id="isMultiCheckInEnabled" name="isMultiCheckInEnabled" />
          <label class="form-check-label" for="isMultiCheckInEnabled">{{ __('Enable') }}</label>
        </div>
      </div>

      <div class="col-12">
        <label class="form-label" for="mobileAppAccess">{{ __('Mobile App Access') }}</label>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" id="mobileAppAccess" name="mobileAppAccess" />
          <label class="form-check-label" for="mobileAppAccess">{{ __('Enable') }}</label>
        </div>
      </div>

      <div class="col-12">
        <label class="form-label" for="webAppAccess">{{ __('Web App Access') }}</label>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" id="webAppAccess" name="webAppAccess" />
          <label class="form-check-label" for="webAppAccess">{{ __('Enable') }}</label>
        </div>
      </div>

      <div class="col-12">
        <label class="form-label" for="locationActivityTracking">{{ __('Location Activity Tracking') }}</label>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input" id="locationActivityTracking"
                 name="locationActivityTracking" />
          <label class="form-check-label" for="locationActivityTracking">{{ __('Enable') }}</label>
        </div>
      </div>

      <div class="col-12 d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary flex-fill">{{ __('Submit') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
    <!--/ Add role form -->
  </div>
</div>
<!--/ Add/Update Role Offcanvas -->
