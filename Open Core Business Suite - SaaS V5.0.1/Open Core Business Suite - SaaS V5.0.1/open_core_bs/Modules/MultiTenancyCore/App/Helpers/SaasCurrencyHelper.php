<?php

namespace Modules\MultiTenancyCore\App\Helpers;

use App\Models\Settings;
use Modules\MultiTenancyCore\App\Models\SaasSetting;

class SaasCurrencyHelper
{
    /**
     * Format amount with currency symbol
     */
    public static function format(float|int|null $amount, ?string $currency = null): string
    {
        if ($amount === null) {
            $amount = 0;
        }

        $symbol = self::getSymbol($currency);

        return $symbol.number_format($amount, 2);
    }

    /**
     * Get currency symbol
     */
    public static function getSymbol(?string $currency = null): string
    {
        if ($currency) {
            return self::getCurrencySymbol($currency);
        }

        // Try to get from SaaS settings first
        $saasCurrency = SaasSetting::get('general_currency');
        if ($saasCurrency) {
            return self::getCurrencySymbol($saasCurrency);
        }

        $saasCurrencySymbol = SaasSetting::get('general_currency_symbol');
        if ($saasCurrencySymbol) {
            return $saasCurrencySymbol;
        }

        // Fall back to main settings
        $settings = Settings::first();

        return $settings->currency_symbol ?? '$';
    }

    /**
     * Get symbol for specific currency code
     */
    protected static function getCurrencySymbol(string $code): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'AED' => 'AED ',
            'SAR' => 'SAR ',
            'QAR' => 'QAR ',
            'KWD' => 'KWD ',
            'BHD' => 'BHD ',
            'OMR' => 'OMR ',
            'JPY' => '¥',
            'CNY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'CHF ',
        ];

        return $symbols[$code] ?? $code.' ';
    }
}
