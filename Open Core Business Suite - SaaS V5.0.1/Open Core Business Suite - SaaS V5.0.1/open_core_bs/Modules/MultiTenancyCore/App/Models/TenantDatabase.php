<?php

namespace Modules\MultiTenancyCore\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TenantDatabase extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'host',
        'port',
        'database_name',
        'username',
        'encrypted_password',
        'provisioning_status',
        'provisioned_at',
        'last_verified_at',
        'provisioning_error'
    ];

    protected $casts = [
        'provisioned_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    /**
     * Get the tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get decrypted password
     */
    public function getPasswordAttribute()
    {
        if (!$this->encrypted_password) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_password);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted password
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['encrypted_password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Check if database is provisioned
     */
    public function isProvisioned()
    {
        return $this->provisioning_status === 'provisioned';
    }

    /**
     * Check if provisioning failed
     */
    public function provisioningFailed()
    {
        return $this->provisioning_status === 'failed';
    }

    /**
     * Check if manual provisioning
     */
    public function isManual()
    {
        return $this->provisioning_status === 'manual';
    }

    /**
     * Get database configuration array
     */
    public function getConfiguration()
    {
        return [
            'driver' => 'mysql',
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database_name,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];
    }

    /**
     * Mark as provisioned
     */
    public function markAsProvisioned()
    {
        $this->provisioning_status = 'provisioned';
        $this->provisioned_at = now();
        $this->provisioning_error = null;
        $this->save();
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($error = null)
    {
        $this->provisioning_status = 'failed';
        $this->provisioning_error = $error;
        $this->save();
    }

    /**
     * Update verification timestamp
     */
    public function markAsVerified()
    {
        $this->last_verified_at = now();
        $this->save();
    }

    /**
     * Generate manual provisioning instructions
     */
    public function getManualInstructions()
    {
        $dbName = $this->database_name;
        $username = $this->username;
        $password = $this->password;

        return [
            'database_name' => $dbName,
            'username' => $username,
            'password' => $password,
            'sql_commands' => [
                "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
                "CREATE USER IF NOT EXISTS '{$username}'@'%' IDENTIFIED BY '{$password}';",
                "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$username}'@'%';",
                "FLUSH PRIVILEGES;"
            ],
            'cpanel_instructions' => __('In cPanel, create a MySQL database named :dbname and a user :username with password. Then assign all privileges to the user on the database.', [
                'dbname' => $dbName,
                'username' => $username
            ])
        ];
    }
}