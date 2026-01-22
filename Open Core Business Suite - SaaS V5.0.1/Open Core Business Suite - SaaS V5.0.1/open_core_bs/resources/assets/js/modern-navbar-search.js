/**
 * Modern Navbar Search
 * AJAX-based search for users, menu items, and other content
 */

'use strict';

(function () {
  if (typeof $ === 'undefined') {
    console.error('jQuery is required for search functionality');
    return;
  }

  $(function () {
    const searchInput = $('.modern-search-input');
    const contentBackdrop = $('.content-backdrop');

    if (!searchInput.length) {
      console.log('Modern search input not found');
      return;
    }

    // Prevent default search behavior from main.js
    searchInput.off('.typeahead');

    // Filter config for typeahead
    const filterConfig = function (data) {
      return function findMatches(q, cb) {
        let matches = [];

        data.filter(function (item) {
          const searchTerm = q.toLowerCase();
          const itemName = item.name.toLowerCase();

          if (itemName.startsWith(searchTerm)) {
            matches.push(item);
          } else if (itemName.includes(searchTerm)) {
            matches.push(item);
          }
        });

        // Sort results: exact matches first, then alphabetical
        matches.sort(function (a, b) {
          const aName = a.name.toLowerCase();
          const bName = b.name.toLowerCase();
          const searchTerm = q.toLowerCase();

          const aStarts = aName.startsWith(searchTerm);
          const bStarts = bName.startsWith(searchTerm);

          if (aStarts && !bStarts) return -1;
          if (!aStarts && bStarts) return 1;

          return aName.localeCompare(bName);
        });

        cb(matches);
      };
    };

    // Fetch search data from backend
    let searchData = null;

    const loadSearchData = function() {
      //console.log('Loading search data from:', baseUrl + 'getSearchDataAjax');

      $.ajax({
        url: baseUrl + 'getSearchDataAjax',
        dataType: 'json',
        async: true,
        success: function(data) {
          //console.log('Search data loaded successfully:', data);
          searchData = data;
          initializeSearch();
        },
        error: function(xhr, status, error) {
          console.error('Failed to load search data:', error, xhr.responseText);
        }
      });
    };

    const initializeSearch = function() {
      if (!searchData) {
        console.error('No search data available');
        return;
      }

      //console.log('Initializing typeahead search...');

      // Destroy existing typeahead if any
      if (searchInput.data('ttTypeahead')) {
        searchInput.typeahead('destroy');
      }

      // Initialize typeahead
      searchInput.typeahead(
        {
          hint: false,
          minLength: 1,
          classNames: {
            menu: 'tt-menu navbar-search-suggestion modern-search-results',
            cursor: 'active',
            suggestion: 'suggestion d-flex justify-content-between px-3 py-2 w-100',
            dataset: 'tt-dataset'
          }
        },
        // Pages/Menu Items
        {
          name: 'pages',
          display: 'name',
          limit: 6,
          source: filterConfig(searchData.pages || []),
          templates: {
            header: '<h6 class="suggestions-header text-primary mb-0 mx-3 mt-3 pb-2"><i class="bx bx-file me-2"></i>Pages</h6>',
            suggestion: function ({ url, icon, name }) {
              return (
                '<a href="' + baseUrl + url + '" class="search-result-item">' +
                '<div class="d-flex align-items-center">' +
                '<div class="search-icon-wrapper me-3">' +
                '<i class="bx ' + icon + ' bx-sm text-primary"></i>' +
                '</div>' +
                '<div class="search-item-content">' +
                '<span class="search-item-title">' + name + '</span>' +
                '</div>' +
                '</div>' +
                '</a>'
              );
            },
            notFound: '<div class="not-found px-3 py-2"><p class="py-2 mb-0 text-muted"><i class="bx bx-info-circle me-2"></i>No pages found</p></div>'
          }
        },
        // Users/Members
        {
          name: 'members',
          display: 'name',
          limit: 5,
          source: filterConfig(searchData.members || []),
          templates: {
            header: '<h6 class="suggestions-header text-primary mb-0 mx-3 mt-3 pb-2"><i class="bx bx-group me-2"></i>Team Members</h6>',
            suggestion: function ({ name, initial, src, subtitle, url }) {
              let avatarHtml;

              if (src) {
                avatarHtml = '<img class="rounded-circle" src="' + src + '" alt="' + name + '" width="36" height="36">';
              } else {
                avatarHtml = '<div class="avatar avatar-sm"><span class="avatar-initial rounded-circle bg-label-primary">' + initial + '</span></div>';
              }

              return (
                '<a href="' + baseUrl + url + '" class="search-result-item">' +
                '<div class="d-flex align-items-center w-100">' +
                '<div class="search-avatar-wrapper me-3">' + avatarHtml + '</div>' +
                '<div class="search-item-content flex-grow-1">' +
                '<span class="search-item-title d-block">' + name + '</span>' +
                '<small class="text-muted">' + subtitle + '</small>' +
                '</div>' +
                '</div>' +
                '</a>'
              );
            },
            notFound: '<div class="not-found px-3 py-2"><p class="py-2 mb-0 text-muted"><i class="bx bx-info-circle me-2"></i>No members found</p></div>'
          }
        }
      )
      // On render
      .bind('typeahead:render', function () {
        console.log('Search results rendered');
        contentBackdrop.addClass('show').removeClass('fade');
      })
      // On select
      .bind('typeahead:select', function (ev, suggestion) {
        if (suggestion.url && suggestion.url !== 'javascript:;') {
          window.location = baseUrl + suggestion.url;
        }
      })
      // On close
      .bind('typeahead:close', function () {
        contentBackdrop.addClass('fade').removeClass('show');
      });

      // Handle backdrop visibility on input
      searchInput.on('keyup', function () {
        if (searchInput.val() === '') {
          contentBackdrop.addClass('fade').removeClass('show');
        }
      });

      // Handle Escape key
      searchInput.on('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
          searchInput.blur();
          searchInput.typeahead('close');
        }
      });

      //console.log('✅ Modern search initialized successfully');
    };

    // Keyboard shortcut: Ctrl+K or Cmd+K
    $(document).on('keydown', function (event) {
      const isCtrlOrCmd = event.ctrlKey || event.metaKey;
      const isKKey = event.key === 'k' || event.key === 'K' || event.keyCode === 75;

      if (isCtrlOrCmd && isKKey) {
        event.preventDefault();
        searchInput.focus();
      }
    });

    // Focus handling
    searchInput.on('focus', function () {
      if (searchInput.val()) {
        searchInput.trigger('input');
      }
    });

    // Initialize PerfectScrollbar for search results
    setTimeout(function () {
      const searchResultsContainer = $('.navbar-search-suggestion');
      if (searchResultsContainer.length && typeof PerfectScrollbar !== 'undefined') {
        const psSearch = new PerfectScrollbar(searchResultsContainer[0], {
          wheelPropagation: false,
          suppressScrollX: true
        });

        searchInput.on('keyup', function () {
          if (psSearch && psSearch.update) {
            psSearch.update();
          }
        });
      }
    }, 100);

    // Load search data on page load
    loadSearchData();
  });
})();
