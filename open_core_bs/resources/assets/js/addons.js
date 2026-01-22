/**
 * Addons Management JavaScript
 * Handles DataTable initialization, module actions, dependency validation, and statistics
 */

$(function () {
  'use strict';

  // CSRF Token Setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  let addonsTable;
  let currentModuleName = null;
  let categoryFilter = '';
  let statusFilter = '';

  // Initialize
  loadStatistics();
  initializeDataTable();
  bindEventHandlers();

  /**
   * Load Statistics from Backend
   */
  function loadStatistics() {
    $.ajax({
      url: pageData.urls.statistics,
      method: 'GET',
      success: function (response) {
        if (response.success) {
          $('#total-modules').text(response.data.total_modules || 0);
          $('#active-modules').text(response.data.active_modules || 0);

          // Count categories
          const categoryCount = response.data.categories ? Object.keys(response.data.categories).length : 0;
          $('#total-categories').text(categoryCount);

          $('#disabled-modules').text(response.data.disabled_modules || 0);

          // Populate category filter
          if (response.data.categories) {
            populateCategoryFilter(Object.keys(response.data.categories));
          }
        }
      },
      error: function (xhr) {
        console.error('Failed to load statistics:', xhr);
      }
    });
  }

  /**
   * Populate Category Filter Dropdown
   */
  function populateCategoryFilter(categories) {
    const $menu = $('#categoryFilterMenu');

    categories.forEach(function (category) {
      $menu.append(`
        <li>
          <a class="dropdown-item category-filter-item" href="javascript:void(0);" data-category="${category}">
            ${category}
          </a>
        </li>
      `);
    });
  }

  /**
   * Initialize DataTable
   */
  function initializeDataTable() {
    addonsTable = $('#addons-table').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: pageData.urls.datatable,
        data: function (d) {
          d.category = categoryFilter;
          d.status = statusFilter;
        }
      },
      columns: [
        {
          data: 'displayName',
          name: 'displayName',
          orderable: true,
          render: function (data, type, row) {
            return `
              <div class="d-flex align-items-center">
                <div class="me-3">
                  <i class="bx bx-category-alt bx-md text-primary"></i>
                </div>
                <div>
                  <h6 class="mb-0 fw-semibold">${row.displayName || row.name}</h6>
                  <small class="text-muted">${row.description || 'No description available'}</small>
                </div>
              </div>
            `;
          }
        },
        {
          data: 'category',
          name: 'category',
          orderable: true,
          searchable: false
        },
        {
          data: 'version',
          name: 'version',
          orderable: true,
          render: function (data) {
            return data || '<span class="text-muted">N/A</span>';
          }
        },
        {
          data: 'dependencies_count',
          name: 'dependencies_count',
          orderable: false,
          searchable: false
        },
        {
          data: 'isEnabled',
          name: 'isEnabled',
          orderable: true,
          searchable: false
        },
        {
          data: 'actions',
          name: 'actions',
          orderable: false,
          searchable: false
        }
      ],
      order: [[0, 'asc']],
      pageLength: 100,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
      responsive: true,
      language: {
        search: '',
        searchPlaceholder: 'Search modules...',
        lengthMenu: '_MENU_',
        info: 'Showing _START_ to _END_ of _TOTAL_ modules',
        infoEmpty: 'No modules found',
        infoFiltered: '(filtered from _MAX_ total modules)',
        paginate: {
          first: 'First',
          last: 'Last',
          next: 'Next',
          previous: 'Previous'
        }
      },
      drawCallback: function () {
        // Initialize tooltips after table draw
        const tooltipTriggerList = [].slice.call(
          document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      }
    });
  }

  /**
   * Render Actions Dropdown
   */
  function renderActionsDropdown(id, actions) {
    let html = `
      <div class="dropdown">
        <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bx bx-dots-vertical-rounded"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
    `;

    actions.forEach(function (action) {
      if (action.divider) {
        html += '<li><hr class="dropdown-divider"></li>';
      } else {
        const cssClass = action.class || '';
        html += `
          <li>
            <a class="dropdown-item ${cssClass}" href="javascript:void(0);" onclick="${action.onclick}">
              <i class="${action.icon} me-2"></i>
              <span>${action.label}</span>
            </a>
          </li>
        `;
      }
    });

    html += `
        </ul>
      </div>
    `;

    return html;
  }

  /**
   * Bind Event Handlers
   */
  function bindEventHandlers() {
    // Category Filter
    $(document).on('click', '.category-filter-item', function (e) {
      e.preventDefault();
      const category = $(this).data('category');
      const label = $(this).text();

      categoryFilter = category;
      $('#categoryFilterLabel').text(label);

      // Update active state
      $('.category-filter-item').removeClass('active');
      $(this).addClass('active');

      // Reload table
      addonsTable.ajax.reload();
    });

    // Status Filter
    $(document).on('click', '.status-filter-item', function (e) {
      e.preventDefault();
      const status = $(this).data('status');
      const label = $(this).text();

      statusFilter = status;
      $('#statusFilterLabel').text(label);

      // Update active state
      $('.status-filter-item').removeClass('active');
      $(this).addClass('active');

      // Reload table
      addonsTable.ajax.reload();
    });

    // Module actions from offcanvas
    $('#module-enable-btn').on('click', function () {
      if (currentModuleName) {
        enableModule(currentModuleName);
      }
    });

    $('#module-disable-btn').on('click', function () {
      if (currentModuleName) {
        disableModule(currentModuleName);
      }
    });

    $('#module-uninstall-btn').on('click', function () {
      if (currentModuleName) {
        uninstallModule(currentModuleName);
      }
    });

    $('#module-configure-btn').on('click', function () {
      if (currentModuleName) {
        configureModule(currentModuleName);
      }
    });
  }

  /**
   * View Module Details in Offcanvas
   */
  window.viewModuleDetails = function (moduleName) {
    currentModuleName = moduleName;

    // Fetch module details
    $.ajax({
      url: pageData.urls.show.replace(':module', moduleName),
      method: 'GET',
      success: function (response) {
        if (response.success) {
          const module = response.data;

          // Populate offcanvas
          $('#module-detail-name').text(module.displayName || module.name);
          $('#module-detail-version').text('Version ' + (module.version || '1.0.0'));
          $('#module-detail-description').text(module.description || 'No description available');
          $('#module-detail-category').text(module.category || 'General');

          // Status
          const statusBadge = $('#module-detail-status');
          if (module.enabled) {
            statusBadge.removeClass('bg-label-danger').addClass('bg-label-success').text(pageData.labels.enabled);
            $('#module-enable-btn').hide();
            $('#module-disable-btn').show();
          } else {
            statusBadge.removeClass('bg-label-success').addClass('bg-label-danger').text(pageData.labels.disabled);
            $('#module-enable-btn').show();
            $('#module-disable-btn').hide();
          }

          // Dependencies
          if (module.dependencies && module.dependencies.length > 0) {
            let dependenciesHtml = '<ul class="list-unstyled mb-0">';
            module.dependencies.forEach(function (dep) {
              const displayName = dep.displayName || dep.name;
              const icon = dep.enabled ? 'bx-check-circle text-success' : 'bx-x-circle text-danger';
              const statusText = dep.enabled ? 'Enabled' : (dep.installed ? 'Disabled' : 'Not Installed');
              dependenciesHtml += `
                <li class="mb-2">
                  <i class='bx ${icon} me-2'></i>${displayName}
                  <small class="text-muted ms-1">(${statusText})</small>
                </li>
              `;
            });
            dependenciesHtml += '</ul>';
            $('#module-detail-dependencies').html(dependenciesHtml);
          } else {
            $('#module-detail-dependencies').html('<p class="text-muted small">No dependencies</p>');
          }

          // Dependents
          if (module.dependents && module.dependents.length > 0) {
            let dependentsHtml = '<ul class="list-unstyled mb-0">';
            module.dependents.forEach(function (dep) {
              const displayName = dep.displayName || dep.name;
              const icon = dep.enabled ? 'bx-check-circle text-success' : 'bx-x-circle text-muted';
              const statusText = dep.enabled ? 'Enabled' : 'Disabled';
              dependentsHtml += `
                <li class="mb-2">
                  <i class='bx ${icon} me-2'></i>${displayName}
                  <small class="text-muted ms-1">(${statusText})</small>
                </li>
              `;
            });
            dependentsHtml += '</ul>';
            $('#module-detail-dependents').html(dependentsHtml);
          } else {
            $('#module-detail-dependents').html('<p class="text-muted small">No modules depend on this</p>');
          }

          // Purchase link (always show for premium modules)
          if (module.purchaseUrl && !module.isCoreModule) {
            $('#purchase-section').show();
            $('#module-detail-purchase-link').attr('href', module.purchaseUrl);
          } else {
            $('#purchase-section').hide();
          }

          // Hide configure button by default (will be implemented later)
          $('#module-configure-btn').hide();

          // Show offcanvas
          const offcanvas = new bootstrap.Offcanvas(document.getElementById('moduleDetailsOffcanvas'));
          offcanvas.show();
        }
      },
      error: function (xhr) {
        showErrorAlert('Failed to load module details');
      }
    });
  };

  /**
   * Alias for viewModuleDetails (used by controller)
   */
  window.showModuleDetails = window.viewModuleDetails;

  /**
   * Enable Module with Dependency Validation
   */
  window.enableModule = function (moduleName) {
    if (pageData.isDemoMode) {
      showErrorAlert(pageData.labels.demoModeRestriction);
      return;
    }

    // Check dependencies first
    $.ajax({
      url: pageData.urls.checkDependencies.replace(':module', moduleName),
      method: 'POST',
      success: function (response) {
        if (response.success) {
          if (response.data.canEnable) {
            // No missing dependencies, proceed with enable
            confirmEnableModule(moduleName);
          } else {
            // Show missing dependencies
            const missingNames = response.data.missing.map(dep => dep.displayName || dep.name);
            showDependencyWarning(moduleName, missingNames);
          }
        }
      },
      error: function (xhr) {
        showErrorAlert('Failed to check dependencies');
      }
    });
  };

  /**
   * Confirm Enable Module
   */
  function confirmEnableModule(moduleName) {
    Swal.fire({
      title: pageData.labels.confirmTitle,
      text: pageData.labels.enableConfirm,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: pageData.labels.confirmButtonText,
      cancelButtonText: pageData.labels.cancelButtonText,
      customClass: {
        confirmButton: 'btn btn-success me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        performEnableModule(moduleName);
      }
    });
  }

  /**
   * Perform Enable Module AJAX
   */
  function performEnableModule(moduleName) {
    $.ajax({
      url: pageData.urls.enable,
      method: 'POST',
      data: {
        module: moduleName
      },
      success: function (response) {
        if (response.success) {
          showSuccessAlert(response.message || pageData.labels.enableSuccess);
          addonsTable.ajax.reload(null, false);
          loadStatistics();

          // Close offcanvas if open
          const offcanvasElement = document.getElementById('moduleDetailsOffcanvas');
          const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
          if (offcanvas) {
            offcanvas.hide();
          }
        } else {
          showErrorAlert(response.message || 'Failed to enable module');
        }
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'Failed to enable module';
        showErrorAlert(message);
      }
    });
  }

  /**
   * Show Dependency Warning
   */
  function showDependencyWarning(moduleName, missingDeps) {
    const depList = missingDeps.map(dep => `<li>${dep}</li>`).join('');

    Swal.fire({
      title: pageData.labels.dependenciesRequired,
      html: `
        <p>${pageData.labels.dependenciesMissing}</p>
        <ul class="text-start">${depList}</ul>
      `,
      icon: 'warning',
      confirmButtonText: 'OK',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  /**
   * Disable Module with Dependents Check
   */
  window.disableModule = function (moduleName) {
    if (pageData.isDemoMode) {
      showErrorAlert(pageData.labels.demoModeRestriction);
      return;
    }

    // Check dependents first
    $.ajax({
      url: pageData.urls.checkDependencies.replace(':module', moduleName),
      method: 'POST',
      success: function (response) {
        if (response.success) {
          if (response.data.canDisable) {
            // No active dependents, proceed with disable
            confirmDisableModule(moduleName);
          } else {
            // Show active dependents
            showDependentsWarning(moduleName, response.data.dependents);
          }
        }
      },
      error: function (xhr) {
        showErrorAlert('Failed to check dependents');
      }
    });
  };

  /**
   * Confirm Disable Module
   */
  function confirmDisableModule(moduleName) {
    Swal.fire({
      title: pageData.labels.confirmTitle,
      text: pageData.labels.disableConfirm,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: pageData.labels.confirmButtonText,
      cancelButtonText: pageData.labels.cancelButtonText,
      customClass: {
        confirmButton: 'btn btn-warning me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        performDisableModule(moduleName);
      }
    });
  }

  /**
   * Perform Disable Module AJAX
   */
  function performDisableModule(moduleName) {
    $.ajax({
      url: pageData.urls.disable,
      method: 'POST',
      data: {
        module: moduleName
      },
      success: function (response) {
        if (response.success) {
          showSuccessAlert(response.message || pageData.labels.disableSuccess);
          addonsTable.ajax.reload(null, false);
          loadStatistics();

          // Close offcanvas if open
          const offcanvasElement = document.getElementById('moduleDetailsOffcanvas');
          const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
          if (offcanvas) {
            offcanvas.hide();
          }
        } else {
          showErrorAlert(response.message || 'Failed to disable module');
        }
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'Failed to disable module';
        showErrorAlert(message);
      }
    });
  }

  /**
   * Show Dependents Warning
   */
  function showDependentsWarning(moduleName, dependents) {
    const depList = dependents.map(dep => `<li>${dep}</li>`).join('');

    Swal.fire({
      title: pageData.labels.hasDependents,
      html: `
        <p>${pageData.labels.dependentsActive}</p>
        <ul class="text-start">${depList}</ul>
      `,
      icon: 'error',
      confirmButtonText: 'OK',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  /**
   * Uninstall Module
   */
  window.uninstallModule = function (moduleName) {
    if (pageData.isDemoMode) {
      showErrorAlert(pageData.labels.demoModeRestriction);
      return;
    }

    Swal.fire({
      title: pageData.labels.confirmTitle,
      text: pageData.labels.uninstallConfirm,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, uninstall it!',
      cancelButtonText: pageData.labels.cancelButtonText,
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        performUninstallModule(moduleName);
      }
    });
  };

  /**
   * Perform Uninstall Module
   */
  function performUninstallModule(moduleName) {
    const form = $('<form>', {
      method: 'POST',
      action: pageData.urls.uninstall
    });

    form.append($('<input>', {
      type: 'hidden',
      name: '_token',
      value: $('meta[name="csrf-token"]').attr('content')
    }));

    form.append($('<input>', {
      type: 'hidden',
      name: '_method',
      value: 'DELETE'
    }));

    form.append($('<input>', {
      type: 'hidden',
      name: 'module',
      value: moduleName
    }));

    $('body').append(form);
    form.submit();
  }

  /**
   * Configure Module
   */
  window.configureModule = function (moduleName) {
    window.location.href = `/settings/${moduleName.toLowerCase()}`;
  };

  /**
   * Show Success Alert
   */
  function showSuccessAlert(message) {
    Swal.fire({
      title: pageData.labels.success,
      text: message,
      icon: 'success',
      customClass: {
        confirmButton: 'btn btn-success'
      },
      buttonsStyling: false
    });
  }

  /**
   * Show Error Alert
   */
  function showErrorAlert(message) {
    Swal.fire({
      title: pageData.labels.error,
      text: message,
      icon: 'error',
      customClass: {
        confirmButton: 'btn btn-danger'
      },
      buttonsStyling: false
    });
  }
});
