import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import { glob } from 'glob';
import fs from 'fs';
import path from 'path';

/**
 * ULTRA-LEAN VITE CONFIG FOR VERY LIMITED MEMORY
 *
 * This config includes ALL vendor libraries (essential for app functionality)
 * but limits module assets to first 15 modules to reduce memory usage.
 *
 * Run with: node --max-old-space-size=4096 ./node_modules/vite/bin/vite.js build --config vite.config.ultra-lean.js
 *
 * Note: If you see "Unable to locate file in Vite manifest" errors:
 * 1. Enable swap space (4GB recommended)
 * 2. Use yarn build:server:optimized (requires 8GB total memory)
 * 3. Build locally and deploy (recommended for production)
 */

function GetFilesArray(query) {
  return glob.sync(query);
}

function getModuleAssets(limit = 10) {
  const moduleAssets = [];

  try {
    const moduleStatusesPath = path.join(process.cwd(), 'modules_statuses.json');
    const moduleStatuses = JSON.parse(fs.readFileSync(moduleStatusesPath, 'utf-8'));

    // Only process first N enabled modules to reduce memory
    let count = 0;
    Object.entries(moduleStatuses).forEach(([moduleName, isEnabled]) => {
      if (isEnabled && count < limit) {
        const moduleJsFiles = GetFilesArray(`Modules/${moduleName}/resources/assets/js/**/*.js`);
        moduleAssets.push(...moduleJsFiles);

        const moduleScssFiles = GetFilesArray(`Modules/${moduleName}/resources/assets/sass/**/*.scss`);
        const moduleCssFiles = GetFilesArray(`Modules/${moduleName}/resources/assets/css/**/*.css`);
        moduleAssets.push(...moduleScssFiles, ...moduleCssFiles);

        count++;
      }
    });

    console.log(`Loaded assets from ${count} modules (limited for memory optimization)`);
  } catch (error) {
    console.warn('Could not load module assets:', error.message);
  }

  return moduleAssets;
}

// Core files - include all vendor libs (critical for app to work)
const pageJsFiles = GetFilesArray('resources/assets/js/*.js');
const pageAppJsFiles = GetFilesArray('resources/assets/js/app/*.js');
const pageDirJsFiles = GetFilesArray('resources/assets/js/pages/*.js');
const employeeReportJsFiles = GetFilesArray('resources/assets/js/employees/**/*.js');
const leaveReportJsFiles = GetFilesArray('resources/assets/js/leave/**/*.js');

const vendorJsFiles = GetFilesArray('resources/assets/vendor/js/*.js');
const LibsJsFiles = GetFilesArray('resources/assets/vendor/libs/**/*.js');

const CoreScssFiles = GetFilesArray('resources/assets/vendor/scss/**/!(_)*.scss');
const LibsScssFiles = GetFilesArray('resources/assets/vendor/libs/**/!(_)*.scss');
const LibsCssFiles = GetFilesArray('resources/assets/vendor/libs/**/*.css');
const FontsScssFiles = GetFilesArray('resources/assets/vendor/fonts/!(_)*.scss');

const pageCssFiles = GetFilesArray('resources/assets/css/*.css');
const pageScssFiles = GetFilesArray('resources/assets/scss/**/*.scss');

// Get limited module assets (only limit modules, not core vendor libs)
const moduleAssets = getModuleAssets(15); // Increase to 15 modules

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
  const env = loadEnv(mode, process.cwd(), '');

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
          'resources/js/main-helper.js',
          'resources/js/main-datatable.js',
          'resources/js/main-select2.js',
          ...CoreScssFiles,
          ...LibsScssFiles,
          ...LibsCssFiles,
          ...FontsScssFiles,
          ...pageCssFiles,
          ...pageScssFiles,
          // Include limited module assets
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
      // Ultra-aggressive memory optimization
      chunkSizeWarningLimit: 2000,
      minify: 'esbuild',

      rollupOptions: {
        external: ['laravel-echo'],
        maxParallelFileOps: 1, // Serialize operations to save memory

        output: {
          manualChunks: undefined, // Disable manual chunking to reduce memory
        }
      },

      sourcemap: false,
      cssCodeSplit: true, // Keep CSS splitting for better memory management
      cssMinify: 'esbuild',
      target: 'es2015',
      reportCompressedSize: false,
    },

    optimizeDeps: {
      include: [],
      exclude: []
    },

    cacheDir: 'node_modules/.vite'
  };
});
