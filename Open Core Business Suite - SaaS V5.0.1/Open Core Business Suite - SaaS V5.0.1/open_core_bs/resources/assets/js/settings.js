$(function () {
  // Check if we're on the settings page
  if (typeof pageData === 'undefined' || !pageData.urls) {
    return; // Exit if pageData is not defined (not on settings page)
  }

  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Navigation handler with smooth transition
  $('#settingsNav a').on('click', function (e) {
    e.preventDefault();

    const section = $(this).data('section');

    // Update active state
    $('#settingsNav a').removeClass('active');
    $(this).addClass('active');

    // Smooth fade transition
    $('.settings-section').fadeOut(200, function () {
      $(this).addClass('d-none');
      $('#section-' + section).removeClass('d-none').hide().fadeIn(300);
    });

    // Update URL without reload
    if (history.pushState) {
      const newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname + '?section=' + section;
      window.history.pushState({ path: newUrl }, '', newUrl);
    }

    // Scroll to top smoothly
    $('html, body').animate({ scrollTop: 0 }, 300);
  });

  // Load section from URL parameter on page load
  const urlParams = new URLSearchParams(window.location.search);
  const activeSection = urlParams.get('section');

  if (activeSection) {
    $('#settingsNav a').removeClass('active');
    $(`#settingsNav a[data-section="${activeSection}"]`).addClass('active');
    $('.settings-section').addClass('d-none');
    $('#section-' + activeSection).removeClass('d-none');
  }

  // Helper function for drag and drop events
  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  // Drag and drop for app logo
  const logoUploadZone = document.getElementById('logoUploadZone');
  if (logoUploadZone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      logoUploadZone.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
      logoUploadZone.addEventListener(eventName, () => {
        logoUploadZone.style.borderColor = '#696cff';
        logoUploadZone.style.backgroundColor = '#f5f5ff';
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      logoUploadZone.addEventListener(eventName, () => {
        logoUploadZone.style.borderColor = '';
        logoUploadZone.style.backgroundColor = '';
      }, false);
    });

    logoUploadZone.addEventListener('drop', handleLogoDrop, false);

    function handleLogoDrop(e) {
      const dt = e.dataTransfer;
      const files = dt.files;
      if (files.length > 0) {
        document.getElementById('appLogo').files = files;
        $('#appLogo').trigger('change');
      }
    }

    // Click to upload
    logoUploadZone.addEventListener('click', () => {
      document.getElementById('appLogo').click();
    });
  }

  // App Logo preview with validation
  $('#appLogo').on('change', function (e) {
    const file = e.target.files[0];
    if (file) {
      // Validate file type
      const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
      if (!validTypes.includes(file.type)) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.invalidFileType,
          confirmButtonText: 'OK'
        });
        $(this).val('');
        return;
      }

      // Validate file size (2MB)
      if (file.size > 2 * 1024 * 1024) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.fileTooLarge,
          confirmButtonText: 'OK'
        });
        $(this).val('');
        return;
      }

      // Show file name
      $('#logoFileName').removeClass('d-none');
      $('#logoFileNameText').text(file.name);

      // Preview image with animation
      const reader = new FileReader();
      reader.onload = function (e) {
        $('#appLogoPreview').fadeOut(200, function () {
          $(this).attr('src', e.target.result).fadeIn(300);
        });
      };
      reader.readAsDataURL(file);
    }
  });

  // Drag and drop for favicon
  const faviconUploadZone = document.getElementById('faviconUploadZone');
  if (faviconUploadZone) {
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      faviconUploadZone.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
      faviconUploadZone.addEventListener(eventName, () => {
        faviconUploadZone.style.borderColor = '#00cfe8';
        faviconUploadZone.style.backgroundColor = '#f0fcff';
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      faviconUploadZone.addEventListener(eventName, () => {
        faviconUploadZone.style.borderColor = '';
        faviconUploadZone.style.backgroundColor = '';
      }, false);
    });

    faviconUploadZone.addEventListener('drop', handleFaviconDrop, false);

    function handleFaviconDrop(e) {
      const dt = e.dataTransfer;
      const files = dt.files;
      if (files.length > 0) {
        document.getElementById('appFavicon').files = files;
        $('#appFavicon').trigger('change');
      }
    }

    // Click to upload
    faviconUploadZone.addEventListener('click', () => {
      document.getElementById('appFavicon').click();
    });
  }

  // App Favicon preview with validation
  $('#appFavicon').on('change', function (e) {
    const file = e.target.files[0];
    if (file) {
      // Validate file type
      const validTypes = ['image/x-icon', 'image/png', 'image/jpeg', 'image/jpg', 'image/vnd.microsoft.icon'];
      if (!validTypes.includes(file.type)) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.invalidFileType,
          confirmButtonText: 'OK'
        });
        $(this).val('');
        return;
      }

      // Validate file size (512KB)
      if (file.size > 512 * 1024) {
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: pageData.labels.fileTooLarge,
          confirmButtonText: 'OK'
        });
        $(this).val('');
        return;
      }

      // Show file name
      $('#faviconFileName').removeClass('d-none');
      $('#faviconFileNameText').text(file.name);

      // Preview image with animation
      const reader = new FileReader();
      reader.onload = function (e) {
        $('#appFaviconPreview').fadeOut(200, function () {
          $(this).attr('src', e.target.result).fadeIn(300);
        });
      };
      reader.readAsDataURL(file);
    }
  });

  // Reset logo preview button
  $('#resetLogoBtn').on('click', function () {
    $('#appLogo').val('');
    $('#appLogoPreview').attr('src', $('#appLogoPreview').data('original-src') || '/assets/img/logo.png');
  });

  // Reset favicon preview button
  $('#resetFaviconBtn').on('click', function () {
    $('#appFavicon').val('');
    $('#appFaviconPreview').attr('src', $('#appFaviconPreview').data('original-src') || '/assets/img/favicon/favicon.ico');
  });

  // Store original preview sources on page load
  if ($('#appLogoPreview').length) {
    $('#appLogoPreview').data('original-src', $('#appLogoPreview').attr('src'));
  }
  if ($('#appFaviconPreview').length) {
    $('#appFaviconPreview').data('original-src', $('#appFaviconPreview').attr('src'));
  }

  // Company logo preview
  $('#companyLogo').on('change', function (e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        $('#companyLogoPreview').attr('src', e.target.result);
      };
      reader.readAsDataURL(file);
    }
  });

  // Remove logo button
  $('#removeLogoButton').on('click', function () {
    $('#companyLogoPreview').attr('src', 'https://placehold.co/150x150');
    $('#companyLogo').val('');
  });

  // Update switch label text on change
  $('input[type="checkbox"].form-check-input').on('change', function () {
    const label = $(this).next('.form-check-label');
    if (label.length) {
      const isChecked = $(this).is(':checked');
      const enabledText = label.data('enabled-text') || pageData.labels.enabled || 'Enabled';
      const disabledText = label.data('disabled-text') || pageData.labels.disabled || 'Disabled';

      // Only update if label contains just the status text
      if (label.text().trim() === enabledText || label.text().trim() === disabledText) {
        label.text(isChecked ? enabledText : disabledText);
      }
    }
  });

  // General Settings Form
  $('#form-general').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {};

    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }

    // Handle checkbox for helper text
    data.isHelperTextEnabled = $('#isHelperTextEnabled').is(':checked') ? 'on' : 'off';

    submitSettings(pageData.urls.updateGeneral, data);
  });

  // Branding Settings Form
  $('#form-branding').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Check if at least one file is selected
    const hasLogo = $('#appLogo')[0].files.length > 0;
    const hasFavicon = $('#appFavicon')[0].files.length > 0;

    if (!hasLogo && !hasFavicon) {
      Swal.fire({
        icon: 'warning',
        title: pageData.labels.error,
        text: 'Please select at least one file to upload',
        confirmButtonText: 'OK'
      });
      return;
    }

    submitSettings(pageData.urls.updateBranding, formData, true);
  });

  // Company Settings Form
  $('#form-company').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    submitSettings(pageData.urls.updateCompany, formData, true);
  });

  // Employee Settings Form
  $('#form-employee').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {};

    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }

    submitSettings(pageData.urls.updateEmployee, data);
  });

  // Maps Settings Form
  $('#form-maps').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {};

    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }

    submitSettings(pageData.urls.updateMaps, data);
  });

  // Code Prefix Settings Form - Note: No backend route yet, using redirect
  $('#form-code-prefix').on('submit', function (e) {
    e.preventDefault();

    Swal.fire({
      icon: 'info',
      title: 'Feature Not Implemented',
      text: 'Code prefix settings update endpoint is not yet configured.',
      timer: 3000,
      showConfirmButton: false
    });
  });

  // Mail Settings Form
  $('#form-mail').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {};

    for (let [key, value] of formData.entries()) {
      data[key] = value;
    }

    submitSettings(pageData.urls.updateMail, data);
  });

  // Send Test Email Button
  $('#sendTestEmailBtn').on('click', function () {
    const testEmail = $('#test_email').val();

    if (!testEmail) {
      Swal.fire({
        icon: 'warning',
        title: pageData.labels.error || 'Error',
        text: 'Please enter an email address',
        confirmButtonText: 'OK'
      });
      return;
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(testEmail)) {
      Swal.fire({
        icon: 'warning',
        title: pageData.labels.error || 'Error',
        text: 'Please enter a valid email address',
        confirmButtonText: 'OK'
      });
      return;
    }

    // Show loading state
    const btn = $(this);
    const originalHtml = btn.html();
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Sending...');

    $.ajax({
      url: pageData.urls.sendTestEmail,
      method: 'POST',
      data: {
        test_email: testEmail
      },
      success: function (response) {
        Swal.fire({
          icon: 'success',
          title: pageData.labels.success || 'Success',
          text: response.message,
          timer: 3000,
          showConfirmButton: true
        });
      },
      error: function (xhr) {
        let message = 'Failed to send test email';

        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        } else if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          message = Object.values(xhr.responseJSON.errors).flat().join('\n');
        }

        Swal.fire({
          icon: 'error',
          title: pageData.labels.error || 'Error',
          html: message.replace(/\n/g, '<br>'),
          confirmButtonText: 'OK'
        });
      },
      complete: function () {
        // Restore button state
        btn.prop('disabled', false).html(originalHtml);
      }
    });
  });

  /**
   * Submit settings to backend
   * @param {string} url - The API endpoint URL
   * @param {Object|FormData} data - The form data to submit
   * @param {boolean} isFormData - Whether data is FormData object (for file uploads)
   */
  function submitSettings(url, data, isFormData = false) {
    // Find the submit button in the active form
    const $activeForm = $('.settings-section:not(.d-none)').find('form');
    const $submitBtn = $activeForm.find('button[type="submit"]');
    const originalBtnHtml = $submitBtn.html();

    // Show loading state on button
    $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

    const ajaxConfig = {
      url: url,
      method: 'POST',
      success: function (response) {
        // Restore button
        $submitBtn.prop('disabled', false).html(originalBtnHtml);

        // Show success with confetti animation
        Swal.fire({
          icon: 'success',
          title: pageData.labels.success,
          text: pageData.labels.settingsUpdated,
          timer: 2000,
          showConfirmButton: false,
          showClass: {
            popup: 'animate__animated animate__fadeInDown'
          },
          hideClass: {
            popup: 'animate__animated animate__fadeOutUp'
          }
        }).then(() => {
          // Reload page to reflect changes with smooth transition
          $('body').fadeOut(300, function () {
            window.location.reload();
          });
        });
      },
      error: function (xhr) {
        // Restore button
        $submitBtn.prop('disabled', false).html(originalBtnHtml);

        let message = pageData.labels.errorOccurred;

        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        } else if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
          message = Object.values(xhr.responseJSON.errors).flat().join('\n');
        }

        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          html: message.replace(/\n/g, '<br>'),
          confirmButtonText: 'OK',
          showClass: {
            popup: 'animate__animated animate__shakeX'
          }
        });
      }
    };

    // If FormData (for file uploads), don't set processData and contentType
    if (isFormData) {
      ajaxConfig.data = data;
      ajaxConfig.processData = false;
      ajaxConfig.contentType = false;
    } else {
      ajaxConfig.data = data;
    }

    $.ajax(ajaxConfig);
  }

  // Add hover effect to upload zones
  $('.upload-zone').hover(
    function () {
      $(this).css('border-color', '#696cff');
    },
    function () {
      $(this).css('border-color', '');
    }
  );

  // Add smooth scroll behavior
  $('html').css('scroll-behavior', 'smooth');
});
