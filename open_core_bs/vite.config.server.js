import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import { glob } from 'glob';
import fs from 'fs';
import path from 'path';

/**
 * MEMORY-OPTIMIZED VITE CONFIG FOR SERVER BUILDS
 *
 * Use this config when building on servers with limited memory (< 8GB RAM)
 * Run with: node --max-old-space-size=8192 ./node_modules/vite/bin/vite.js build --config vite.config.server.js
 */

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(query) {
  return glob.sync(query);
}

function getModuleAssets() {
  const moduleAssets = [];

  try {
    // Read module statuses
    const moduleStatusesPath = path.join(process.cwd(), 'modules_statuses.json');
    const moduleStatuses = JSON.parse(fs.readFileSync(moduleStatusesPath, 'utf-8'));

    // Process each enabled module
    Object.entries(moduleStatuses).forEach(([moduleName, isEnabled]) => {
      if (isEnabled) {
        // Get JS files from module
        const moduleJsFiles = GetFilesArray(`Modules/${moduleName}/resources/assets/js/**/*.js`);
        moduleAssets.push(...moduleJsFiles);

        // Get SCSS/CSS files from module
        const moduleScssFiles = GetFilesArray(`Modules/${moduleName}/resources/assets/sass/**/*.scss`);
        const moduleCssFiles = GetFilesArray(`Modules/${moduleName}/resources/assets/css/**/*.css`);
        moduleAssets.push(...moduleScssFiles, ...moduleCssFiles);
      }
    });

    console.log(`Loaded assets from ${Object.values(moduleStatuses).filter(Boolean).length} enabled modules`);
  } catch (error) {
    console.warn('Could not load module assets:', error.message);
  }

  return moduleAssets;
}

/**
 * Js Files
 */
// Page JS Files
const pageJsFiles = GetFilesArray('resources/assets/js/*.js');

const pageAppJsFiles = GetFilesArray('resources/assets/js/app/*.js');

// Page specific JS files in pages directory
const pageDirJsFiles = GetFilesArray('resources/assets/js/pages/*.js');

// Employee report JS files
const employeeReportJsFiles = GetFilesArray('resources/assets/js/employees/**/*.js');

// Leave report JS files
const leaveReportJsFiles = GetFilesArray('resources/assets/js/leave/**/*.js');

// Processing Vendor JS Files
const vendorJsFiles = GetFilesArray('resources/assets/vendor/js/*.js');

// Processing Libs JS Files
const LibsJsFiles = GetFilesArray('resources/assets/vendor/libs/**/*.js');

/**
 * Scss Files
 */
// Processing Core, Themes & Pages Scss Files
const CoreScssFiles = GetFilesArray('resources/assets/vendor/scss/**/!(_)*.scss');

// Processing Libs Scss & Css Files
const LibsScssFiles = GetFilesArray('resources/assets/vendor/libs/**/!(_)*.scss');
const LibsCssFiles = GetFilesArray('resources/assets/vendor/libs/**/*.css');

const pageCssFiles = GetFilesArray('resources/assets/css/*.css');

// Processing Page Scss Files
const pageScssFiles = GetFilesArray('resources/assets/scss/**/*.scss');

// Processing Fonts Scss Files
const FontsScssFiles = GetFilesArray('resources/assets/vendor/fonts/!(_)*.scss');

// Get all module assets dynamically
const moduleAssets = getModuleAssets();


// Processing Window Assignment for Libs like jKanban, pdfMake
function libsWindowAssignment() {
  return {
    name: 'libsWindowAssignment',

    transform(src, id) {
      if (id.includes('jkanban.js')) {
        return src.replace('this.jKanban', 'window.jKanban');
      } else if (id.includes('vfs_fonts')) {
        return src.replaceAll('this.pdfMake', 'window.pdfMake');
      }
    }
  };
}

export default defineConfig(({ mode }) => {
  // Load env file based on `mode` in the current working directory.
  const env = loadEnv(mode, process.cwd(), '');

  // Extract host from APP_URL (e.g., http://192.168.0.5:8000 -> 192.168.0.5)
  let hmrHost = 'localhost';
  if (env.APP_URL) {
    try {
      const url = new URL(env.APP_URL);
      hmrHost = url.hostname;
    } catch (e) {
      console.warn('Could not parse APP_URL, using localhost for HMR');
    }
  }

  return {
    plugins: [
      laravel({
        input: [
          'resources/css/app.css',
          'resources/assets/css/demo.css',
          'resources/js/app.js',
          ...pageJsFiles,
          ...pageAppJsFiles,
          ...pageDirJsFiles,
          ...employeeReportJsFiles,
          ...leaveReportJsFiles,
          ...vendorJsFiles,
          ...LibsJsFiles,
          'resources/js/main-helper.js', // Processing Main Helper JS File
          'resources/js/main-datatable.js', // Processing Main Datatable JS File
          'resources/js/main-select2.js', // Processing Main Select2 JS File
          ...CoreScssFiles,
          ...LibsScssFiles,
          ...LibsCssFiles,
          ...FontsScssFiles,
          ...pageCssFiles,
          ...pageScssFiles,
          // Include all module assets dynamically
          ...moduleAssets
        ],
        refresh: true
      }),
      html(),
      libsWindowAssignment()
    ],
    server: {
      host: '0.0.0.0',
      hmr: {
        host: hmrHost
      }
    },
    build: {
      // Aggressive memory optimization
      chunkSizeWarningLimit: 2000,

      // Use esbuild for faster, more memory-efficient minification
      minify: 'esbuild',

      rollupOptions: {
        external: ['laravel-echo'],

        // Additional memory optimizations
        maxParallelFileOps: 2, // Limit parallel operations
      },

      // Disable sourcemap for production to save memory
      sourcemap: false,

      // CSS optimizations
      cssCodeSplit: true,
      cssMinify: 'esbuild',

      // Target modern browsers for smaller output
      target: 'es2015',

      // Smaller output
      reportCompressedSize: false,
    },

    // Optimize dependencies
    optimizeDeps: {
      include: [],
      exclude: []
    },

    // Increase cache directory to avoid re-processing
    cacheDir: 'node_modules/.vite'
  };
});
