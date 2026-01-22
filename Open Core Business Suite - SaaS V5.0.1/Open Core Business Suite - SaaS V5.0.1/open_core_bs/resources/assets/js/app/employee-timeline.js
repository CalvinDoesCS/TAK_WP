$(function () {
    'use strict';

    const timelineContainer = $('#timelineContainer');
    const userId = pageData.userId;
    let timelineData = [];
    let currentFilter = 'all';

    // Load timeline data
    function loadTimeline() {
        $.ajax({
            url: pageData.urls.timeline,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    timelineData = response.timeline;
                    renderTimeline(timelineData);
                }
            },
            error: function (xhr) {
                timelineContainer.html(`
                    <div class="alert alert-danger">
                        <i class="bx bx-error me-2"></i>
                        ${pageData.labels.errorLoadingTimeline}
                    </div>
                `);
            }
        });
    }

    // Render timeline
    function renderTimeline(events) {
        if (!events || events.length === 0) {
            timelineContainer.html(`
                <div class="text-center text-muted py-5">
                    <i class="bx bx-time bx-lg"></i>
                    <p class="mt-2">${pageData.labels.noTimelineEvents}</p>
                </div>
            `);
            return;
        }

        let html = '<ul class="timeline">';

        events.forEach((event, index) => {
            const iconClass = event.icon || 'bx-time';
            const colorClass = event.color || 'primary';
            const isFirst = index === 0;

            html += `
                <li class="timeline-item timeline-item-transparent ${isFirst ? 'pb-4' : 'py-4'}" data-type="${event.type}">
                    <span class="timeline-point timeline-point-${colorClass}">
                        <i class="bx ${iconClass}"></i>
                    </span>
                    <div class="timeline-event">
                        <div class="timeline-header mb-1">
                            <h6 class="mb-0">${event.title}</h6>
                            <small class="text-muted">${event.date}</small>
                        </div>
                        <p class="mb-2">${event.description}</p>
                        ${event.metadata ? renderMetadata(event.metadata) : ''}
                    </div>
                </li>
            `;
        });

        html += '</ul>';
        timelineContainer.html(html);
    }

    // Render metadata as badges/details
    function renderMetadata(metadata) {
        let html = '<div class="d-flex flex-wrap gap-2 mt-2">';

        for (const [key, value] of Object.entries(metadata)) {
            if (value) {
                html += `<span class="badge bg-label-secondary">${key}: ${value}</span>`;
            }
        }

        html += '</div>';
        return html;
    }

    // Filter timeline events
    function filterTimeline(filterType) {
        currentFilter = filterType;

        // Update button states
        $('[id^="filter"]').removeClass('active');
        $(`#filter${filterType.charAt(0).toUpperCase() + filterType.slice(1)}Events`).addClass('active');

        let filteredEvents = timelineData;

        if (filterType !== 'all') {
            const filterMap = {
                'status': ['status_changed', 'terminated', 'relieved', 'retired', 'suspended'],
                'probation': ['probation_started', 'probation_confirmed', 'probation_extended', 'probation_failed'],
                'change': ['team_changed', 'designation_changed', 'salary_changed', 'reporting_manager_changed']
            };

            filteredEvents = timelineData.filter(event =>
                filterMap[filterType] && filterMap[filterType].includes(event.type)
            );
        }

        renderTimeline(filteredEvents);
    }

    // Event listeners
    $('#filterAllEvents').on('click', () => filterTimeline('all'));
    $('#filterStatusEvents').on('click', () => filterTimeline('status'));
    $('#filterProbationEvents').on('click', () => filterTimeline('probation'));
    $('#filterChangeEvents').on('click', () => filterTimeline('change'));

    // Load timeline when tab is shown
    $('button[data-bs-target="#tab-timeline"]').on('shown.bs.tab', function () {
        if (timelineData.length === 0) {
            loadTimeline();
        }
    });

    // Auto-load timeline if it's the active tab on page load (for exited employees)
    if ($('#tab-timeline').hasClass('active') || $('#tab-timeline').hasClass('show')) {
        loadTimeline();
    }
});
