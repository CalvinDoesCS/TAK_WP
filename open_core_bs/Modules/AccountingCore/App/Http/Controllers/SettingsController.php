<?php

namespace Modules\AccountingCore\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Settings\ModuleSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\AccountingCore\App\Settings\AccountingCoreSettings;

class SettingsController extends Controller
{
    protected AccountingCoreSettings $settings;

    protected ModuleSettingsService $settingsService;

    public function __construct(AccountingCoreSettings $settings, ModuleSettingsService $settingsService)
    {
        $this->settings = $settings;
        $this->settingsService = $settingsService;
    }

    /**
     * Display the settings page
     */
    public function index()
    {
        $currentValues = $this->settings->getCurrentValues();
        $sections = $this->settings->getSections();
        $moduleName = $this->settings->getModuleName();
        $moduleDescription = $this->settings->getModuleDescription();
        $moduleIcon = $this->settings->getModuleIcon();

        return view('accountingcore::settings.index', compact(
            'currentValues',
            'sections',
            'moduleName',
            'moduleDescription',
            'moduleIcon'
        ));
    }

    /**
     * Update the settings
     */
    public function update(Request $request)
    {
        // Prepare data for validation - convert checkbox values before validation
        $data = $request->all();

        // Convert checkbox values to boolean for validation
        $booleanFields = ['allow_future_dates', 'require_attachments', 'allow_custom_categories'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Validate the prepared data
        $validator = Validator::make($data, $this->settings->getValidationRules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Save settings using the service
        foreach ($validated as $key => $value) {
            $this->settingsService->set('AccountingCore', $key, $value);
        }

        return response()->json([
            'success' => true,
            'message' => __('Settings updated successfully'),
        ]);
    }
}
