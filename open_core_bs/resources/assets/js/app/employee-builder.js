/**
 * Employee Builder - Step-by-step Employee Creation Interface
 * Intuitive wizard for creating employees with validation at each step
 */

import Dropzone from 'dropzone';
import flatpickr from 'flatpickr';

// Disable auto discover
Dropzone.autoDiscover = false;

$(function () {
  'use strict';

  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // State management
  let currentStep = 1;
  let stepper = null;
  let profilePictureFile = null;
  let hasUnsavedChanges = false;
  let validationState = {
    step1: {
      email: false,
      phone: false,
      code: false
    }
  };

  // Store form data per step
  let formData = {
    step1: {},
    step2: {},
    step3: {}
  };

  // Initialize
  initializeStepper();
  initializeStep1();
  initializeStep2();
  initializeNavigationWarning();

  /**
   * Initialize BS Stepper
   */
  function initializeStepper() {
    const stepperElement = document.querySelector('#employeeBuilderStepper');
    if (stepperElement && typeof window.Stepper !== 'undefined') {
      try {
        // Check if instance already exists on the element
        if (stepperElement.bsStepper) {
          stepper = stepperElement.bsStepper;
        } else {
          stepper = new window.Stepper(stepperElement, {
            linear: false,
            animation: true
          });
        }
      } catch (e) {
        console.error('Failed to initialize stepper:', e);
      }
    }
  }

  /**
   * Initialize Step 1: Personal Information
   */
  function initializeStep1() {
    // Initialize Flatpickr for date of birth
    if (document.getElementById('dob')) {
      flatpickr('#dob', {
        dateFormat: 'Y-m-d',
        maxDate: 'today',
        allowInput: true,
        onChange: function() {
          updateNextButtonState();
        }
      });
    }

    // Initialize Profile Picture Dropzone
    const profileDropzoneElement = document.querySelector('#profilePictureDropzone');
    if (profileDropzoneElement) {
      const profileDropzone = new Dropzone(profileDropzoneElement, {
        url: '#',
        maxFiles: 1,
        maxFilesize: 5,
        acceptedFiles: 'image/jpeg,image/jpg,image/png',
        addRemoveLinks: true,
        autoProcessQueue: false,
        dictDefaultMessage: employeeData.labels.dropzoneMessage || 'Drop profile picture here or click to upload',
        dictRemoveFile: employeeData.labels.remove || 'Remove',
        dictMaxFilesExceeded: employeeData.labels.maxFilesExceeded || 'Only one file allowed',
        dictInvalidFileType: employeeData.labels.invalidFileType || 'Only image files are allowed',
        dictFileTooBig: employeeData.labels.fileTooBig || 'File is too big (Max: 5MB)',
        init: function () {
          this.on('addedfile', function (file) {
            if (this.files.length > 1) {
              this.removeFile(this.files[0]);
            }
            profilePictureFile = file;
            hasUnsavedChanges = true;
          });
          this.on('removedfile', function () {
            profilePictureFile = null;
          });
        }
      });
    }

    // Real-time validation for email
    let emailValidationTimeout;
    $('#email').on('input', function () {
      clearTimeout(emailValidationTimeout);
      const email = $(this).val();

      if (!email) {
        removeFieldValidation('#email');
        validationState.step1.email = false;
        updateNextButtonState();
        return;
      }

      // Basic email format validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showFieldError('#email', employeeData.labels.invalidEmail || 'Invalid email format');
        validationState.step1.email = false;
        updateNextButtonState();
        return;
      }

      emailValidationTimeout = setTimeout(function () {
        validateEmailAsync(email);
      }, 500);
    });

    // Real-time validation for phone
    let phoneValidationTimeout;
    $('#phone').on('input', function () {
      clearTimeout(phoneValidationTimeout);
      const phone = $(this).val();

      if (!phone) {
        removeFieldValidation('#phone');
        validationState.step1.phone = false;
        updateNextButtonState();
        return;
      }

      phoneValidationTimeout = setTimeout(function () {
        validatePhoneAsync(phone);
      }, 500);
    });

    // Real-time validation for employee code
    let codeValidationTimeout;
    $('#code').on('input', function () {
      clearTimeout(codeValidationTimeout);
      const code = $(this).val();

      if (!code) {
        removeFieldValidation('#code');
        validationState.step1.code = false;
        updateNextButtonState();
        return;
      }

      codeValidationTimeout = setTimeout(function () {
        validateCodeAsync(code);
      }, 500);
    });

    // Listen to other required fields to update button state
    $('#firstName, #lastName, #gender, #dob').on('change input', function () {
      updateNextButtonState();
    });

    // Next button for step 1
    $('#btnNextStep1').on('click', function () {
      const isValid = validateStep1();
      if (isValid) {
        saveStepData(1);
        goToStep(2);
      }
    });
  }

  /**
   * Initialize Step 2: Employment & Account Details
   */
  function initializeStep2() {
    // Initialize Flatpickr for date of joining
    if (document.getElementById('doj')) {
      flatpickr('#doj', {
        dateFormat: 'Y-m-d',
        allowInput: true
      });
    }

    // Initialize Select2 for dropdowns
    if ($('.select2').length) {
      $('.select2').select2({
        width: '100%',
        dropdownParent: $('#step-employment-account')
      });
    }

    // Password toggle functionality
    $('#useDefaultPassword').on('change', function () {
      if ($(this).is(':checked')) {
        $('#passwordDiv').hide();
        $('#password').val('').removeAttr('required');
        $('#confirmPassword').val('').removeAttr('required');
      } else {
        $('#passwordDiv').show();
        $('#password').attr('required', 'required');
        $('#confirmPassword').attr('required', 'required');
      }
    });

    // Attendance type change handler
    $('#attendanceType').on('change', function () {
      const value = this.value;
      $('.attendance-type-field').hide().find('select').val('');

      if (value === 'geofence') {
        $('#geofenceGroupDiv').show();
        getGeofenceGroups();
      } else if (value === 'ipAddress') {
        $('#ipGroupDiv').show();
        getIpGroups();
      } else if (value === 'staticqr') {
        $('#qrGroupDiv').show();
        getQrGroups();
      } else if (value === 'site') {
        $('#siteDiv').show();
        getSites();
      } else if (value === 'dynamicqr') {
        $('#dynamicQrDiv').show();
        getDynamicQrDevices();
      }
    });

    // Auto-calculate probation end date
    $('#probationPeriodMonths, #doj').on('change', function () {
      const doj = $('#doj').val();
      const months = $('#probationPeriodMonths').val();

      if (doj && months) {
        const dojDate = new Date(doj);
        dojDate.setMonth(dojDate.getMonth() + parseInt(months));

        const day = String(dojDate.getDate()).padStart(2, '0');
        const month = String(dojDate.getMonth() + 1).padStart(2, '0');
        const year = dojDate.getFullYear();

        $('#probationEndDate').val(`${day}-${month}-${year}`);
      } else {
        $('#probationEndDate').val('');
      }
    });

    // Previous button
    $('#btnPrevStep2').on('click', function () {
      goToStep(1);
    });

    // Next button for step 2
    $('#btnNextStep2').on('click', function () {
      if (validateStep2()) {
        saveStepData(2);
        populateReviewStep();
        goToStep(3);
      }
    });
  }

  /**
   * Validate email via AJAX
   */
  function validateEmailAsync(email) {
    $.ajax({
      url: employeeData.urls.validateEmail,
      method: 'POST',
      data: { email: email },
      beforeSend: function () {
        showFieldLoading('#email');
      },
      success: function (response) {
        if (response.valid) {
          showFieldSuccess('#email', employeeData.labels.emailAvailable || 'Email is available');
          validationState.step1.email = true;
        } else {
          showFieldError('#email', employeeData.labels.emailExists || 'Email already exists');
          validationState.step1.email = false;
        }
        updateNextButtonState();
      },
      error: function () {
        showFieldError('#email', employeeData.labels.validationError || 'Validation error');
        validationState.step1.email = false;
        updateNextButtonState();
      }
    });
  }

  /**
   * Validate phone via AJAX
   */
  function validatePhoneAsync(phone) {
    $.ajax({
      url: employeeData.urls.validatePhone,
      method: 'POST',
      data: { phone: phone },
      beforeSend: function () {
        showFieldLoading('#phone');
      },
      success: function (response) {
        if (response.valid) {
          showFieldSuccess('#phone', employeeData.labels.phoneAvailable || 'Phone is available');
          validationState.step1.phone = true;
        } else {
          showFieldError('#phone', employeeData.labels.phoneExists || 'Phone already exists');
          validationState.step1.phone = false;
        }
        updateNextButtonState();
      },
      error: function () {
        showFieldError('#phone', employeeData.labels.validationError || 'Validation error');
        validationState.step1.phone = false;
        updateNextButtonState();
      }
    });
  }

  /**
   * Validate employee code via AJAX
   */
  function validateCodeAsync(code) {
    $.ajax({
      url: employeeData.urls.validateCode,
      method: 'POST',
      data: { code: code },
      beforeSend: function () {
        showFieldLoading('#code');
      },
      success: function (response) {
        if (response.valid) {
          showFieldSuccess('#code', employeeData.labels.codeAvailable || 'Employee code is available');
          validationState.step1.code = true;
        } else {
          showFieldError('#code', employeeData.labels.codeExists || 'Employee code already exists');
          validationState.step1.code = false;
        }
        updateNextButtonState();
      },
      error: function () {
        showFieldError('#code', employeeData.labels.validationError || 'Validation error');
        validationState.step1.code = false;
        updateNextButtonState();
      }
    });
  }

  /**
   * Update next button state based on validation
   */
  function updateNextButtonState() {
    const allValid = validationState.step1.email && validationState.step1.phone && validationState.step1.code;
    const allFilled = $('#firstName').val() && $('#lastName').val() && $('#email').val() &&
                      $('#phone').val() && $('#code').val() && $('#gender').val() && $('#dob').val();

    $('#btnNextStep1').prop('disabled', !(allValid && allFilled));
  }

  /**
   * Validate Step 1
   */
  function validateStep1() {
    // Check required fields
    if (!$('#firstName').val()) {
      showError(employeeData.labels.firstNameRequired || 'First name is required');
      return false;
    }
    if (!$('#lastName').val()) {
      showError(employeeData.labels.lastNameRequired || 'Last name is required');
      return false;
    }
    if (!$('#email').val()) {
      showError(employeeData.labels.emailRequired || 'Email is required');
      return false;
    }
    if (!validationState.step1.email) {
      showError(employeeData.labels.emailNotValid || 'Email is not valid or already exists');
      return false;
    }
    if (!$('#phone').val()) {
      showError(employeeData.labels.phoneRequired || 'Phone number is required');
      return false;
    }
    if (!validationState.step1.phone) {
      showError(employeeData.labels.phoneNotValid || 'Phone number is not valid or already exists');
      return false;
    }
    if (!$('#code').val()) {
      showError(employeeData.labels.codeRequired || 'Employee code is required');
      return false;
    }
    if (!validationState.step1.code) {
      showError(employeeData.labels.codeNotValid || 'Employee code is not valid or already exists');
      return false;
    }
    if (!$('#gender').val()) {
      showError(employeeData.labels.genderRequired || 'Gender is required');
      return false;
    }
    if (!$('#dob').val()) {
      showError(employeeData.labels.dobRequired || 'Date of birth is required');
      return false;
    }

    return true;
  }

  /**
   * Validate Step 2
   */
  function validateStep2() {
    // Check employment details
    if (!$('#doj').val()) {
      showError(employeeData.labels.dojRequired || 'Date of joining is required');
      return false;
    }
    if (!$('#designationId').val()) {
      showError(employeeData.labels.designationRequired || 'Designation is required');
      return false;
    }
    if (!$('#teamId').val()) {
      showError(employeeData.labels.teamRequired || 'Team is required');
      return false;
    }
    if (!$('#reportingToId').val()) {
      showError(employeeData.labels.reportingManagerRequired || 'Reporting manager is required');
      return false;
    }
    if (!$('#shiftId').val()) {
      showError(employeeData.labels.shiftRequired || 'Shift is required');
      return false;
    }

    // Check account settings
    if (!$('#role').val()) {
      showError(employeeData.labels.roleRequired || 'Role is required');
      return false;
    }

    // Password validation
    if (!$('#useDefaultPassword').is(':checked')) {
      const password = $('#password').val();
      const confirmPassword = $('#confirmPassword').val();

      if (!password) {
        showError(employeeData.labels.passwordRequired || 'Password is required');
        return false;
      }
      if (password.length < 6) {
        showError(employeeData.labels.passwordMinLength || 'Password must be at least 6 characters');
        return false;
      }
      if (password !== confirmPassword) {
        showError(employeeData.labels.passwordMismatch || 'Passwords do not match');
        return false;
      }
    }

    // Attendance type validation
    const attendanceType = $('#attendanceType').val();
    if (!attendanceType) {
      showError(employeeData.labels.attendanceTypeRequired || 'Attendance type is required');
      return false;
    }

    // Conditional attendance validation
    if (attendanceType === 'geofence' && !$('#geofenceGroupId').val()) {
      showError(employeeData.labels.geofenceGroupRequired || 'Geofence group is required');
      return false;
    }
    if (attendanceType === 'ipAddress' && !$('#ipGroupId').val()) {
      showError(employeeData.labels.ipGroupRequired || 'IP group is required');
      return false;
    }
    if (attendanceType === 'staticqr' && !$('#qrGroupId').val()) {
      showError(employeeData.labels.qrGroupRequired || 'QR group is required');
      return false;
    }
    if (attendanceType === 'site' && !$('#siteId').val()) {
      showError(employeeData.labels.siteRequired || 'Site is required');
      return false;
    }
    if (attendanceType === 'dynamicqr' && !$('#dynamicQrId').val()) {
      showError(employeeData.labels.dynamicQrDeviceRequired || 'Dynamic QR device is required');
      return false;
    }

    return true;
  }

  /**
   * Save step data
   */
  function saveStepData(step) {
    if (step === 1) {
      formData.step1 = {
        firstName: $('#firstName').val(),
        lastName: $('#lastName').val(),
        email: $('#email').val(),
        phone: $('#phone').val(),
        code: $('#code').val(),
        gender: $('#gender').val(),
        dob: $('#dob').val(),
        altPhone: $('#altPhone').val(),
        address: $('#address').val()
      };
    } else if (step === 2) {
      formData.step2 = {
        doj: $('#doj').val(),
        designationId: $('#designationId').val(),
        teamId: $('#teamId').val(),
        reportingToId: $('#reportingToId').val(),
        shiftId: $('#shiftId').val(),
        role: $('#role').val(),
        useDefaultPassword: $('#useDefaultPassword').is(':checked') ? 'on' : '',
        password: $('#password').val(),
        confirmPassword: $('#confirmPassword').val(),
        attendanceType: $('#attendanceType').val(),
        geofenceGroupId: $('#geofenceGroupId').val(),
        ipGroupId: $('#ipGroupId').val(),
        qrGroupId: $('#qrGroupId').val(),
        siteId: $('#siteId').val(),
        dynamicQrId: $('#dynamicQrId').val(),
        probationPeriodMonths: $('#probationPeriodMonths').val(),
        probationRemarks: $('#probationRemarks').val()
      };
    }
    hasUnsavedChanges = true;
  }

  /**
   * Navigate to specific step
   */
  function goToStep(step) {
    currentStep = step;

    if (stepper) {
      // Get current index (0-based)
      const currentIndex = stepper._currentIndex;
      const targetIndex = step - 1; // Convert 1-based to 0-based

      // Navigate using next() or previous() methods
      if (targetIndex > currentIndex) {
        // Move forward
        for (let i = currentIndex; i < targetIndex; i++) {
          stepper.next();
        }
      } else if (targetIndex < currentIndex) {
        // Move backward
        for (let i = currentIndex; i > targetIndex; i--) {
          stepper.previous();
        }
      }
      // If targetIndex === currentIndex, do nothing (already there)
    } else {
      console.error('Stepper is not initialized!');
    }

    // Scroll to top and prevent focus on first element
    $('html, body').animate({ scrollTop: 0 }, 300, function() {
      // Remove focus from any active element
      if (document.activeElement) {
        document.activeElement.blur();
      }
    });
  }

  /**
   * Populate review step with form data
   */
  function populateReviewStep() {
    // Personal Information
    $('#reviewFirstName').text(formData.step1.firstName || '-');
    $('#reviewLastName').text(formData.step1.lastName || '-');
    $('#reviewEmail').text(formData.step1.email || '-');
    $('#reviewPhone').text(formData.step1.phone || '-');
    $('#reviewCode').text(formData.step1.code || '-');
    $('#reviewGender').text($('#gender option:selected').text() || '-');
    $('#reviewDob').text(formData.step1.dob || '-');
    $('#reviewAltPhone').text(formData.step1.altPhone || '-');
    $('#reviewAddress').text(formData.step1.address || '-');

    // Employment Details
    $('#reviewDoj').text(formData.step2.doj || '-');
    $('#reviewDesignation').text($('#designationId option:selected').text() || '-');
    $('#reviewTeam').text($('#teamId option:selected').text() || '-');
    $('#reviewReportingManager').text($('#reportingToId option:selected').text() || '-');
    $('#reviewShift').text($('#shiftId option:selected').text() || '-');

    // Account Settings
    $('#reviewRole').text(formData.step2.role || '-');
    $('#reviewPassword').text(formData.step2.useDefaultPassword ? employeeData.labels.defaultPassword || 'Default Password' : employeeData.labels.customPassword || 'Custom Password');

    // Attendance Configuration
    $('#reviewAttendanceType').text($('#attendanceType option:selected').text() || '-');

    let attendanceDetails = '';
    if (formData.step2.attendanceType === 'geofence') {
      attendanceDetails = $('#geofenceGroupId option:selected').text();
    } else if (formData.step2.attendanceType === 'ipAddress') {
      attendanceDetails = $('#ipGroupId option:selected').text();
    } else if (formData.step2.attendanceType === 'staticqr') {
      attendanceDetails = $('#qrGroupId option:selected').text();
    } else if (formData.step2.attendanceType === 'site') {
      attendanceDetails = $('#siteId option:selected').text();
    } else if (formData.step2.attendanceType === 'dynamicqr') {
      attendanceDetails = $('#dynamicQrId option:selected').text();
    }
    $('#reviewAttendanceDetails').text(attendanceDetails || employeeData.labels.noRestrictions || 'No restrictions');

    // Probation Period
    if (formData.step2.probationPeriodMonths) {
      $('#reviewProbation').text(formData.step2.probationPeriodMonths + ' ' + (employeeData.labels.months || 'month(s)'));
      $('#reviewProbationEndDate').text($('#probationEndDate').val() || '-');
    } else {
      $('#reviewProbation').text(employeeData.labels.noProbation || 'No probation period');
      $('#reviewProbationEndDate').text('-');
    }
  }

  /**
   * Handle form submission
   */
  $('#btnPrevStep3').on('click', function () {
    goToStep(2);
  });

  $('#employeeBuilderForm').on('submit', function (e) {
    e.preventDefault();

    // Collect all form data
    const finalData = new FormData();

    // Step 1 data
    Object.keys(formData.step1).forEach(key => {
      if (formData.step1[key]) {
        finalData.append(key, formData.step1[key]);
      }
    });

    // Step 2 data
    Object.keys(formData.step2).forEach(key => {
      if (formData.step2[key]) {
        finalData.append(key, formData.step2[key]);
      }
    });

    // Profile picture file
    if (profilePictureFile) {
      finalData.append('file', profilePictureFile);
    }

    // Disable submit button
    const submitBtn = $('#btnCreateEmployee');
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + (employeeData.labels.creating || 'Creating...'));

    // Submit
    $.ajax({
      url: employeeData.urls.store,
      method: 'POST',
      data: finalData,
      processData: false,
      contentType: false,
      success: function (response) {
        hasUnsavedChanges = false;
        Swal.fire({
          icon: 'success',
          title: employeeData.labels.success || 'Success!',
          text: employeeData.labels.employeeCreated || 'Employee created successfully',
          customClass: { confirmButton: 'btn btn-success' },
          buttonsStyling: false
        }).then(() => {
          window.location.href = employeeData.urls.index;
        });
      },
      error: function (xhr) {
        submitBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>' + (employeeData.labels.createEmployee || 'Create Employee'));

        if (xhr.status === 422) {
          const errors = xhr.responseJSON.errors;
          let errorMessages = [];
          $.each(errors, function (key, value) {
            errorMessages.push(value[0]);
          });

          Swal.fire({
            icon: 'error',
            title: employeeData.labels.validationError || 'Validation Error',
            html: errorMessages.join('<br>'),
            customClass: { confirmButton: 'btn btn-danger' },
            buttonsStyling: false
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: employeeData.labels.error || 'Error!',
            text: xhr.responseJSON?.message || employeeData.labels.createFailed || 'Failed to create employee',
            customClass: { confirmButton: 'btn btn-danger' },
            buttonsStyling: false
          });
        }
      }
    });
  });

  /**
   * Initialize navigation warning
   */
  function initializeNavigationWarning() {
    window.addEventListener('beforeunload', function (e) {
      if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
  }

  /**
   * Field validation helpers
   */
  function showFieldLoading(fieldId) {
    $(fieldId).removeClass('is-invalid is-valid');
    $(fieldId).next('.invalid-feedback, .valid-feedback').remove();
    $(fieldId).after('<div class="text-muted small mt-1"><span class="spinner-border spinner-border-sm me-1"></span>Validating...</div>');
  }

  function showFieldSuccess(fieldId, message) {
    $(fieldId).removeClass('is-invalid').addClass('is-valid');
    $(fieldId).next('.invalid-feedback, .valid-feedback, .text-muted').remove();
    $(fieldId).after('<div class="valid-feedback d-block">' + message + '</div>');
  }

  function showFieldError(fieldId, message) {
    $(fieldId).removeClass('is-valid').addClass('is-invalid');
    $(fieldId).next('.invalid-feedback, .valid-feedback, .text-muted').remove();
    $(fieldId).after('<div class="invalid-feedback d-block">' + message + '</div>');
  }

  function removeFieldValidation(fieldId) {
    $(fieldId).removeClass('is-invalid is-valid');
    $(fieldId).next('.invalid-feedback, .valid-feedback, .text-muted').remove();
  }

  /**
   * Show error using SweetAlert2
   */
  function showError(message) {
    Swal.fire({
      icon: 'warning',
      title: employeeData.labels.validationWarning || 'Validation Warning',
      text: message,
      customClass: { confirmButton: 'btn btn-warning' },
      buttonsStyling: false
    });
  }

  /**
   * Attendance configuration helper functions
   */
  function getDynamicQrDevices() {
    $.ajax({
      url: employeeData.urls.getDynamicQrDevices,
      type: 'GET',
      success: function (response) {
        if (response.length === 0) {
          showError(employeeData.labels.createDynamicQrDevice || 'Please create a dynamic QR device first');
          return;
        }
        let options = '<option value="">' + (employeeData.labels.selectDynamicQrDevice || 'Select a dynamic QR device') + '</option>';
        response.forEach(function (item) {
          options += '<option value="' + item.id + '">' + item.name + '</option>';
        });
        $('#dynamicQrId').html(options);
      }
    });
  }

  function getGeofenceGroups() {
    $.ajax({
      url: employeeData.urls.getGeofenceGroups,
      type: 'GET',
      success: function (response) {
        if (response.length === 0) {
          showError(employeeData.labels.createGeofenceGroup || 'Please create a geofence group first');
          return;
        }
        let options = '<option value="">' + (employeeData.labels.selectGeofenceGroup || 'Select a geofence group') + '</option>';
        response.forEach(function (item) {
          options += '<option value="' + item.id + '">' + item.name + '</option>';
        });
        $('#geofenceGroupId').html(options);
      }
    });
  }

  function getIpGroups() {
    $.ajax({
      url: employeeData.urls.getIpGroups,
      type: 'GET',
      success: function (response) {
        if (response.length === 0) {
          showError(employeeData.labels.createIpGroup || 'Please create an IP group first');
          return;
        }
        let options = '<option value="">' + (employeeData.labels.selectIpGroup || 'Select an IP group') + '</option>';
        response.forEach(function (item) {
          options += '<option value="' + item.id + '">' + item.name + '</option>';
        });
        $('#ipGroupId').html(options);
      }
    });
  }

  function getQrGroups() {
    $.ajax({
      url: employeeData.urls.getQrGroups,
      type: 'GET',
      success: function (response) {
        if (response.length === 0) {
          showError(employeeData.labels.createQrGroup || 'Please create a QR group first');
          return;
        }
        let options = '<option value="">' + (employeeData.labels.selectQrGroup || 'Select a QR group') + '</option>';
        response.forEach(function (item) {
          options += '<option value="' + item.id + '">' + item.name + '</option>';
        });
        $('#qrGroupId').html(options);
      }
    });
  }

  function getSites() {
    $.ajax({
      url: employeeData.urls.getSites,
      type: 'GET',
      success: function (response) {
        if (response.length === 0) {
          showError(employeeData.labels.createSite || 'Please create a site first');
          return;
        }
        let options = '<option value="">' + (employeeData.labels.selectSite || 'Select a site') + '</option>';
        response.forEach(function (item) {
          options += '<option value="' + item.id + '">' + item.name + '</option>';
        });
        $('#siteId').html(options);
      }
    });
  }
});
