/**
 * Example Octane Room Loader (ES Module)
 * This file demonstrates how to load and initialize a WASM room
 * 
 * The OctaneConfig object is injected by WordPress via wp_localize_script:
 * {
 *   identity: "user@example.com",
 *   roomName: "example-room",
 *   roomToken: "sha256_hash",
 *   wasmUrl: "https://site.com/wp-content/plugins/octane-core/rooms/example-room.wasm",
 *   apiEndpoint: "https://site.com/wp-json/octane/v1/",
 *   graphqlEndpoint: "https://site.com/graphql",
 *   timestamp: 1234567890
 * }
 */

// Wait for DOM and config to be ready
if (typeof window.OctaneConfig === 'undefined') {
    console.error('OctaneConfig not found - ensure the shortcode is used correctly');
}

const config = window.OctaneConfig || {};

/**
 * Initialize the WASM room
 */
async function initOctaneRoom() {
    try {
        console.log(`[Octane] Initializing room: ${config.roomName}`);
        console.log(`[Octane] User identity: ${config.identity}`);
        
        // Find the container
        const container = document.getElementById(`octane-room-${config.roomName}`) ||
                         document.querySelector(`[data-room="${config.roomName}"]`);
        
        if (!container) {
            throw new Error(`Container not found for room: ${config.roomName}`);
        }
        
        // Show loading state
        container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;"><p>Loading WASM module...</p></div>';
        
        // Load WASM using streaming API for best performance
        const importObject = {
            env: {
                // Provide any imports your WASM module needs
                consoleLog: (ptr, len) => {
                    // Example: read string from WASM memory
                    console.log('[WASM]', 'Log from WASM');
                },
                getUserIdentity: () => {
                    console.log('[WASM] Identity requested:', config.identity);
                    return 0; // Return pointer to identity string
                }
            }
        };
        
        // Use instantiateStreaming for optimal performance
        const wasmModule = await WebAssembly.instantiateStreaming(
            fetch(config.wasmUrl),
            importObject
        );
        
        console.log('[Octane] WASM module loaded successfully');
        
        // Initialize the module if it exports an init function
        if (wasmModule.instance.exports.init) {
            wasmModule.instance.exports.init();
        }
        
        // Render the room
        renderRoom(container, wasmModule.instance.exports);
        
    } catch (error) {
        console.error('[Octane] Failed to initialize room:', error);
        showError(error);
    }
}

/**
 * Render the room in the container
 */
function renderRoom(container, wasmExports) {
    // Clear loading state
    container.innerHTML = '';
    
    // Create canvas or DOM elements as needed
    const canvas = document.createElement('canvas');
    canvas.width = container.offsetWidth;
    canvas.height = container.offsetHeight;
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    container.appendChild(canvas);
    
    // Example: call WASM render function
    if (wasmExports.render) {
        // Set up animation loop
        const ctx = canvas.getContext('2d');
        
        function animate() {
            if (wasmExports.update) {
                wasmExports.update();
            }
            requestAnimationFrame(animate);
        }
        
        animate();
    }
    
    console.log('[Octane] Room rendered successfully');
}

/**
 * Show error message in the container
 */
function showError(error) {
    const container = document.querySelector(`[data-room="${config.roomName}"]`);
    if (container) {
        container.innerHTML = `
            <div style="padding:2rem;text-align:center;color:#c00;">
                <h3>❌ Failed to Load Room</h3>
                <p>${error.message}</p>
                <p style="font-size:0.9em;color:#999;">Check the console for more details.</p>
            </div>
        `;
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOctaneRoom);
} else {
    initOctaneRoom();
}

// Export for external access
export { initOctaneRoom, config };
