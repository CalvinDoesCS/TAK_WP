<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

class SystemStatusController extends Controller
{
    public function index()
    {
        $systemInfo = [
            'application' => $this->getApplicationInfo(),
            'server' => $this->getServerInfo(),
            'database' => $this->getDatabaseInfo(),
            'drivers' => $this->getDriverInfo(),
            'php_extensions' => $this->getPhpExtensions(),
            'modules' => $this->getModulesInfo(),
            'storage' => $this->getStorageInfo(),
            'services' => $this->getServicesStatus(),
        ];

        return view('system-status.index', compact('systemInfo'));
    }

    private function getApplicationInfo(): array
    {
        return [
            'name' => config('variables.templateName', 'Open Core BS'),
            'version' => config('variables.templateVersion', '1.0.0'),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'laravel_version' => app()->version(),
        ];
    }

    private function getServerInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_os' => PHP_OS_FAMILY.' '.php_uname('r'),
            'server_time' => now()->format('Y-m-d H:i:s T'),
            'max_execution_time' => ini_get('max_execution_time').' seconds',
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    private function getDatabaseInfo(): array
    {
        $connection = config('database.default');
        $databaseName = config("database.connections.{$connection}.database");

        $version = 'Unknown';
        try {
            if ($connection === 'mysql') {
                $result = DB::select('SELECT VERSION() as version');
                $version = $result[0]->version ?? 'Unknown';
            } elseif ($connection === 'pgsql') {
                $result = DB::select('SELECT version()');
                $version = $result[0]->version ?? 'Unknown';
            } elseif ($connection === 'sqlite') {
                $result = DB::select('SELECT sqlite_version() as version');
                $version = $result[0]->version ?? 'Unknown';
            }
        } catch (\Exception $e) {
            $version = 'Error: '.$e->getMessage();
        }

        return [
            'driver' => ucfirst($connection),
            'version' => $version,
            'database' => $databaseName,
            'host' => config("database.connections.{$connection}.host", 'N/A'),
            'port' => config("database.connections.{$connection}.port", 'N/A'),
        ];
    }

    private function getDriverInfo(): array
    {
        return [
            'cache' => config('cache.default'),
            'queue' => config('queue.default'),
            'session' => config('session.driver'),
            'broadcasting' => config('broadcasting.default'),
            'mail' => config('mail.default'),
            'filesystem' => config('filesystems.default'),
        ];
    }

    private function getPhpExtensions(): array
    {
        $requiredExtensions = [
            'bcmath' => 'BCMath',
            'ctype' => 'Ctype',
            'curl' => 'cURL',
            'dom' => 'DOM',
            'fileinfo' => 'Fileinfo',
            'gd' => 'GD',
            'json' => 'JSON',
            'mbstring' => 'Mbstring',
            'openssl' => 'OpenSSL',
            'pdo' => 'PDO',
            'pdo_mysql' => 'PDO MySQL',
            'tokenizer' => 'Tokenizer',
            'xml' => 'XML',
            'zip' => 'Zip',
            'redis' => 'Redis',
            'intl' => 'Intl',
            'exif' => 'EXIF',
            'pcntl' => 'PCNTL',
            'posix' => 'POSIX',
        ];

        $extensions = [];
        foreach ($requiredExtensions as $ext => $name) {
            $extensions[$name] = extension_loaded($ext);
        }

        return $extensions;
    }

    private function getModulesInfo(): array
    {
        $allModules = Module::all();
        $enabledModules = Module::allEnabled();
        $disabledModules = Module::allDisabled();

        $modulesList = [];
        foreach ($allModules as $module) {
            $modulesList[] = [
                'name' => $module->getName(),
                'enabled' => $module->isEnabled(),
                'path' => $module->getPath(),
            ];
        }

        // Sort by name
        usort($modulesList, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return [
            'total' => count($allModules),
            'enabled' => count($enabledModules),
            'disabled' => count($disabledModules),
            'list' => $modulesList,
        ];
    }

    private function getStorageInfo(): array
    {
        $storagePath = storage_path();
        $publicPath = public_path();

        return [
            'storage_writable' => is_writable($storagePath),
            'public_writable' => is_writable($publicPath),
            'storage_link_exists' => file_exists(public_path('storage')),
            'logs_writable' => is_writable(storage_path('logs')),
            'cache_writable' => is_writable(storage_path('framework/cache')),
            'sessions_writable' => is_writable(storage_path('framework/sessions')),
            'views_writable' => is_writable(storage_path('framework/views')),
        ];
    }

    private function getServicesStatus(): array
    {
        $services = [];

        // Redis
        try {
            $redisClient = config('database.redis.client');
            if ($redisClient && extension_loaded('redis')) {
                \Illuminate\Support\Facades\Redis::ping();
                $services['redis'] = ['status' => true, 'message' => 'Connected'];
            } elseif ($redisClient === 'predis') {
                \Illuminate\Support\Facades\Redis::ping();
                $services['redis'] = ['status' => true, 'message' => 'Connected (Predis)'];
            } else {
                $services['redis'] = ['status' => false, 'message' => 'Not configured'];
            }
        } catch (\Exception $e) {
            $services['redis'] = ['status' => false, 'message' => 'Not available'];
        }

        // Cache
        try {
            Cache::put('system_status_test', 'test', 1);
            $value = Cache::get('system_status_test');
            Cache::forget('system_status_test');
            $services['cache'] = ['status' => $value === 'test', 'message' => $value === 'test' ? 'Working' : 'Failed'];
        } catch (\Exception $e) {
            $services['cache'] = ['status' => false, 'message' => $e->getMessage()];
        }

        // Database
        try {
            DB::connection()->getPdo();
            $services['database'] = ['status' => true, 'message' => 'Connected'];
        } catch (\Exception $e) {
            $services['database'] = ['status' => false, 'message' => $e->getMessage()];
        }

        // WebSocket (Reverb)
        $reverbEnabled = config('reverb.servers.reverb.host') !== null;
        $services['websocket'] = [
            'status' => $reverbEnabled,
            'message' => $reverbEnabled ? 'Configured (Reverb)' : 'Not configured',
        ];

        // Mail
        $mailConfigured = ! empty(config('mail.mailers.smtp.host'));
        $services['mail'] = [
            'status' => $mailConfigured,
            'message' => $mailConfigured ? 'SMTP Configured' : 'Not configured',
        ];

        return $services;
    }
}
