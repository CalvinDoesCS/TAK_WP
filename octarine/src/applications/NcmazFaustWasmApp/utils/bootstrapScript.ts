import { FAUST_SECRET_KEY } from "../constants";
import type { AssetMap } from "../types";

export function generateBootstrapScript(assetMap: AssetMap): string {
  return `<script>(function(){
    console.log('🚀 Bootstrap script executing...');
    
    // Check all script tags and their current src values
    console.log('📜 All script tags in page:', Array.from(document.scripts).map(function(s) {
      return {
        id: s.id,
        src: s.src,
        type: s.type,
        hasContent: s.textContent && s.textContent.length > 0,
        loaded: !s.src || s.readyState === 'complete' || s.readyState === 'loaded',
        isBlob: s.src && s.src.startsWith('blob:'),
        isNext: s.src && s.src.includes('/_next/')
      };
    }));
    
    // Monitor script loading with detailed status
    var scriptsWithSrc = Array.from(document.scripts).filter(function(s) { return s.src; });
    console.log('📜 Total scripts with src:', scriptsWithSrc.length);
    
    var blobScripts = scriptsWithSrc.filter(function(s) { return s.src.startsWith('blob:'); });
    var nextScripts = scriptsWithSrc.filter(function(s) { return s.src.includes('/_next/'); });
    
    console.log('📜 Scripts with blob: URLs:', blobScripts.length);
    console.log('📜 Scripts with /_next/ in path:', nextScripts.length);
    
    if (nextScripts.length > 0) {
      console.error('❌ PROBLEM: Scripts still have /_next/ URLs instead of blob URLs!');
      nextScripts.forEach(function(s) {
        console.error('  ❌', s.src);
      });
    }
    
    // Track loaded scripts
    var loadedCount = 0;
    var totalScripts = scriptsWithSrc.length;
    
    scriptsWithSrc.forEach(function(script, idx) {
      console.log('Script ' + (idx + 1) + ': ' + script.src.substring(0, 60) + '...');
      
      // Verify blob content AND MIME type for blob URLs
      if (script.src.startsWith('blob:')) {
        fetch(script.src)
          .then(function(res) { 
            console.log('📦 Blob ' + (idx + 1) + ' MIME type:', res.headers.get('content-type'));
            return res.text(); 
          })
          .then(function(content) {
            console.log('📦 Blob ' + (idx + 1) + ' size: ' + content.length + ' bytes');
            console.log('📦 Blob ' + (idx + 1) + ' preview:', content.substring(0, 200));
            if (content.length === 0) {
              console.error('❌ Blob ' + (idx + 1) + ' is EMPTY!');
            } else if (content.length < 100) {
              console.warn('⚠️ Blob ' + (idx + 1) + ' is very small, full content:', content);
            } else if (!content.includes('function') && !content.includes('var ') && !content.includes('const ')) {
              console.error('❌ Blob ' + (idx + 1) + ' does not look like JavaScript!');
            }
          })
          .catch(function(err) {
            console.error('❌ Failed to fetch blob ' + (idx + 1) + ':', err);
          });
      }
      
      script.addEventListener('load', function() {
        loadedCount++;
        console.log('✅ Script ' + (idx + 1) + ' loaded successfully (' + loadedCount + '/' + totalScripts + ')');
      });
      script.addEventListener('error', function(e) {
        console.error('❌ Script ' + (idx + 1) + ' failed to load:', script.src.substring(0, 60), e);
      });
    });
    
    // Check if all scripts have loaded after 2 seconds
    setTimeout(function() {
      console.log('📊 Script loading status: ' + loadedCount + '/' + totalScripts + ' scripts loaded');
      if (loadedCount < totalScripts) {
        console.warn('⚠️ Only ' + loadedCount + ' of ' + totalScripts + ' initial scripts loaded');
        console.warn('⚠️ This is normal - remaining scripts may load dynamically');
      } else {
        console.log('✅ All initial scripts loaded successfully!');
      }
      
      // Check if Next.js has initialized
      if (window.next) {
        console.log('✅ Next.js router initialized:', window.next);
      } else {
        console.warn('⚠️ Next.js router not initialized yet');
      }
      
      // Check for any console errors
      console.log('🔍 Checking window.__NEXT_DATA__...');
      if (window.__NEXT_DATA__) {
        console.log('✅ __NEXT_DATA__ exists');
        console.log('📋 Page:', window.__NEXT_DATA__.page);
        console.log('📋 Build ID:', window.__NEXT_DATA__.buildId);
        console.log('📋 Props structure:', Object.keys(window.__NEXT_DATA__.props || {}));
      } else {
        console.error('❌ __NEXT_DATA__ is missing!');
      }
    }, 2000);
    
    // Check if __NEXT_DATA__ exists and log it
    setTimeout(function() {
      if (!window.__NEXT_DATA__) {
        console.error('❌ CRITICAL: __NEXT_DATA__ is undefined!');
        console.log('Checking for __NEXT_DATA__ script tag...');
        var nextDataScript = document.getElementById('__NEXT_DATA__');
        if (nextDataScript) {
          console.log('✅ __NEXT_DATA__ script tag exists');
          console.log('Script type:', nextDataScript.type);
          console.log('Script content length:', nextDataScript.textContent.length);
          console.log('Script content preview:', nextDataScript.textContent.substring(0, 500));
          try {
            var parsed = JSON.parse(nextDataScript.textContent);
            console.log('✅ JSON is valid, manually assigning to window.__NEXT_DATA__...');
            window.__NEXT_DATA__ = parsed;
            console.log('✅ window.__NEXT_DATA__ now set:', !!window.__NEXT_DATA__);
          } catch(e) {
            console.error('❌ Failed to parse __NEXT_DATA__ JSON:', e.message);
            console.log('Invalid JSON:', nextDataScript.textContent.substring(0, 1000));
          }
        } else {
          console.error('❌ __NEXT_DATA__ script tag not found in DOM!');
        }
      } else {
        console.log('✅ __NEXT_DATA__ already exists on window');
        console.log('🔍 Current __NEXT_DATA__ content:', {
          page: window.__NEXT_DATA__.page,
          query: window.__NEXT_DATA__.query,
          buildId: window.__NEXT_DATA__.buildId,
          hasProps: !!window.__NEXT_DATA__.props,
          hasSeedNode: !!window.__NEXT_DATA__.props?.__SEED_NODE__,
          hasPageData: !!window.__NEXT_DATA__.props?.pageProps?.data,
          fullData: window.__NEXT_DATA__
        });
        
        // Check if the script tag has better data
        var nextDataScript = document.getElementById('__NEXT_DATA__');
        if (nextDataScript && nextDataScript.textContent) {
          try {
            var scriptData = JSON.parse(nextDataScript.textContent);
            console.log('🔍 Script tag __NEXT_DATA__:', {
              page: scriptData.page,
              hasSeedNode: !!scriptData.props?.__SEED_NODE__,
              hasPageData: !!scriptData.props?.pageProps?.data
            });
            
            // Compare and override if different
            if (scriptData.props?.pageProps?.data && !window.__NEXT_DATA__.props?.pageProps?.data) {
              console.warn('⚠️ Script tag has data but window does not! Overriding...');
              window.__NEXT_DATA__ = scriptData;
              console.log('✅ Overridden window.__NEXT_DATA__ with script tag data');
            }
          } catch(e) {
            console.error('Failed to parse script tag JSON:', e);
          }
        }
      }
    }, 100);
    
    // Asset map for runtime chunk loading
    const MAP = ${JSON.stringify(assetMap)};
    const origAppend = Element.prototype.appendChild;
    Element.prototype.appendChild = function(node) {
      if (node.tagName === 'SCRIPT' && node.src) {
        // Get the pathname from src - if it's already a blob URL, skip
        if (node.src.startsWith('blob:')) {
          return origAppend.call(this, node);
        }
        
        // For relative paths like /_next/..., use directly as key
        // For full URLs, extract pathname
        var key = node.src;
        if (node.src.startsWith('http://') || node.src.startsWith('https://')) {
          try {
            var url = new URL(node.src);
            key = url.pathname;
          } catch(e) {
            console.warn('Failed to parse script URL:', node.src);
            return origAppend.call(this, node);
          }
        } else if (!node.src.startsWith('/')) {
          // Ensure leading slash for relative paths
          key = '/' + node.src;
        }
        
        if (MAP[key]) { 
          console.log('🔄 Runtime rewriting script:', key, '→ blob URL');
          node.src = MAP[key]; 
        } else if (key.includes('/_next/')) {
          console.warn('⚠️ Script not in asset map:', key);
        }
      }
      return origAppend.call(this, node);
    };
    
    const origFetch = window.fetch;
    window.fetch = function(input, init) {
      // Log all fetch attempts
      console.log('🌐 Fetch intercepted:', typeof input === 'string' ? input : input.url);
      
      if (typeof input === 'string') {
        // Skip if already a blob URL
        if (input.startsWith('blob:')) {
          return origFetch(input, init);
        }
        
        // Mock GraphQL requests with proper Apollo Client cache structure
        if (input.includes('/graphql') || input.includes('graphql')) {
          console.log('✅ Mocking GraphQL fetch with injected data');
          
          var requestBody = '';
          var parsedBody = null;
          try {
            requestBody = init?.body ? String(init.body) : '';
            console.log('📝 Request body:', requestBody);
            // Try to parse as JSON to extract variables
            parsedBody = JSON.parse(requestBody);
            console.log('📋 Parsed query variables:', parsedBody.variables);
          } catch (e) {
            console.warn('⚠️  Could not parse request body as JSON:', e);
          }
          
          // Get the pre-fetched data and front page node
          var mockData = window.__NEXT_DATA__?.props?.pageProps?.data || {};
          var frontPageNode = window.__NEXT_DATA__?.props?.__SEED_NODE__ || null;
          
          // Check if this is a nodeByUri query (needed by WordPressTemplate)
          var isNodeByUriQuery = requestBody.includes('nodeByUri');
          var mockResponse;
          
          if (isNodeByUriQuery && frontPageNode) {
            console.log('🎯 nodeByUri query detected - returning front page node');
            
            // Check if query is asking for a specific URI
            var requestedUri = '/';
            if (parsedBody?.variables?.uri) {
              requestedUri = parsedBody.variables.uri;
              console.log('🔍 Query requesting URI:', requestedUri);
            }
            
            // Blob URLs should be treated as root path (iframe location is blob URL)
            if (requestedUri.startsWith('blob:')) {
              console.log('🔄 Converting blob URL to root path');
              requestedUri = '/';
            }
            
            // Return null for non-root URIs (let Next.js 404 handle them properly)
            // Only return our front page node for root path
            if (requestedUri === '/' || requestedUri === '') {
              console.log('✅ Returning front page node for root path');
              mockResponse = {
                data: {
                  nodeByUri: frontPageNode,
                  ...mockData
                },
                errors: null
              };
            } else {
              console.log('⚠️  URI not found, returning null nodeByUri for:', requestedUri);
              mockResponse = {
                data: {
                  nodeByUri: null,
                  ...mockData
                },
                errors: null
              };
            }
          } else {
            console.log('📦 Regular query - returning base mock data');
            mockResponse = {
              data: mockData,
              errors: null
            };
          }
          
          console.log('✅ Mock response structure:', {
            hasNodeByUri: !!mockResponse.data.nodeByUri,
            hasGeneralSettings: !!mockResponse.data.generalSettings,
            hasPrimaryMenuItems: !!mockResponse.data.primaryMenuItems,
            hasPosts: !!mockResponse.data.posts
          });
          
          return Promise.resolve({
            ok: true,
            status: 200,
            statusText: 'OK',
            json: function() {
              console.log('📤 Returning mocked JSON response');
              return Promise.resolve(mockResponse);
            },
            text: function() {
              return Promise.resolve(JSON.stringify(mockResponse));
            },
            headers: new Headers({
              'content-type': 'application/json',
              'x-faustwp-secret': '${FAUST_SECRET_KEY}'
            })
          });
        }
        
        var key = input;
        // For full URLs, extract pathname
        if (input.startsWith('http://') || input.startsWith('https://')) {
          try {
            var url = new URL(input);
            key = url.pathname;
          } catch(e) {
            return origFetch(input, init);
          }
        } else if (!input.startsWith('/')) {
          // Ensure leading slash for relative paths
          key = '/' + input;
        }
        
        if (MAP[key]) {
          console.log('🔄 Runtime rewriting fetch:', key, '→ blob URL');
          return origFetch(MAP[key], init);
        }
      }
      return origFetch(input, init);
    };
    
    // Fix history API for blob: URLs in iframe
    if (window.location.protocol === 'blob:') {
      if (window.history) {
        try {
          const origPush = window.history.pushState;
          const origReplace = window.history.replaceState;
          window.history.pushState = function(state, title, url) {
            try {
              return origPush.call(window.history, state, title, url);
            } catch(e) {
              console.warn('pushState blocked:', e.message);
              return null;
            }
          };
          window.history.replaceState = function(state, title, url) {
            try {
              return origReplace.call(window.history, state, title, url);
            } catch(e) {
              console.warn('replaceState blocked:', e.message);
              return null;
            }
          };
        } catch(e) {
          console.error('Failed to patch history:', e);
        }
      }
    }
    
    // Comprehensive debugging for index.html
    console.log('🔍 DEBUG: __NEXT_DATA__ structure:', {
      page: window.__NEXT_DATA__?.page,
      query: window.__NEXT_DATA__?.query,
      buildId: window.__NEXT_DATA__?.buildId,
      props: window.__NEXT_DATA__?.props,
      hasSeedNode: !!window.__NEXT_DATA__?.props?.__SEED_NODE__,
      seedNodeType: window.__NEXT_DATA__?.props?.__SEED_NODE__?.__typename,
      pagePropsKeys: Object.keys(window.__NEXT_DATA__?.props?.pageProps || {}),
      hasData: !!window.__NEXT_DATA__?.props?.pageProps?.data
    });
    
    if (window.__NEXT_DATA__ && window.__NEXT_DATA__.props) {
      console.log('📦 Page data available:', {
        hasData: !!window.__NEXT_DATA__.props.pageProps.data,
        posts: window.__NEXT_DATA__.props.pageProps.data?.posts?.nodes?.length || 0,
        menus: window.__NEXT_DATA__.props.pageProps.data?.primaryMenuItems?.nodes?.length || 0,
        page: window.__NEXT_DATA__.page,
        hasSeedNode: !!window.__NEXT_DATA__.props.__SEED_NODE__,
        fullPageProps: window.__NEXT_DATA__.props.pageProps
      });
    }
    
    // Catch and log any JavaScript errors
    window.addEventListener('error', function(e) {
      console.error('🔴 Page error:', {
        message: e.message,
        filename: e.filename,
        lineno: e.lineno,
        colno: e.colno,
        error: e.error,
        stack: e.error?.stack
      });
    });
    window.addEventListener('unhandledrejection', function(e) {
      console.error('🔴 Unhandled promise rejection:', e.reason, e.reason?.stack);
    });
    
    // Monitor React mounting
    var mountCheckInterval = setInterval(function() {
      var rootDiv = document.getElementById('__next');
      if (rootDiv && rootDiv.innerHTML && rootDiv.innerHTML.trim() !== '' && !rootDiv.innerHTML.includes('nprogress')) {
        console.log('✅ React mounted successfully!');
        console.log('📄 Root content preview:', rootDiv.innerHTML.substring(0, 500));
        clearInterval(mountCheckInterval);
      } else if (rootDiv) {
        // Log what we see every second to track changes
        var currentContent = rootDiv.innerHTML.substring(0, 200);
        if (!window._lastContent || window._lastContent !== currentContent) {
          console.log('⏳ Root div updating...', currentContent);
          window._lastContent = currentContent;
        }
      }
    }, 1000);
    
    // Final check after 3 seconds
    setTimeout(function() {
      clearInterval(mountCheckInterval);
      var rootDiv = document.getElementById('__next');
      if (rootDiv) {
        var content = rootDiv.innerHTML;
        console.log('📄 Final root div content:', content.substring(0, 500));
        if (!content || content.trim() === '' || content.includes('nprogress')) {
          console.error('❌ React did not render - still showing nprogress or empty');
          console.log('🔍 Checking for React errors in console...');
          
          // Check for React
          if (window.React) {
            console.log('✅ React is loaded:', window.React.version);
          } else {
            console.error('❌ React is not loaded globally (this may be OK for modern React)');
          }
          
          // Check for Next.js router
          if (window.next && window.next.router) {
            console.log('✅ Next.js router exists');
            console.log('📋 Current route:', window.next.router.pathname);
          }
          
          // Most important: check __NEXT_DATA__
          console.log('🔍 Final __NEXT_DATA__ check:');
          if (window.__NEXT_DATA__) {
            console.log('✅ __NEXT_DATA__:', {
              page: window.__NEXT_DATA__.page,
              hasSeedNode: !!window.__NEXT_DATA__.props?.__SEED_NODE__,
              hasPageProps: !!window.__NEXT_DATA__.props?.pageProps,
              hasData: !!window.__NEXT_DATA__.props?.pageProps?.data,
              propsKeys: Object.keys(window.__NEXT_DATA__.props || {}),
              pagePropsKeys: Object.keys(window.__NEXT_DATA__.props?.pageProps || {})
            });
          }
          
          // Check if there were any errors
          console.log('💡 TIP: Look for red error messages above - React may have failed to mount due to an error');
        } else {
          console.log('✅ Root div has content - React may have mounted');
        }
      }
    }, 3000);
  })();</script>`;
}
