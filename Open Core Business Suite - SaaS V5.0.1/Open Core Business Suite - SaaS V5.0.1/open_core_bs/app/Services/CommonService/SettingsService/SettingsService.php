<?php

namespace App\Services\CommonService\SettingsService;

use App\Models\Settings;
use App\Services\Settings\ModuleSettingsService;
use Illuminate\Support\Collection;

class SettingsService implements ISettings
{
    private Settings $settings;

    public function __construct()
    {
        $this->settings = Settings::first();
    }

    public function isDeviceVerificationEnabled(): bool
    {
        $settingsService = app(ModuleSettingsService::class);

        return (bool) $settingsService->get('FieldManager', 'is_device_verification_enabled', false);
    }

    public function getDocumentTypePrefix(): string
    {
        return $this->settings->document_type_code_prefix;
    }

    public function getAllSettings(): Collection
    {
        return collect($this->settings->toArray());
    }
}
