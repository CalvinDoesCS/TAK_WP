/**
 * ProductOrder - Category Management
 */

'use strict';

$(function () {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize DataTable
    const categoryTable = initializeDataTable();

    // Initialize Select2
    initializeSelect2();

    // Bind Events
    bindButtonEvents();

    // Load parent categories for dropdown
    loadParentCategories();
});

/**
 * Initialize DataTable with server-side processing
 */
function initializeDataTable() {
    return $('#productCategoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: pageData.urls.datatable,
            type: 'GET',
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { data: 'code', name: 'code' },
            { data: 'description', name: 'description' },
            { data: 'parent', name: 'parent.name', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'created_by', name: 'createdBy.first_name', orderable: false },
            { data: 'updated_by', name: 'updatedBy.first_name', orderable: false },
            {
                data: 'created_at',
                name: 'created_at',
                render: function (data) {
                    return data ? new Date(data).toLocaleDateString() : '-';
                }
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        language: {
            search: pageData.labels.search,
            processing: pageData.labels.processing,
            lengthMenu: pageData.labels.lengthMenu,
            info: pageData.labels.info,
            infoEmpty: pageData.labels.infoEmpty,
            emptyTable: pageData.labels.emptyTable,
            paginate: pageData.labels.paginate
        }
    });
}

/**
 * Initialize Select2 dropdowns
 */
function initializeSelect2() {
    $('#parent_id').select2({
        placeholder: pageData.labels.selectParent || 'Select Parent Category',
        allowClear: true,
        dropdownParent: $('#categoryOffcanvas')
    });
}

/**
 * Bind button and form events
 */
function bindButtonEvents() {
    // Add Category button
    $('#addCategoryBtn').on('click', function () {
        openOffcanvas();
    });

    // Form submission
    $('#categoryForm').on('submit', function (e) {
        e.preventDefault();
        saveCategory();
    });

    // Offcanvas hidden event - reset form
    $('#categoryOffcanvas').on('hidden.bs.offcanvas', function () {
        resetForm();
    });
}

/**
 * Load parent categories for select dropdown
 */
function loadParentCategories() {
    $.ajax({
        url: pageData.urls.getParents,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const $select = $('#parent_id');
                $select.empty();
                $select.append('<option value="">' + (pageData.labels.noneRootCategory || 'None (Root Category)') + '</option>');

                response.data.forEach(function (category) {
                    $select.append(
                        $('<option>', {
                            value: category.id,
                            text: category.name + ' (' + category.code + ')'
                        })
                    );
                });
            }
        },
        error: function (xhr) {
            console.error('Failed to load parent categories');
        }
    });
}

/**
 * Open offcanvas for add/edit
 */
function openOffcanvas(categoryId = null, parentId = null) {
    const offcanvasElement = document.getElementById('categoryOffcanvas');
    const offcanvas = new bootstrap.Offcanvas(offcanvasElement);

    if (categoryId) {
        // Edit mode
        $('#categoryOffcanvasLabel').text(pageData.labels.editCategory);
        loadCategory(categoryId);
    } else if (parentId) {
        // Add subcategory mode
        $('#categoryOffcanvasLabel').text(pageData.labels.addSubcategory);
        $('#parent_id').val(parentId).trigger('change');
    } else {
        // Add mode
        $('#categoryOffcanvasLabel').text(pageData.labels.addCategory);
    }

    offcanvas.show();
}

/**
 * Load category data for editing
 */
function loadCategory(categoryId) {
    const url = pageData.urls.getById.replace(':id', categoryId);

    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            if (response.status === 'success') {
                const data = response.data;
                $('#categoryId').val(data.id);
                $('#name').val(data.name);
                $('#code').val(data.code);
                $('#description').val(data.description);
                $('#parent_id').val(data.parent_id).trigger('change');
                $('#status').prop('checked', data.status === 'active');
            }
        },
        error: function (xhr) {
            handleAjaxError(xhr);
        }
    });
}

/**
 * Save category (create or update)
 */
function saveCategory() {
    const categoryId = $('#categoryId').val();
    const isEdit = categoryId !== '';

    const formData = {
        name: $('#name').val(),
        code: $('#code').val(),
        description: $('#description').val(),
        parent_id: $('#parent_id').val() || null,
        status: $('#status').is(':checked') ? '1' : '0'
    };

    if (isEdit) {
        formData.id = categoryId;
    }

    const url = isEdit
        ? pageData.urls.update.replace(':id', categoryId)
        : pageData.urls.store;

    const method = isEdit ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function (response) {
            if (response.status === 'success') {
                // Hide offcanvas
                const offcanvasElement = document.getElementById('categoryOffcanvas');
                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                offcanvas.hide();

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.success,
                    text: isEdit ? pageData.labels.categoryUpdated : pageData.labels.categoryAdded,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Reload table
                $('#productCategoryTable').DataTable().ajax.reload();

                // Reload parent categories
                loadParentCategories();

                // Reset form
                resetForm();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: response.data || pageData.labels.validationError
                });
            }
        },
        error: function (xhr) {
            handleAjaxError(xhr);
        }
    });
}

/**
 * Reset form fields
 */
function resetForm() {
    $('#categoryForm')[0].reset();
    $('#categoryId').val('');
    $('#parent_id').val('').trigger('change');
    $('#status').prop('checked', true);
}

/**
 * Edit category - called from DataTable actions
 */
window.editCategory = function (categoryId) {
    openOffcanvas(categoryId);
};

/**
 * Add subcategory - called from DataTable actions
 */
window.addSubcategory = function (parentId) {
    openOffcanvas(null, parentId);
};

/**
 * Toggle category status - called from DataTable actions
 */
window.toggleStatus = function (categoryId) {
    const url = pageData.urls.changeStatus.replace(':id', categoryId);

    $.ajax({
        url: url,
        method: 'POST',
        success: function (response) {
            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: pageData.labels.success,
                    text: pageData.labels.statusChanged,
                    timer: 2000,
                    showConfirmButton: false
                });

                // Reload table
                $('#productCategoryTable').DataTable().ajax.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: pageData.labels.error,
                    text: response.data || pageData.labels.error
                });
            }
        },
        error: function (xhr) {
            handleAjaxError(xhr);
        }
    });
};

/**
 * Delete category - called from DataTable actions
 */
window.deleteCategory = function (categoryId) {
    Swal.fire({
        title: pageData.labels.confirmDelete,
        text: pageData.labels.deleteWarning,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.yes,
        cancelButtonText: pageData.labels.no,
        customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            const url = pageData.urls.delete.replace(':id', categoryId);

            $.ajax({
                url: url,
                method: 'DELETE',
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.deleted,
                            text: pageData.labels.categoryDeleted,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Reload table
                        $('#productCategoryTable').DataTable().ajax.reload();

                        // Reload parent categories
                        loadParentCategories();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.data || pageData.labels.error
                        });
                    }
                },
                error: function (xhr) {
                    handleAjaxError(xhr);
                }
            });
        }
    });
};

/**
 * Handle AJAX errors
 */
function handleAjaxError(xhr) {
    if (xhr.status === 422) {
        // Validation errors
        const errors = xhr.responseJSON.errors;
        let errorMessage = '';

        Object.keys(errors).forEach(field => {
            errorMessage += errors[field][0] + '<br>';
        });

        Swal.fire({
            icon: 'error',
            title: pageData.labels.validationError || 'Validation Error',
            html: errorMessage
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: pageData.labels.error,
            text: xhr.responseJSON?.data || pageData.labels.error
        });
    }
}
