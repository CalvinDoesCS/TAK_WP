/**
 * Plan Form JavaScript
 * Handles create/edit plan form functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Select/Deselect all modules buttons
    const selectAllBtn = document.getElementById('selectAllModules');
    const deselectAllBtn = document.getElementById('deselectAllModules');

    // Handle Allow All Modules toggle
    const allowAllToggle = document.getElementById('allow_all_modules');
    const addonModulesSection = document.getElementById('addon-modules-section');

    function updateModuleSectionState() {
        const moduleCheckboxes = document.querySelectorAll('.module-checkbox');

        if (allowAllToggle && allowAllToggle.checked) {
            // Disable module checkboxes and dim the section
            moduleCheckboxes.forEach(function(checkbox) {
                checkbox.disabled = true;
            });
            if (addonModulesSection) {
                addonModulesSection.classList.add('opacity-50');
            }
            // Disable select/deselect buttons
            if (selectAllBtn) selectAllBtn.disabled = true;
            if (deselectAllBtn) deselectAllBtn.disabled = true;
        } else {
            // Enable module checkboxes
            moduleCheckboxes.forEach(function(checkbox) {
                checkbox.disabled = false;
            });
            if (addonModulesSection) {
                addonModulesSection.classList.remove('opacity-50');
            }
            // Enable select/deselect buttons
            if (selectAllBtn) selectAllBtn.disabled = false;
            if (deselectAllBtn) deselectAllBtn.disabled = false;
        }
    }

    if (allowAllToggle) {
        allowAllToggle.addEventListener('change', updateModuleSectionState);
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const checkboxes = document.querySelectorAll('.module-checkbox');
            checkboxes.forEach(function(checkbox) {
                if (!checkbox.disabled) {
                    checkbox.checked = true;
                }
            });
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const checkboxes = document.querySelectorAll('.module-checkbox');
            checkboxes.forEach(function(checkbox) {
                if (!checkbox.disabled) {
                    checkbox.checked = false;
                }
            });
        });
    }

    // Initialize module section state on page load
    updateModuleSectionState();

    // Form submission
    $('#planForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const formData = new FormData(this);

        // Fix checkbox value for is_active
        const isActive = $('#is_active').is(':checked');
        formData.delete('is_active');
        formData.append('is_active', isActive ? '1' : '0');

        const url = form.attr('action');
        const methodInput = form.find('input[name="_method"]').val();

        // For PUT/PATCH requests, use POST and include _method in formData
        if (methodInput) {
            formData.append('_method', methodInput);
        }

        $.ajax({
            url: url,
            method: 'POST', // Always use POST for FormData
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: pageData.translations.success,
                        text: response.data?.message || pageData.translations.success,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    }).then(() => {
                        window.location.href = pageData.routes.indexUrl;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.translations.error,
                        text: response.data?.message || response.message || pageData.translations.errorOccurred,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = pageData.translations.errorOccurred;

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }

                Swal.fire({
                    icon: 'error',
                    title: pageData.translations.error,
                    html: errorMessage,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            }
        });
    });
});
