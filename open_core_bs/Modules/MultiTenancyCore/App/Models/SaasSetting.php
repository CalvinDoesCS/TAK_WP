<?php

namespace Modules\MultiTenancyCore\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaasSetting extends Model
{
    use HasFactory;

    protected $table = 'saas_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get typed value
     */
    public function getTypedValue()
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->value;
            case 'json':
                return json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    /**
     * Set typed value
     */
    public function setTypedValue($value)
    {
        switch ($this->type) {
            case 'boolean':
                $this->value = $value ? '1' : '0';
                break;
            case 'json':
                $this->value = json_encode($value);
                break;
            default:
                $this->value = (string) $value;
        }
    }

    /**
     * Get a setting by key
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->getTypedValue() : $default;
    }

    /**
     * Set a setting by key
     */
    public static function set($key, $value, $type = 'string', $description = null)
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->type = $type;
        $setting->description = $description ?: $setting->description;
        $setting->setTypedValue($value);
        $setting->save();

        return $setting;
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllAsArray()
    {
        $settings = [];
        foreach (static::all() as $setting) {
            $settings[$setting->key] = $setting->getTypedValue();
        }

        return $settings;
    }

    /**
     * Get offline payment settings
     */
    public static function getOfflinePaymentSettings(): array
    {
        $keys = [
            'bank_name',
            'account_name',
            'account_number',
            'routing_number',
            'swift_code',
            'bank_address',
            'payment_instructions',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = static::get('offline_payment_'.$key, '');
        }

        return $settings;
    }
}
