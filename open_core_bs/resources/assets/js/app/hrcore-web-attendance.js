/**
 * HRCore Web Attendance
 */

'use strict';

$(function () {
    let currentStatus = {
        hasCheckedIn: false,
        hasCheckedOut: false,
        checkInTime: null,
        checkOutTime: null,
        todayLogs: [],
        isMultipleCheckInEnabled: false,
        canCheckIn: true,
        lastLogType: null,
        isBreakSystemEnabled: false,
        isOnBreak: false
    };

    // Initialize
    init();

    /**
     * Initialize the web attendance page
     */
    function init() {
        // Setup AJAX defaults
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Start clock
        updateClock();
        setInterval(updateClock, 1000);

        // Load today's status
        loadTodayStatus();

        // Bind events
        bindEvents();
    }

    /**
     * Update the digital clock
     */
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        $('#currentTime').text(timeString);

        // Update date if it changes
        const dateString = now.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        $('#currentDate').text(dateString);

        // Update working hours if checked in
        if (currentStatus.hasCheckedIn && currentStatus.checkInTime) {
            updateWorkingHours();
        }
    }

    /**
     * Update working hours display
     */
    function updateWorkingHours() {
        console.log('UpdateWorkingHours called with checkInTime:', currentStatus.checkInTime);
        
        if (!currentStatus.checkInTime) {
            console.log('No check-in time, cannot calculate working hours');
            return;
        }
        
        const checkInTime = new Date(currentStatus.checkInTime);
        const now = new Date();
        const diff = now - checkInTime;

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        const workingHoursText = `${hours}h ${minutes}m`;
        console.log('Setting working hours to:', workingHoursText);
        $('#workingHours').text(workingHoursText);
        console.log('Working hours element text:', $('#workingHours').text());
    }

    /**
     * Load today's attendance status
     */
    function loadTodayStatus() {
        console.log('Loading today status from:', pageData.urls.getTodayStatus);

        // Show loading overlay
        $('#statusLoadingOverlay').addClass('active');

        $.ajax({
            url: pageData.urls.getTodayStatus,
            method: 'GET',
            success: function(response) {
                console.log('Today status response:', response);
                if (response.status === 'success') {
                    const data = response.data;
                    console.log('Today status data:', data);

                    currentStatus.hasCheckedIn = data.hasCheckedIn;
                    currentStatus.hasCheckedOut = data.hasCheckedOut;
                    currentStatus.checkInTime = data.checkInTime;
                    currentStatus.checkOutTime = data.checkOutTime;
                    currentStatus.todayLogs = data.logs || [];
                    currentStatus.isMultipleCheckInEnabled = data.isMultipleCheckInEnabled || false;
                    currentStatus.canCheckIn = data.canCheckIn !== false;
                    currentStatus.lastLogType = data.lastLogType;
                    currentStatus.isBreakSystemEnabled = data.isBreakSystemEnabled || false;
                    currentStatus.isOnBreak = data.isOnBreak || false;

                    console.log('Updated currentStatus:', currentStatus);

                    updateUI();
                    displayTodayLogs();
                }
            },
            error: function(xhr) {
                console.error('Failed to load today status:', xhr);
                console.error('Response text:', xhr.responseText);
                // Continue with default state
                updateUI();
            },
            complete: function() {
                // Hide loading overlay
                $('#statusLoadingOverlay').removeClass('active');
            }
        });
    }

    /**
     * Update UI based on current status
     */
    function updateUI() {
        console.log('UpdateUI called with currentStatus:', currentStatus);

        const $checkBtn = $('#checkInOutBtn');
        const $checkBtnText = $('#checkBtnText');
        const $checkBtnIcon = $checkBtn.find('i');
        const $statusMessage = $('#statusMessage');
        const $breakBtn = $('#breakBtn');
        const $breakBtnText = $('#breakBtnText');
        const $breakBtnIcon = $breakBtn.find('i');

        // Reset button state
        $checkBtn.removeClass('btn-primary btn-warning btn-secondary').prop('disabled', false);
        $breakBtn.prop('disabled', false);

        // Handle break button visibility and state
        // In multi-check-in mode, show break button only when last log is check_in
        // In single check-in mode, show break button when checked in but not checked out
        let shouldShowBreakButton = false;
        if (currentStatus.isBreakSystemEnabled && currentStatus.hasCheckedIn) {
            if (currentStatus.isMultipleCheckInEnabled) {
                // Multi-check-in mode: show only when last log is check_in
                shouldShowBreakButton = currentStatus.lastLogType === 'check_in';
            } else {
                // Single check-in mode: show when not checked out
                shouldShowBreakButton = !currentStatus.hasCheckedOut;
            }
        }

        if (shouldShowBreakButton) {
            // Show break button when checked in and break system is enabled
            $breakBtn.removeClass('d-none');

            if (currentStatus.isOnBreak) {
                // User is on break - show Stop Break button
                $breakBtn.removeClass('btn-info').addClass('btn-warning');
                $breakBtn.html('<i class="bx bx-stop-circle me-2"></i><span id="breakBtnText">' + pageData.labels.stopBreak + '</span>');
            } else {
                // User is not on break - show Start Break button
                $breakBtn.removeClass('btn-warning').addClass('btn-info');
                $breakBtn.html('<i class="bx bx-coffee me-2"></i><span id="breakBtnText">' + pageData.labels.startBreak + '</span>');
            }
        } else {
            // Hide break button
            $breakBtn.addClass('d-none');
        }

        if (currentStatus.isMultipleCheckInEnabled) {
            // Multiple check-in/out is enabled
            if (currentStatus.todayLogs.length === 0) {
                // First check-in
                $checkBtn.addClass('btn-primary');
                $checkBtn.html('<i class="bx bx-log-in me-2"></i><span id="checkBtnText">' + pageData.labels.checkIn + '</span>');
                $statusMessage.text(pageData.labels.readyToCheckIn);
            } else if (currentStatus.lastLogType === 'check_in') {
                // Ready to check out
                $checkBtn.addClass('btn-warning');
                $checkBtn.html('<i class="bx bx-log-out me-2"></i><span id="checkBtnText">' + pageData.labels.checkOut + '</span>');
                $statusMessage.text(pageData.labels.readyToCheckOut);
            } else {
                // Ready to check in again
                $checkBtn.addClass('btn-primary');
                $checkBtn.html('<i class="bx bx-log-in me-2"></i><span id="checkBtnText">' + pageData.labels.checkIn + '</span>');
                $statusMessage.text(pageData.labels.readyToCheckIn + ' ' + pageData.labels.multipleCheckInAllowed);
            }
        } else {
            // Single check-in/out mode
            if (!currentStatus.hasCheckedIn) {
                // Ready to check in
                $checkBtn.addClass('btn-primary');
                $checkBtn.html('<i class="bx bx-log-in me-2"></i><span id="checkBtnText">' + pageData.labels.checkIn + '</span>');
                $statusMessage.text(pageData.labels.readyToCheckIn);
            } else if (!currentStatus.hasCheckedOut) {
                // Ready to check out
                $checkBtn.addClass('btn-warning');
                $checkBtn.html('<i class="bx bx-log-out me-2"></i><span id="checkBtnText">' + pageData.labels.checkOut + '</span>');
                $statusMessage.text(pageData.labels.readyToCheckOut);
            } else {
                // Already checked out - disable button
                $checkBtn.prop('disabled', true).addClass('btn-secondary');
                $checkBtn.html('<i class="bx bx-log-out me-2"></i><span id="checkBtnText">' + pageData.labels.checkOut + '</span>');
                $statusMessage.text(pageData.labels.alreadyCheckedOut);
            }
        }

        // Update status cards
        console.log('Updating check-in time display...');
        if (currentStatus.checkInTime) {
            const checkInDate = new Date(currentStatus.checkInTime);
            const checkInTimeText = checkInDate.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            console.log('Setting check-in time to:', checkInTimeText);
            $('#checkInTime').text(checkInTimeText);
            console.log('Check-in time element text:', $('#checkInTime').text());
        } else {
            console.log('No check-in time, setting to not checked in');
            $('#checkInTime').text(pageData.labels.notCheckedIn);
        }

        if (currentStatus.checkOutTime) {
            const checkOutDate = new Date(currentStatus.checkOutTime);
            const checkOutTimeText = checkOutDate.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            console.log('Setting check-out time to:', checkOutTimeText);
            $('#checkOutTime').text(checkOutTimeText);
        } else {
            console.log('No check-out time, setting to not checked out');
            $('#checkOutTime').text(pageData.labels.notCheckedOut);
        }
        
        console.log('UpdateUI completed');
    }

    /**
     * Display today's activity logs
     */
    function displayTodayLogs() {
        const $logsContainer = $('#todayLogs');

        if (currentStatus.todayLogs.length === 0) {
            return; // Keep the no activity message
        }

        let logsHtml = '';
        currentStatus.todayLogs.forEach(function(log) {
            const time = new Date(log.created_at).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            let typeClass, icon, label;

            switch(log.type) {
                case 'check_in':
                    typeClass = 'check-in';
                    icon = 'bx-log-in';
                    label = pageData.labels.checkIn;
                    break;
                case 'check_out':
                    typeClass = 'check-out';
                    icon = 'bx-log-out';
                    label = pageData.labels.checkOut;
                    break;
                case 'break_start':
                    typeClass = 'break-start';
                    icon = 'bx-coffee';
                    label = pageData.labels.breakStart;
                    break;
                case 'break_end':
                    typeClass = 'break-end';
                    icon = 'bx-stop-circle';
                    label = pageData.labels.breakEnd;
                    break;
                default:
                    typeClass = '';
                    icon = 'bx-info-circle';
                    label = log.type;
            }

            logsHtml += `
                <div class="attendance-log-item ${typeClass}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bx ${icon} me-2"></i>
                            <strong>${label}</strong>
                        </div>
                        <small class="text-muted">${time}</small>
                    </div>
                </div>
            `;
        });

        $logsContainer.html(logsHtml);
    }

    /**
     * Bind UI events
     */
    function bindEvents() {
        $('#checkInOutBtn').on('click', function() {
            const $btn = $(this);

            if ($btn.prop('disabled')) {
                return;
            }

            // Determine the action based on current status
            let isCheckIn = true;
            let confirmMessage = pageData.labels.confirmCheckIn;
            let buttonText = pageData.labels.checkIn;

            if (currentStatus.isMultipleCheckInEnabled) {
                // For multiple check-in mode, check the last log type
                if (currentStatus.lastLogType === 'check_in') {
                    isCheckIn = false;
                    confirmMessage = pageData.labels.confirmCheckOut;
                    buttonText = pageData.labels.checkOut;
                }
            } else {
                // For single check-in mode
                if (currentStatus.hasCheckedIn && !currentStatus.hasCheckedOut) {
                    isCheckIn = false;
                    confirmMessage = pageData.labels.confirmCheckOut;
                    buttonText = pageData.labels.checkOut;
                }
            }

            Swal.fire({
                title: confirmMessage,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: buttonText,
                cancelButtonText: pageData.labels.cancel || 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    performCheckInOut();
                }
            });
        });

        // Break button event
        $('#breakBtn').on('click', function() {
            const $btn = $(this);

            if ($btn.prop('disabled')) {
                return;
            }

            const isOnBreak = currentStatus.isOnBreak;
            const confirmMessage = isOnBreak ? pageData.labels.confirmStopBreak : pageData.labels.confirmStartBreak;
            const buttonText = isOnBreak ? pageData.labels.stopBreak : pageData.labels.startBreak;

            Swal.fire({
                title: confirmMessage,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: buttonText,
                cancelButtonText: pageData.labels.cancel || 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    performBreakStartStop();
                }
            });
        });
    }

    /**
     * Perform check in/out
     */
    function performCheckInOut() {
        const $btn = $('#checkInOutBtn');
        const originalHtml = $btn.html();
        
        // Show loading state
        $btn.prop('disabled', true);
        $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + pageData.labels.checking);

        const data = {
            date: new Date().toISOString().split('T')[0],
            time: new Date().toTimeString().split(' ')[0]
        };

        $.ajax({
            url: pageData.urls.checkInOut,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.status === 'success') {
                    const message = response.data.type === 'check_in' ? 
                        pageData.labels.checkedIn : 
                        pageData.labels.checkedOut;

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: message,
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        buttonsStyling: false
                    });

                    // Reload status from server to ensure consistency
                    loadTodayStatus();

                    // Trigger utilities panel refresh
                    if (window.refreshUtilitiesPanel) {
                        setTimeout(window.refreshUtilitiesPanel, 1000);
                    }

                    // Dispatch custom event
                    window.dispatchEvent(new CustomEvent('attendance-updated'));
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data || pageData.labels.error,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        buttonsStyling: false
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = pageData.labels.error;
                
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    customClass: {
                        confirmButton: 'btn btn-success'
                    },
                    buttonsStyling: false
                });

                // Re-enable button on error
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }
        });
    }

    /**
     * Perform break start/stop
     */
    function performBreakStartStop() {
        const $btn = $('#breakBtn');
        const $checkBtn = $('#checkInOutBtn');

        // Disable both buttons during operation
        $btn.prop('disabled', true);
        $checkBtn.prop('disabled', true);
        $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + pageData.labels.checking);

        $.ajax({
            url: pageData.urls.startStopBreak,
            method: 'POST',
            success: function(response) {
                if (response.status === 'success') {
                    const message = response.data.isOnBreak ?
                        pageData.labels.breakStarted :
                        pageData.labels.breakStopped;

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: message,
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        buttonsStyling: false
                    });

                    // Reload status from server to ensure consistency
                    loadTodayStatus();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data || pageData.labels.error,
                        customClass: {
                            confirmButton: 'btn btn-success'
                        },
                        buttonsStyling: false
                    });

                    // Re-enable buttons and restore state on error
                    updateUI();
                }
            },
            error: function(xhr) {
                let errorMessage = pageData.labels.error;

                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    customClass: {
                        confirmButton: 'btn btn-success'
                    },
                    buttonsStyling: false
                });

                // Re-enable buttons and restore state on error
                updateUI();
            }
        });
    }
});