'use strict';

$(function () {
    // Check if pageData is defined
    if (typeof pageData === 'undefined') {
        console.error('pageData is not defined. Make sure the view includes the pageData script block.');
        return;
    }

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // Track which tabs have been loaded
    const loadedTabs = {
        overview: false,
        attendance: false,
        leave: false,
        timeline: false
    };

    // Load default tab based on employee status
    if (pageData.defaultTab === 'timeline') {
        // Timeline will be loaded by employee-timeline.js
        loadedTabs.timeline = true;
    } else {
        // Overview tab is server-rendered, mark as loaded
        loadedTabs.overview = true;
    }

    // Profile picture change handler
    const profilePictureInput = document.getElementById('file');
    const changeProfilePictureButton = document.getElementById('changeProfilePictureButton');
    const profilePictureForm = document.getElementById('profilePictureForm');

    if (changeProfilePictureButton && !pageData.isExited) {
        changeProfilePictureButton.addEventListener('click', function () {
            profilePictureInput.click();
        });
    }

    if (profilePictureInput && !pageData.isExited) {
        profilePictureInput.addEventListener('change', function () {
            if (profilePictureInput.files.length > 0) {
                // Show loading indicator
                Swal.fire({
                    title: pageData.labels.loading,
                    text: 'Uploading profile picture...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $(profilePictureForm).submit();
            }
        });
    }

    // Tab lazy loading handler
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetTab = $(e.target).attr('data-bs-target').replace('#tab-', '');

        // Load tab content if not already loaded
        if (!loadedTabs[targetTab]) {
            switch (targetTab) {
                case 'overview':
                    // Overview is server-rendered, no AJAX needed
                    loadedTabs.overview = true;
                    break;
                case 'attendance':
                    loadAttendanceTab();
                    break;
                case 'leave':
                    loadLeaveTab();
                    break;
                case 'timeline':
                    // Timeline is loaded by employee-timeline.js
                    loadedTabs.timeline = true;
                    break;
            }
        }
    });

    /**
     * Load Overview Tab
     */
    function loadOverviewTab() {
        if (loadedTabs.overview) return;

        $.ajax({
            url: pageData.urls.overview,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    const data = response.data;

                    // Update quick stats
                    $('#totalLeaveCount').text(data.stats.totalLeave);
                    $('#attendancePercentage').html(data.stats.attendancePercentage + '<small>%</small>');
                    $('#pendingApprovalsCount').text(data.stats.pendingApprovals);
                    $('#activeWarningsCount').text(data.stats.activeWarnings);

                    // Update employment status
                    $('#employmentStatusContainer').html(data.employmentStatus);

                    // Update recent activity
                    $('#recentActivityContainer').html(data.recentActivity);

                    loadedTabs.overview = true;
                }
            },
            error: function (xhr) {
                console.error('Failed to load overview:', xhr);
                // Show placeholder error state
                $('#quickStatsContainer').html(`
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="bx bx-error me-2"></i>${pageData.labels.errorLoading}
                        </div>
                    </div>
                `);
            }
        });
    }

    /**
     * Load Attendance Tab
     */
    function loadAttendanceTab() {
        if (loadedTabs.attendance) return;

        $.ajax({
            url: pageData.urls.attendance,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    $('#attendanceTabContent').html(response.html);
                    loadedTabs.attendance = true;
                }
            },
            error: function (xhr) {
                $('#attendanceTabContent').html(`
                    <div class="alert alert-danger">
                        <i class="bx bx-error me-2"></i>${pageData.labels.errorLoading}
                    </div>
                `);
            }
        });
    }

    /**
     * Load Leave Tab
     */
    function loadLeaveTab() {
        if (loadedTabs.leave) return;

        $.ajax({
            url: pageData.urls.leave,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    $('#leaveTabContent').html(response.html);
                    loadedTabs.leave = true;
                }
            },
            error: function (xhr) {
                $('#leaveTabContent').html(`
                    <div class="alert alert-danger">
                        <i class="bx bx-error me-2"></i>${pageData.labels.errorLoading}
                    </div>
                `);
            }
        });
    }

    /**
     * Load Work Info Form (Legacy Support)
     */
    window.loadSelectList = function () {
        var roleSelector = $('#role'),
            teamSelector = $('#teamId'),
            shiftSelector = $('#shiftId'),
            reportingToSelector = $('#reportingToId'),
            designationSelector = $('#designationId');

        // Load Roles
        getRoles().then(function (roles) {
            roleSelector.empty();
            roleSelector.append('<option value="">Select Role</option>');
            roles.forEach(function (roleItem) {
                roleSelector.append(
                    `<option value="${roleItem.name}" ${role === roleItem.name ? 'selected' : ''}>${roleItem.name}</option>`
                );
            });
        });

        getTeams().then(function (teams) {
            teamSelector.empty();
            teamSelector.append('<option value="">Select Team</option>');
            teams.forEach(function (team) {
                teamSelector.append(
                    `<option value="${team.id}" ${team.id === user.team_id ? 'selected' : ''}>${team.code}-${team.name}</option>`
                );
            });
        });

        getShifts().then(function (shifts) {
            shiftSelector.empty();
            shiftSelector.append('<option value="">Select Shift</option>');
            shifts.forEach(function (shift) {
                shiftSelector.append(
                    `<option value="${shift.id}" ${shift.id === user.shift_id ? 'selected' : ''}>${shift.code}-${shift.name}</option>`
                );
            });
        });

        getReportingToUsers().then(function (employeeTypes) {
            reportingToSelector.empty();
            reportingToSelector.append('<option value="">Select Reporting To</option>');

            // Remove the current user from the list
            employeeTypes = employeeTypes.filter(function (employee) {
                return employee.id !== user.id;
            });

            employeeTypes.forEach(function (employeeType) {
                reportingToSelector.append(
                    `<option value="${employeeType.id}" ${employeeType.id === user.reporting_to_id ? 'selected' : ''}>${employeeType.first_name} ${employeeType.last_name}</option>`
                );
            });
        });

        getDesignations().then(function (designations) {
            designationSelector.empty();
            designationSelector.append('<option value="">Select Designation</option>');
            designations.forEach(function (designation) {
                designationSelector.append(
                    `<option value="${designation.id}" ${designation.id === user.designation_id ? 'selected' : ''}>${designation.name}</option>`
                );
            });
        });

        // Initialize Select2
        roleSelector.select2({ placeholder: 'Select Role', dropdownParent: $('#workInfoForm') });
        teamSelector.select2({ placeholder: 'Select Team', dropdownParent: $('#workInfoForm') });
        shiftSelector.select2({ placeholder: 'Select Shift', dropdownParent: $('#workInfoForm') });
        reportingToSelector.select2({ placeholder: 'Select Reporting To', dropdownParent: $('#workInfoForm') });
        designationSelector.select2({ placeholder: 'Select Designation', dropdownParent: $('#workInfoForm') });

        // Attendance type change handler
        $('#attendanceType').off('change').on('change', function () {
            const value = this.value;
            $('.attendance-type-field').hide().find('select').val('');

            switch (value) {
                case 'geofence':
                    $('#geofenceGroupDiv').show();
                    loadGeofenceGroups();
                    break;
                case 'ipAddress':
                    $('#ipGroupDiv').show();
                    loadIpGroups();
                    break;
                case 'staticqr':
                    $('#qrGroupDiv').show();
                    loadQrGroups();
                    break;
                case 'site':
                    $('#siteDiv').show();
                    loadSites();
                    break;
                case 'dynamicqr':
                    $('#dynamicQrDiv').show();
                    loadDynamicQrDevices();
                    break;
            }
        });

        // Initialize attendance type fields
        initializeAttendanceType();
    };

    /**
     * Load Basic Info Form (Legacy Support)
     */
    window.loadEditBasicInfo = function () {
        // Initialize Select2 for dropdowns
        $('#gender').select2({
            dropdownParent: $('#offcanvasEditBasicInfo')
        });

        $('#bloodGroup').select2({
            dropdownParent: $('#offcanvasEditBasicInfo')
        });

        $('#emergencyContactRelationship').select2({
            dropdownParent: $('#offcanvasEditBasicInfo')
        });
    };

    /**
     * Load Geofence Groups
     */
    window.loadGeofenceGroups = function (selectedId = null) {
        if (!pageData.urls.getGeofenceGroups) return;

        $.ajax({
            url: pageData.urls.getGeofenceGroups,
            type: 'GET',
            success: function (response) {
                let options = '<option value="">Select Geofence Group</option>';
                response.forEach(function (item) {
                    const selected = selectedId && item.id == selectedId ? 'selected' : '';
                    options += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                });
                $('#geofenceGroupId').html(options);
            }
        });
    };

    /**
     * Load IP Groups
     */
    window.loadIpGroups = function (selectedId = null) {
        if (!pageData.urls.getIpGroups) return;

        $.ajax({
            url: pageData.urls.getIpGroups,
            type: 'GET',
            success: function (response) {
                let options = '<option value="">Select IP Group</option>';
                response.forEach(function (item) {
                    const selected = selectedId && item.id == selectedId ? 'selected' : '';
                    options += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                });
                $('#ipGroupId').html(options);
            }
        });
    };

    /**
     * Load QR Groups
     */
    window.loadQrGroups = function (selectedId = null) {
        if (!pageData.urls.getQrGroups) return;

        $.ajax({
            url: pageData.urls.getQrGroups,
            type: 'GET',
            success: function (response) {
                let options = '<option value="">Select QR Group</option>';
                response.forEach(function (item) {
                    const selected = selectedId && item.id == selectedId ? 'selected' : '';
                    options += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                });
                $('#qrGroupId').html(options);
            }
        });
    };

    /**
     * Load Sites
     */
    window.loadSites = function (selectedId = null) {
        if (!pageData.urls.getSites) return;

        $.ajax({
            url: pageData.urls.getSites,
            type: 'GET',
            success: function (response) {
                let options = '<option value="">Select Site</option>';
                response.forEach(function (item) {
                    const selected = selectedId && item.id == selectedId ? 'selected' : '';
                    options += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                });
                $('#siteId').html(options);
            }
        });
    };

    /**
     * Load Dynamic QR Devices
     */
    window.loadDynamicQrDevices = function (selectedId = null) {
        if (!pageData.urls.getDynamicQrDevices) return;

        $.ajax({
            url: pageData.urls.getDynamicQrDevices,
            type: 'GET',
            success: function (response) {
                let options = '<option value="">Select Dynamic QR Device</option>';
                response.forEach(function (item) {
                    const selected = selectedId && item.id == selectedId ? 'selected' : '';
                    options += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                });
                $('#dynamicQrId').html(options);
            }
        });
    };

    /**
     * Initialize Attendance Type Fields
     * Shows the correct attendance group field and pre-selects the current value
     */
    window.initializeAttendanceType = function () {
        if (typeof user === 'undefined') return;

        const attendanceType = user.attendance_type;

        // Hide all attendance type fields first
        $('.attendance-type-field').hide();

        // Show and populate based on current type
        switch (attendanceType) {
            case 'geofence':
                $('#geofenceGroupDiv').show();
                loadGeofenceGroups(user.geofence_group_id);
                break;
            case 'ip_address':
                $('#ipGroupDiv').show();
                loadIpGroups(user.ip_address_group_id);
                break;
            case 'qr_code':
                $('#qrGroupDiv').show();
                loadQrGroups(user.qr_group_id);
                break;
            case 'site':
                $('#siteDiv').show();
                loadSites(user.site_id);
                break;
            case 'dynamic_qr':
                $('#dynamicQrDiv').show();
                loadDynamicQrDevices(user.dynamic_qr_device_id);
                break;
        }
    };
});

