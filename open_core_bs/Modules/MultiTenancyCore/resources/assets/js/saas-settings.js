// SaaS Settings JavaScript

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize when jQuery is available
    if (typeof jQuery !== 'undefined') {
        jQuery(function($) {
            initializeSaasSettings($);
        });
    } else {
        console.error('jQuery is required for SaaS settings');
    }
});

function initializeSaasSettings($) {
    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Toggle gateway status
    $('.gateway-toggle').on('change', function() {
        const gateway = $(this).data('gateway');
        const enabled = $(this).is(':checked');
        const $toggle = $(this);

        $.ajax({
            url: pageData.urls.toggleGateway,
            type: 'POST',
            data: {
                gateway: gateway,
                enabled: enabled ? 1 : 0
            },
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                // Revert the toggle
                $toggle.prop('checked', !enabled);

                let errorMessage = 'Failed to update gateway status';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });

    // Update offline payment settings
    window.updateOfflineSettings = function(e) {
        e.preventDefault();

        const form = $('#offlinePaymentForm');
        const formData = new FormData(form[0]);

        $.ajax({
            url: pageData.urls.updateOffline,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to update settings';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    };

    // Update general settings
    window.updateGeneralSettings = function(e) {
        e.preventDefault();
        
        const form = $('#generalSettingsForm');
        const formData = new FormData(form[0]);
        
        // Convert checkboxes to boolean
        const checkboxes = [
            'allow_tenant_registration',
            'auto_approve_tenants',
            'require_email_verification',
            'enable_trial',
            'require_payment_for_trial',
            'tenant_auto_provisioning'
        ];
        
        checkboxes.forEach(function(checkbox) {
            formData.set(checkbox, $('#' + checkbox).is(':checked') ? 1 : 0);
        });
        
        $.ajax({
            url: pageData.urls.updateGeneral,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to update settings';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    };

    // Store Quill instances
    const quillEditors = {};
    
    // Email template functions
    window.editEmailTemplate = function(templateId) {
        $('#template-content-' + templateId).addClass('d-none');
        $('#template-form-' + templateId).removeClass('d-none');
        
        // Initialize Quill editor if not already initialized
        if (!quillEditors[templateId] && typeof Quill !== 'undefined') {
            const editorElement = document.querySelector('#editor-' + templateId);
            const bodyTextarea = document.querySelector('#body-' + templateId);
            
            if (editorElement && bodyTextarea) {
                quillEditors[templateId] = new Quill(editorElement, {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            ['link'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['clean']
                        ]
                    },
                    placeholder: 'Enter email body...'
                });
                
                // Set initial content (handle escaped newlines and convert to HTML)
                let initialContent = bodyTextarea.value;
                // Replace literal \n with actual newlines
                initialContent = initialContent.replace(/\\n/g, '\n');
                // Then convert actual newlines to <br> for HTML
                initialContent = initialContent.replace(/\n/g, '<br>');
                quillEditors[templateId].root.innerHTML = initialContent;
                
                // Update hidden textarea on change
                quillEditors[templateId].on('text-change', function() {
                    // Get plain text and preserve line breaks
                    const plainText = quillEditors[templateId].getText();
                    bodyTextarea.value = plainText.trim();
                });
            }
        }
    };
    
    window.cancelEditTemplate = function(templateId) {
        $('#template-content-' + templateId).removeClass('d-none');
        $('#template-form-' + templateId).addClass('d-none');
    };
    
    window.updateEmailTemplate = function(e, templateId) {
        e.preventDefault();
        
        const form = $(e.target);
        const formData = new FormData(form[0]);
        
        // Update body from Quill editor if exists
        if (quillEditors[templateId]) {
            const plainText = quillEditors[templateId].getText().trim();
            formData.set('body', plainText);
        }
        
        // Fix checkbox value
        const isActive = form.find('input[name="is_active"]').is(':checked');
        formData.delete('is_active');
        formData.append('is_active', isActive ? '1' : '0');
        formData.append('_method', 'PUT');
        
        $.ajax({
            url: '/multitenancy/admin/email-templates/' + templateId,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload to show updated content
                        window.location.reload();
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to update template'
                });
            }
        });
    };
    
    window.sendTestEmail = function(templateId) {
        Swal.fire({
            title: 'Send Test Email',
            input: 'email',
            inputLabel: 'Enter email address',
            inputPlaceholder: 'test@example.com',
            showCancelButton: true,
            confirmButtonText: 'Send Test',
            confirmButtonColor: '#696cff',
            inputValidator: (value) => {
                if (!value || !value.includes('@')) {
                    return 'Please enter a valid email address';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/multitenancy/admin/email-templates/' + templateId + '/test',
                    type: 'POST',
                    data: { email: result.value },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to send test email'
                        });
                    }
                });
            }
        });
    };
    
    window.testSelectedTemplate = function() {
        // Get all templates from the page
        const templates = [];
        document.querySelectorAll('[id^="template-content-"]').forEach(el => {
            const templateId = el.id.replace('template-content-', '');
            const card = el.closest('.card-body');
            if (card) {
                const nameElement = card.querySelector('h6');
                const categoryElement = card.closest('.card').parentElement.previousElementSibling;
                const isActive = card.querySelector('.badge.bg-label-success') !== null;
                
                if (nameElement) {
                    let category = 'General';
                    // Find the category heading
                    let prevSibling = card.closest('.card').parentElement.previousElementSibling;
                    while (prevSibling) {
                        if (prevSibling.tagName === 'H6' && prevSibling.classList.contains('text-uppercase')) {
                            category = prevSibling.textContent.trim();
                            break;
                        }
                        prevSibling = prevSibling.previousElementSibling;
                    }
                    
                    templates.push({
                        id: templateId,
                        name: nameElement.textContent.trim(),
                        category: category,
                        isActive: isActive
                    });
                }
            }
        });
        
        if (templates.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Templates Found',
                text: 'No email templates found.'
            });
            return;
        }
        
        // Build select options grouped by category
        const groupedTemplates = {};
        templates.forEach(template => {
            if (!groupedTemplates[template.category]) {
                groupedTemplates[template.category] = [];
            }
            groupedTemplates[template.category].push(template);
        });
        
        let selectOptions = '<option value="">-- Select a template --</option>';
        Object.keys(groupedTemplates).forEach(category => {
            selectOptions += `<optgroup label="${category}">`;
            groupedTemplates[category].forEach(template => {
                const status = template.isActive ? '' : ' (Inactive)';
                selectOptions += `<option value="${template.id}">${template.name}${status}</option>`;
            });
            selectOptions += '</optgroup>';
        });
        
        Swal.fire({
            title: 'Test Email Template',
            html: `
                <div class="mb-3">
                    <label class="form-label">Select Template</label>
                    <select id="test-template-select" class="form-select mb-3">
                        ${selectOptions}
                    </select>
                </div>
                <div>
                    <label class="form-label">Send To</label>
                    <input type="email" id="test-template-email" class="form-control" placeholder="Enter email address">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Send Test',
            confirmButtonColor: '#696cff',
            didOpen: () => {
                // Initialize select2 if available
                if (typeof $.fn.select2 !== 'undefined') {
                    $('#test-template-select').select2({
                        dropdownParent: $('.swal2-container'),
                        width: '100%'
                    });
                }
            },
            preConfirm: () => {
                const templateId = document.getElementById('test-template-select').value;
                const email = document.getElementById('test-template-email').value;
                
                if (!templateId) {
                    Swal.showValidationMessage('Please select a template');
                    return false;
                }
                
                if (!email || !email.includes('@')) {
                    Swal.showValidationMessage('Please enter a valid email address');
                    return false;
                }
                
                return { templateId, email };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const { templateId, email } = result.value;
                
                // Show loading
                Swal.fire({
                    title: 'Sending Test Email',
                    text: 'Please wait...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: '/multitenancy/admin/email-templates/' + templateId + '/test',
                    type: 'POST',
                    data: { email: email },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Test Email Sent',
                            text: response.message || `Test email sent successfully to ${email}`
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to send test email'
                        });
                    }
                });
            }
        });
    };
}