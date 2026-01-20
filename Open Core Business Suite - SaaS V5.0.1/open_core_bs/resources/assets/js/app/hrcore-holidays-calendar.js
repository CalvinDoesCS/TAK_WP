/**
 * Holiday Calendar
 */

'use strict';

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function() {
  // Debug logging
  console.log('Holiday Calendar Script Loaded');
  console.log('Holiday Data:', window.holidayData);
  console.log('Holiday Data is Array?', Array.isArray(window.holidayData));
  console.log('Holiday Data Length:', window.holidayData ? window.holidayData.length : 0);

  // Check if we have holiday data
  if (window.holidayData && window.holidayData.length > 0) {
    const calendarEl = document.getElementById('holidayCalendar');

    if (calendarEl) {
      const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, listPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,listYear'
        },
        height: 'auto',
        events: window.holidayData,
        eventDisplay: 'block',
        displayEventTime: false,
        dayMaxEvents: 3,
        eventClick: function(info) {
          // Show holiday details in a modal or tooltip
          const event = info.event;
          const notes = event.extendedProps.notes || 'No additional information';

          Swal.fire({
            title: event.title,
            html: `
              <div class="text-start">
                <p><strong>Date:</strong> ${event.start.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
                <p><strong>Notes:</strong> ${notes}</p>
              </div>
            `,
            icon: 'info',
            confirmButtonText: 'Close',
            customClass: {
              confirmButton: 'btn btn-primary'
            },
            buttonsStyling: false
          });
        },
        dateClick: function(info) {
          // Check if clicked date has a holiday
          const clickedDate = info.dateStr;
          const holidaysOnDate = window.holidayData.filter(h => h.start === clickedDate);

          if (holidaysOnDate.length > 0) {
            let holidayList = holidaysOnDate.map(h => `<li>${h.title}</li>`).join('');

            Swal.fire({
              title: `Holidays on ${info.date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}`,
              html: `<ul class="text-start">${holidayList}</ul>`,
              icon: 'info',
              confirmButtonText: 'Close',
              customClass: {
                confirmButton: 'btn btn-primary'
              },
              buttonsStyling: false
            });
          }
        },
        eventDidMount: function(info) {
          // Add tooltip
          if (info.event.extendedProps.notes) {
            info.el.setAttribute('data-bs-toggle', 'tooltip');
            info.el.setAttribute('data-bs-placement', 'top');
            info.el.setAttribute('title', info.event.extendedProps.notes);
            new bootstrap.Tooltip(info.el);
          }
        }
      });

      calendar.render();

      // Update calendar height on window resize
      window.addEventListener('resize', function() {
        calendar.updateSize();
      });

      console.log('Calendar rendered successfully');
      console.log('Total events rendered:', calendar.getEvents().length);
    } else {
      console.error('Calendar element not found');
      document.getElementById('calendarFallback')?.style.setProperty('display', 'block');
    }
  } else {
    console.warn('Holiday data is not available or empty');
    console.log('Raw holidayData:', window.holidayData);
    // Show fallback message
    const fallback = document.getElementById('calendarFallback');
    if (fallback) {
      fallback.style.display = 'block';
      fallback.querySelector('.alert').innerHTML = '<i class="bx bx-info-circle me-2"></i>No holiday data available to display on the calendar.';
    }
  }
});
