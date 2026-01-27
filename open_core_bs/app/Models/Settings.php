<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Settings extends Model implements AuditableContract
{
    use Auditable;

    public $timestamps = false;

    protected $table = 'settings';

    protected $fillable = [
        'default_language',
        'app_name',
        'app_logo',
        'app_favicon',
        'app_version',
        'country',
        'phone_country_code',
        'default_timezone',
        'currency',
        'currency_symbol',
        'distance_unit',
        'm_app_version',
        'm_location_update_time_type',
        'm_location_update_interval',
        'privacy_policy_url',
        'verify_client_number',
        'employee_code_prefix',
        'order_prefix',
        'map_provider',
        'map_zoom_level',
        'center_latitude',
        'center_longitude',
        'is_helper_text_enabled',
        'default_password',
        'is_biometric_verification_enabled',
        'map_api_key',
        'employees_limit',
        'accessible_module_routes',
        'support_email',
        'support_phone',
        'support_whatsapp',
        'website',
        'is_multiple_check_in_enabled',
        'is_auto_check_out_enabled',
        'company_name',
        'company_logo',
        'company_address',
        'company_phone',
        'company_email',
        'company_website',
        'company_country',
        'company_state',
        'company_city',
        'company_zipcode',
        'company_tax_id',
        'company_reg_no',
        'payroll_frequency',
        'payroll_start_date',
        'payroll_cutoff_date',
        'auto_payroll_processing',
        'branding_type',
        'maps_key',
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
    ];

    protected $casts = [
        'm_location_update_interval' => 'integer',
        'center_latitude' => 'float',
        'center_longitude' => 'float',
        'is_biometric_verification_enabled' => 'boolean',
        'is_helper_text_enabled' => 'boolean',
        'employees_limit' => 'integer',
        'is_multiple_check_in_enabled' => 'boolean',
        'is_auto_check_out_enabled' => 'boolean',
        'mail_port' => 'integer',
    ];

    // This function is used to clear cache
    protected static function boot()
    {
        parent::boot();
        static::saved(function () {
            Cache::forget('app_settings');
        });
    }
}
