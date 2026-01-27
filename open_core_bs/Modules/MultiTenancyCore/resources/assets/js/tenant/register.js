'use strict';

/**
 * Tenant Registration Page JavaScript
 * Handles form validation, password strength, and AJAX submission
 */

document.addEventListener('DOMContentLoaded', function () {
  // Check if we're on the registration page
  if (!document.querySelector('.wizard-registration')) {
    return;
  }

  // Verify pageData is available
  if (typeof pageData === 'undefined') {
    console.error('pageData is not defined');
    return;
  }

  // CSRF Token Setup for all AJAX requests
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    // For jQuery
    if (typeof $ !== 'undefined') {
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': csrfToken
        }
      });
    }
  }

  // Constants
  const DEBOUNCE_DELAY = 500;
  const MIN_PASSWORD_LENGTH = pageData.settings?.minPasswordLength || 8;

  // State
  let emailTimeout;
  let phoneTimeout;
  let subdomainTimeout;
  let stepper;

  // Step validation tracking
  const stepValidation = {
    1: false,
    2: false
  };

  // Field validation status
  const fieldStatus = {
    email: false,
    phone: false,
    subdomain: false
  };

  // Initialize BS Stepper
  function initStepper() {
    const wizardElement = document.querySelector('.wizard-registration');
    if (wizardElement && typeof Stepper !== 'undefined') {
      stepper = new Stepper(wizardElement, {
        linear: false,
        animation: true
      });
    }
  }

  // Password Visibility Toggle
  function initPasswordToggle() {
    document.querySelectorAll('.password-toggle').forEach(function (toggle) {
      toggle.addEventListener('click', function () {
        const inputGroup = this.closest('.input-group');
        const passwordInput = inputGroup?.querySelector('input');
        const icon = this.querySelector('i');

        if (passwordInput && icon) {
          if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('bx-hide');
            icon.classList.add('bx-show');
          } else {
            passwordInput.type = 'password';
            icon.classList.remove('bx-show');
            icon.classList.add('bx-hide');
          }
        }
      });
    });
  }

  // Password Strength Indicator
  function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    const strengthFill = document.getElementById('passwordStrengthFill');
    const strengthText = document.getElementById('passwordStrengthText');

    if (!passwordInput || !strengthFill || !strengthText) return;

    passwordInput.addEventListener('input', function () {
      const password = this.value;
      const result = checkPasswordStrength(password);

      // Remove all classes
      strengthFill.className = 'password-strength-fill';
      strengthText.className = 'password-strength-text text-muted';

      if (password.length > 0) {
        strengthFill.classList.add(result.class);
        strengthText.classList.remove('text-muted');
        strengthText.classList.add(result.class);
      }

      strengthFill.style.width = result.width;
      strengthText.textContent = result.text;
    });
  }

  function checkPasswordStrength(password) {
    let strength = 0;

    const checks = {
      length: password.length >= MIN_PASSWORD_LENGTH,
      lowercase: /[a-z]/.test(password),
      uppercase: /[A-Z]/.test(password),
      numbers: /\d/.test(password),
      special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };

    strength = Object.values(checks).filter(Boolean).length;

    const labels = pageData.labels || {};

    const strengthLevels = {
      0: { class: '', text: labels.enterPassword || 'Enter a password', width: '0%' },
      1: { class: 'weak', text: labels.weak || 'Weak', width: '20%' },
      2: { class: 'fair', text: labels.fair || 'Fair', width: '40%' },
      3: { class: 'good', text: labels.good || 'Good', width: '60%' },
      4: { class: 'strong', text: labels.strong || 'Strong', width: '80%' },
      5: { class: 'excellent', text: labels.excellent || 'Excellent', width: '100%' }
    };

    return strengthLevels[strength];
  }

  // Email Validation with AJAX
  function initEmailValidation() {
    const emailInput = document.getElementById('email');
    if (!emailInput) return;

    emailInput.addEventListener('input', function () {
      clearTimeout(emailTimeout);
      const email = this.value.trim();

      // Clear validation states
      clearFieldValidation(this);
      fieldStatus.email = false;

      if (email && isValidEmailFormat(email)) {
        showFieldLoading(this);

        emailTimeout = setTimeout(() => {
          validateField('email', email, this);
        }, DEBOUNCE_DELAY);
      }
    });

    emailInput.addEventListener('blur', function () {
      const email = this.value.trim();
      if (email && isValidEmailFormat(email) && !fieldStatus.email) {
        clearTimeout(emailTimeout);
        showFieldLoading(this);
        validateField('email', email, this);
      }
    });
  }

  // Phone Validation with AJAX
  function initPhoneValidation() {
    const phoneInput = document.getElementById('phone');
    if (!phoneInput) return;

    phoneInput.addEventListener('input', function () {
      clearTimeout(phoneTimeout);
      const phone = this.value.trim();

      // Clear validation states
      clearFieldValidation(this);
      fieldStatus.phone = false;

      if (phone && phone.length >= 5) {
        showFieldLoading(this);

        phoneTimeout = setTimeout(() => {
          validateField('phone', phone, this);
        }, DEBOUNCE_DELAY);
      }
    });

    phoneInput.addEventListener('blur', function () {
      const phone = this.value.trim();
      if (phone && phone.length >= 5 && !fieldStatus.phone) {
        clearTimeout(phoneTimeout);
        showFieldLoading(this);
        validateField('phone', phone, this);
      }
    });
  }

  // Subdomain Auto-generation and Validation
  function initSubdomainValidation() {
    const companyNameInput = document.getElementById('company_name');
    const subdomainInput = document.getElementById('subdomain');

    if (!companyNameInput || !subdomainInput) return;

    // Auto-generate subdomain from company name
    companyNameInput.addEventListener('input', function () {
      if (!subdomainInput.hasAttribute('data-manual')) {
        const subdomain = this.value
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '')
          .substring(0, 63);
        subdomainInput.value = subdomain;

        // Trigger subdomain validation
        if (subdomain.length >= 3) {
          subdomainInput.dispatchEvent(new Event('input'));
        }
      }
    });

    // Subdomain validation
    subdomainInput.addEventListener('input', function () {
      this.setAttribute('data-manual', 'true');
      clearTimeout(subdomainTimeout);

      const subdomain = this.value.trim().toLowerCase();

      // Clear validation states
      clearFieldValidation(this);
      fieldStatus.subdomain = false;

      // Only validate if subdomain meets minimum requirements
      if (subdomain && subdomain.length >= 3) {
        showFieldLoading(this);

        subdomainTimeout = setTimeout(() => {
          validateField('subdomain', subdomain, this);
        }, DEBOUNCE_DELAY);
      }
    });

    subdomainInput.addEventListener('blur', function () {
      const subdomain = this.value.trim().toLowerCase();
      if (subdomain && subdomain.length >= 3 && !fieldStatus.subdomain) {
        clearTimeout(subdomainTimeout);
        showFieldLoading(this);
        validateField('subdomain', subdomain, this);
      }
    });
  }

  // Validate Field via AJAX
  function validateField(field, value, inputElement) {
    const url = pageData.urls?.validate;
    if (!url) {
      console.error('Validation URL not found');
      hideFieldLoading(inputElement);
      return;
    }

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json'
      },
      body: JSON.stringify({
        field: field,
        [field]: value
      })
    })
      .then(response => response.json())
      .then(data => {
        hideFieldLoading(inputElement);

        if (data.success) {
          showFieldSuccess(inputElement, getSuccessMessage(field));
          fieldStatus[field] = true;
          checkStepValidation();
        } else if (data.errors && data.errors[field]) {
          showFieldError(inputElement, data.errors[field][0]);
          fieldStatus[field] = false;
        }
      })
      .catch(error => {
        hideFieldLoading(inputElement);
        console.error('Validation error:', error);

        // Try to parse error response
        if (error.response) {
          error.response.json().then(data => {
            if (data.errors && data.errors[field]) {
              showFieldError(inputElement, data.errors[field][0]);
            }
          }).catch(() => {});
        }
      });
  }

  function getSuccessMessage(field) {
    const labels = pageData.labels || {};
    const messages = {
      email: labels.emailAvailable || 'Email is available',
      phone: labels.phoneAvailable || 'Phone number is available',
      subdomain: labels.subdomainAvailable || 'Subdomain is available'
    };
    return messages[field] || 'Valid';
  }

  // Field Validation UI Helpers
  function clearFieldValidation(input) {
    input.classList.remove('is-valid', 'is-invalid');

    // Remove existing feedback
    const container = input.closest('.mb-3') || input.parentElement;
    container.querySelectorAll('.valid-feedback, .invalid-feedback').forEach(el => {
      if (!el.hasAttribute('data-server')) {
        el.remove();
      }
    });
  }

  function showFieldLoading(input) {
    const container = input.closest('.position-relative') || input.parentElement;
    let spinner = container.querySelector('.field-validation-spinner');

    if (spinner) {
      spinner.classList.add('show');
    }
  }

  function hideFieldLoading(input) {
    const container = input.closest('.position-relative') || input.parentElement;
    let spinner = container.querySelector('.field-validation-spinner');

    if (spinner) {
      spinner.classList.remove('show');
    }
  }

  function showFieldSuccess(input, message) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');

    const container = input.closest('.mb-3') || input.parentElement.parentElement;

    // Remove existing dynamic feedback
    container.querySelectorAll('.valid-feedback:not([data-server]), .invalid-feedback:not([data-server])').forEach(el => el.remove());

    const feedback = document.createElement('div');
    feedback.className = 'valid-feedback d-block';
    feedback.textContent = message;
    container.appendChild(feedback);
  }

  function showFieldError(input, message) {
    input.classList.remove('is-valid');
    input.classList.add('is-invalid');

    // Add shake animation
    input.classList.add('field-error');
    setTimeout(() => input.classList.remove('field-error'), 400);

    const container = input.closest('.mb-3') || input.parentElement.parentElement;

    // Remove existing dynamic feedback
    container.querySelectorAll('.valid-feedback:not([data-server]), .invalid-feedback:not([data-server])').forEach(el => el.remove());

    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback d-block';
    feedback.textContent = message;
    container.appendChild(feedback);
  }

  // Helper Functions
  function isValidEmailFormat(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // Step Validation
  function checkStep1Validation() {
    const firstName = document.getElementById('firstName')?.value.trim();
    const lastName = document.getElementById('lastName')?.value.trim();
    const gender = document.getElementById('gender')?.value;
    const phone = document.getElementById('phone')?.value.trim();
    const email = document.getElementById('email')?.value.trim();
    const password = document.getElementById('password')?.value;
    const passwordConfirm = document.getElementById('password_confirmation')?.value;

    const emailValid = fieldStatus.email || (email && document.getElementById('email')?.classList.contains('is-valid'));
    const phoneValid = fieldStatus.phone || (phone && document.getElementById('phone')?.classList.contains('is-valid'));

    stepValidation[1] =
      firstName &&
      lastName &&
      gender &&
      phone &&
      phoneValid &&
      email &&
      password &&
      password.length >= MIN_PASSWORD_LENGTH &&
      passwordConfirm &&
      password === passwordConfirm &&
      emailValid;

    return stepValidation[1];
  }

  function checkStep2Validation() {
    const companyName = document.getElementById('company_name')?.value.trim();
    const subdomain = document.getElementById('subdomain')?.value.trim();
    const terms = document.getElementById('terms')?.checked;

    const subdomainValid = fieldStatus.subdomain || (subdomain && document.getElementById('subdomain')?.classList.contains('is-valid'));

    stepValidation[2] = companyName && subdomain && subdomain.length >= 3 && terms && subdomainValid;

    return stepValidation[2];
  }

  function checkStepValidation() {
    checkStep1Validation();
    checkStep2Validation();
  }

  function validateCurrentStep() {
    if (!stepper) return true;

    const currentStep = stepper._currentIndex + 1;

    if (currentStep === 1) {
      const isValid = validateStep1Fields();
      if (!isValid) {
        return false;
      }
    } else if (currentStep === 2) {
      const isValid = validateStep2Fields();
      if (!isValid) {
        return false;
      }
    }

    return true;
  }

  function validateStep1Fields() {
    let isValid = true;
    const labels = pageData.labels || {};

    // First Name
    const firstName = document.getElementById('firstName');
    if (!firstName?.value.trim()) {
      showFieldError(firstName, labels.firstNameRequired || 'First name is required');
      isValid = false;
    } else {
      clearFieldError(firstName);
    }

    // Last Name
    const lastName = document.getElementById('lastName');
    if (!lastName?.value.trim()) {
      showFieldError(lastName, labels.lastNameRequired || 'Last name is required');
      isValid = false;
    } else {
      clearFieldError(lastName);
    }

    // Gender
    const gender = document.getElementById('gender');
    if (!gender?.value) {
      showFieldError(gender, labels.genderRequired || 'Please select a gender');
      isValid = false;
    } else {
      clearFieldError(gender);
    }

    // Phone
    const phone = document.getElementById('phone');
    if (!phone?.value.trim()) {
      showFieldError(phone, labels.phoneRequired || 'Phone number is required');
      isValid = false;
    } else if (!fieldStatus.phone && !phone.classList.contains('is-valid')) {
      showFieldError(phone, labels.phoneNotValidated || 'Please wait for phone validation');
      isValid = false;
    } else {
      clearFieldError(phone);
    }

    // Email
    const email = document.getElementById('email');
    if (!email?.value.trim()) {
      showFieldError(email, labels.emailRequired || 'Email address is required');
      isValid = false;
    } else if (!isValidEmailFormat(email.value.trim())) {
      showFieldError(email, labels.emailInvalid || 'Please enter a valid email address');
      isValid = false;
    } else if (!fieldStatus.email && !email.classList.contains('is-valid')) {
      showFieldError(email, labels.emailNotValidated || 'Please wait for email validation');
      isValid = false;
    } else {
      clearFieldError(email);
    }

    // Password
    const password = document.getElementById('password');
    if (!password?.value) {
      showFieldError(password, labels.passwordRequired || 'Password is required');
      isValid = false;
    } else if (password.value.length < MIN_PASSWORD_LENGTH) {
      showFieldError(password, labels.passwordTooShort || `Password must be at least ${MIN_PASSWORD_LENGTH} characters`);
      isValid = false;
    } else {
      clearFieldError(password);
    }

    // Password Confirmation
    const passwordConfirm = document.getElementById('password_confirmation');
    if (!passwordConfirm?.value) {
      showFieldError(passwordConfirm, labels.confirmPasswordRequired || 'Please confirm your password');
      isValid = false;
    } else if (password?.value !== passwordConfirm.value) {
      showFieldError(passwordConfirm, labels.passwordMismatch || 'Passwords do not match');
      isValid = false;
    } else {
      clearFieldError(passwordConfirm);
    }

    return isValid;
  }

  function validateStep2Fields() {
    let isValid = true;
    const labels = pageData.labels || {};

    // Company Name
    const companyName = document.getElementById('company_name');
    if (!companyName?.value.trim()) {
      showFieldError(companyName, labels.companyNameRequired || 'Company name is required');
      isValid = false;
    } else {
      clearFieldError(companyName);
    }

    // Subdomain
    const subdomain = document.getElementById('subdomain');
    if (!subdomain?.value.trim()) {
      showFieldError(subdomain, labels.subdomainRequired || 'Subdomain is required');
      isValid = false;
    } else if (subdomain.value.trim().length < 3) {
      showFieldError(subdomain, labels.subdomainTooShort || 'Subdomain must be at least 3 characters');
      isValid = false;
    } else if (!fieldStatus.subdomain && !subdomain.classList.contains('is-valid')) {
      showFieldError(subdomain, labels.subdomainNotValidated || 'Please wait for subdomain validation');
      isValid = false;
    } else {
      clearFieldError(subdomain);
    }

    // Terms
    const terms = document.getElementById('terms');
    if (!terms?.checked) {
      showFieldError(terms, labels.termsRequired || 'You must accept the terms and conditions');
      isValid = false;
    } else {
      clearFieldError(terms);
    }

    return isValid;
  }

  function clearFieldError(input) {
    if (!input) return;

    input.classList.remove('is-invalid');

    const container = input.closest('.mb-3') || input.closest('.mt-4') || input.parentElement.parentElement;
    if (container) {
      container.querySelectorAll('.invalid-feedback:not([data-server])').forEach(el => el.remove());
    }
  }

  function showStepError(message) {
    // Remove existing alerts
    document.querySelectorAll('.bs-stepper-content .alert').forEach(el => el.remove());

    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const content = document.querySelector('.bs-stepper-content');
    if (content) {
      content.insertBefore(alert, content.firstChild);

      setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
      }, 5000);
    }
  }

  // Wizard Navigation
  function initWizardNavigation() {
    // Next buttons
    document.querySelectorAll('.btn-next').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (validateCurrentStep() && stepper) {
          stepper.next();
        }
      });
    });

    // Previous buttons
    document.querySelectorAll('.btn-prev').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (stepper) {
          stepper.previous();
        }
      });
    });
  }

  // Form Submission
  function initFormSubmission() {
    const form = document.getElementById('formAuthentication');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      // Validate all steps with inline errors
      const step1Valid = validateStep1Fields();
      const step2Valid = validateStep2Fields();

      if (!step1Valid) {
        // Navigate to step 1 if there are errors
        if (stepper && stepper._currentIndex !== 0) {
          stepper.to(1);
        }
        return;
      }

      if (!step2Valid) {
        // Stay on step 2 to show errors
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      setButtonLoading(submitBtn, true);

      const formData = new FormData(form);

      fetch(pageData.urls.register, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json'
        },
        body: formData
      })
        .then(response => {
          if (!response.ok) {
            return response.json().then(data => {
              throw { status: response.status, data };
            });
          }
          return response.json();
        })
        .then(data => {
          setButtonLoading(submitBtn, false);

          if (data.success) {
            // Show success message
            showSuccessMessage(data.message);

            // Redirect after a short delay
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 1500);
          } else {
            showStepError(data.message || pageData.labels?.registrationFailed || 'Registration failed');
          }
        })
        .catch(error => {
          setButtonLoading(submitBtn, false);

          if (error.data && error.data.errors) {
            // Handle validation errors
            handleValidationErrors(error.data.errors);
          } else {
            showStepError(error.data?.message || pageData.labels?.registrationFailed || 'Registration failed. Please try again.');
          }
        });
    });
  }

  function setButtonLoading(button, loading) {
    if (!button) return;

    if (loading) {
      button.classList.add('btn-loading');
      button.disabled = true;
      const text = button.innerHTML;
      button.setAttribute('data-original-text', text);
      button.innerHTML = `
        <span class="btn-text">${text}</span>
        <span class="btn-spinner">
          <span class="spinner-border spinner-border-sm" role="status"></span>
        </span>
      `;
    } else {
      button.classList.remove('btn-loading');
      button.disabled = false;
      const originalText = button.getAttribute('data-original-text');
      if (originalText) {
        button.innerHTML = originalText;
      }
    }
  }

  function handleValidationErrors(errors) {
    Object.keys(errors).forEach(field => {
      const input = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
      if (input) {
        showFieldError(input, errors[field][0]);

        // Navigate to the step containing the error
        if (['firstName', 'lastName', 'gender', 'phone', 'email', 'password', 'password_confirmation'].includes(field)) {
          if (stepper && stepper._currentIndex !== 0) {
            stepper.to(1);
          }
        }
      }
    });
  }

  function showSuccessMessage(message) {
    // Remove existing alerts
    document.querySelectorAll('.bs-stepper-content .alert').forEach(el => el.remove());

    const alert = document.createElement('div');
    alert.className = 'alert alert-success fade show';
    alert.innerHTML = `
      <i class="bx bx-check-circle me-2"></i>
      ${message}
    `;

    const content = document.querySelector('.bs-stepper-content');
    if (content) {
      content.insertBefore(alert, content.firstChild);
    }
  }

  // Input Validation Listeners
  function initInputListeners() {
    // Step 1 fields
    ['firstName', 'lastName', 'gender', 'phone', 'password', 'password_confirmation'].forEach(id => {
      const input = document.getElementById(id);
      if (input) {
        input.addEventListener('input', checkStepValidation);
        input.addEventListener('change', checkStepValidation);
      }
    });

    // Step 2 fields
    ['company_name'].forEach(id => {
      const input = document.getElementById(id);
      if (input) {
        input.addEventListener('input', checkStepValidation);
      }
    });

    // Terms checkbox
    const termsCheckbox = document.getElementById('terms');
    if (termsCheckbox) {
      termsCheckbox.addEventListener('change', checkStepValidation);
    }
  }

  // Initialize everything
  function init() {
    initStepper();
    initPasswordToggle();
    initPasswordStrength();
    initEmailValidation();
    initPhoneValidation();
    initSubdomainValidation();
    initWizardNavigation();
    initFormSubmission();
    initInputListeners();
  }

  init();
});