/**
 * Print profile function
 */
window.printProfile = function () {
    window.print();
};

/**
 * Reactivate employee
 */
window.reactivateEmployee = function () {
    Swal.fire({
        title: 'Reactivate Employee?',
        text: 'This will restore the employee to active status.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reactivate',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.reactivate,
                method: 'POST',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: response.message || 'Employee reactivated successfully',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.message || 'Failed to reactivate employee'
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: 'An error occurred. Please try again.'
                    });
                }
            });
        }
    });
};

/**
 * Mark employee as relieved
 */
window.markAsRelieved = function () {
    Swal.fire({
        title: 'Mark as Relieved?',
        text: 'This will mark the terminated employee as officially relieved.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Mark as Relieved',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.markRelieved,
                method: 'POST',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: response.message || 'Employee marked as relieved successfully',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.message || 'Failed to mark employee as relieved'
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: 'An error occurred. Please try again.'
                    });
                }
            });
        }
    });
};

/**
 * View onboarding checklist
 */
window.viewOnboardingChecklist = function () {
    // Navigate to onboarding checklist
    window.location.href = `/employees/${pageData.userId}/onboarding-checklist`;
};

/**
 * Mark employee as inactive
 */
window.markAsInactive = function () {
    Swal.fire({
        title: pageData.labels.areYouSure || 'Are you sure?',
        text: 'This will mark the employee as inactive.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Mark as Inactive',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-warning',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.markInactive,
                method: 'POST',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: response.message || 'Employee marked as inactive successfully',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.message || 'Failed to mark employee as inactive',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: 'An error occurred. Please try again.',
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                }
            });
        }
    });
};

/**
 * Mark employee as retired
 */
window.retireEmployee = function () {
    Swal.fire({
        title: pageData.labels.areYouSure || 'Are you sure?',
        text: 'This will mark the employee as retired.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Mark as Retired',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: pageData.urls.retire,
                method: 'POST',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: pageData.labels.success,
                            text: response.message || 'Employee marked as retired successfully',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: pageData.labels.error,
                            text: response.message || 'Failed to mark employee as retired',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: pageData.labels.error,
                        text: 'An error occurred. Please try again.',
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        },
                        buttonsStyling: false
                    });
                }
            });
        }
    });
};
