<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use ModuleConstants;
use Nwidart\Modules\Facades\Module;

class SettingsController extends Controller
{
    public function getAppSettings()
    {
        $settings = Settings::first();

        $response = [
            'isSaaSMode' => isSaaSMode(),
            'isDemoMode' => config('app.demo', false),
            'privacyPolicyUrl' => $settings->privacy_policy_url,
            'currency' => $settings->currency,
            'currencySymbol' => $settings->currency_symbol,
            'distanceUnit' => $settings->distance_unit,
            'countryPhoneCode' => $settings->phone_country_code,
            'supportEmail' => $settings->support_email,
            'supportPhone' => $settings->support_phone,
            'supportWhatsapp' => $settings->support_whatsapp,
            'website' => $settings->website,
            'companyName' => $settings->company_name,
            'companyLogo' => $settings->company_logo ? asset('storage/images/'.$settings->company_logo) : null,
            'companyAddress' => $settings->company_address,
            'companyPhone' => $settings->company_phone,
            'companyEmail' => $settings->company_email,
            'companyWebsite' => $settings->company_website,
            'companyCountry' => $settings->company_country,
            'companyState' => $settings->company_state,
        ];

        return Success::response($response);
    }

    public function getModuleSettings()
    {
        // Get tenant plan allowed modules if in SaaS tenant context
        $allowedModules = null;

        if (isSaaSMode() && function_exists('isTenant') && isTenant()) {
            $tenant = tenant();

            if ($tenant) {
                // Load subscription with plan eagerly to ensure proper data access
                $subscription = $tenant->activeSubscription()->with('plan')->first();
                $plan = $subscription?->plan;

                if ($plan) {
                    $allowedModules = $plan->getAllowedModules();
                    // Empty array means all modules allowed, so set to null to skip filtering
                    if (empty($allowedModules)) {
                        $allowedModules = null;
                    }
                }
            }
        }

        // Helper to check if module is enabled (system-wide and tenant plan if applicable)
        $isModuleEnabled = function (string $moduleConstant) use ($allowedModules) {
            $systemEnabled = Module::has($moduleConstant) && Module::isEnabled($moduleConstant);

            if (! $systemEnabled) {
                return false;
            }

            // If no plan restrictions (null), allow all system-enabled modules
            if ($allowedModules === null) {
                return true;
            }

            // Check if module is in allowed list or is a core module
            if (function_exists('isCoreModule') && isCoreModule($moduleConstant)) {
                return true;
            }

            return \in_array($moduleConstant, $allowedModules, true);
        };

        $response = [
            'isExpenseModuleEnabled' => true,
            'isLeaveModuleEnabled' => true,
            'isChatModuleEnabled' => true,
            'isClientVisitModuleEnabled' => true,
            'isSosModuleEnabled' => true,
            'isAttendanceModuleEnabled' => true,
            'isBiometricVerificationModuleEnabled' => false,
            'isProductModuleEnabled' => $isModuleEnabled(ModuleConstants::PRODUCT_ORDER),
            'isFieldTaskModuleEnabled' => $isModuleEnabled(ModuleConstants::FIELD_TASK),
            'isNoticeModuleEnabled' => $isModuleEnabled(ModuleConstants::NOTICE_BOARD),
            'isDynamicFormModuleEnabled' => $isModuleEnabled(ModuleConstants::DYNAMIC_FORMS),
            'isDocumentModuleEnabled' => $isModuleEnabled(ModuleConstants::DOCUMENT),
            'isLoanModuleEnabled' => $isModuleEnabled(ModuleConstants::LOAN_MANAGEMENT),
            'isAiChatModuleEnabled' => $isModuleEnabled(ModuleConstants::AI_CHATBOT),
            'isPaymentCollectionModuleEnabled' => $isModuleEnabled(ModuleConstants::PAYMENT_COLLECTION),
            'isGeofenceModuleEnabled' => $isModuleEnabled(ModuleConstants::GEOFENCE),
            'isIpBasedAttendanceModuleEnabled' => $isModuleEnabled(ModuleConstants::IP_ADDRESS_ATTENDANCE),
            'isUidLoginModuleEnabled' => $isModuleEnabled(ModuleConstants::UID_LOGIN),
            'isOfflineTrackingModuleEnabled' => $isModuleEnabled(ModuleConstants::OFFLINE_TRACKING),
            'isQrCodeAttendanceModuleEnabled' => $isModuleEnabled(ModuleConstants::QR_ATTENDANCE),
            'isDynamicQrCodeAttendanceEnabled' => $isModuleEnabled(ModuleConstants::DYNAMIC_QR_ATTENDANCE),
            'isBreakModuleEnabled' => $isModuleEnabled(ModuleConstants::BREAK),
            'isSiteModuleEnabled' => $isModuleEnabled(ModuleConstants::SITE_ATTENDANCE),
            'isDataImportExportModuleEnabled' => $isModuleEnabled(ModuleConstants::DATA_IMPORT_EXPORT),
            'isPayrollModuleEnabled' => $isModuleEnabled(ModuleConstants::PAYROLL),
            'isSalesTargetModuleEnabled' => $isModuleEnabled(ModuleConstants::SALES_TARGET),
            'isDigitalIdCardModuleEnabled' => $isModuleEnabled(ModuleConstants::DIGITAL_ID_CARD),
            'isApprovalModuleEnabled' => $isModuleEnabled(ModuleConstants::APPROVALS),
            'isRecruitmentModuleEnabled' => $isModuleEnabled(ModuleConstants::RECRUITMENT),
            'isCalendarModuleEnabled' => $isModuleEnabled(ModuleConstants::CALENDAR),
            'isAccountingModuleEnabled' => $isModuleEnabled(ModuleConstants::ACCOUNTING),
            'isManagerAppModuleEnabled' => $isModuleEnabled(ModuleConstants::MANAGER_APP),
            'isFaceAttendanceModuleEnabled' => $isModuleEnabled(ModuleConstants::FACE_ATTENDANCE),
            'isNotesModuleEnabled' => $isModuleEnabled(ModuleConstants::NOTES),
            'isAssetsModuleEnabled' => $isModuleEnabled(ModuleConstants::ASSETS),
            'isDisciplinaryActionsModuleEnabled' => $isModuleEnabled(ModuleConstants::DISCIPLINARY_ACTIONS),
            'isHrPoliciesModuleEnabled' => $isModuleEnabled(ModuleConstants::HR_POLICIES),
            'isGoogleRecaptchaModuleEnabled' => $isModuleEnabled(ModuleConstants::GOOGLE_RECAPTCHA),
            'isSystemBackupModuleEnabled' => $isModuleEnabled(ModuleConstants::SYSTEM_BACKUP),
            'isLmsModuleEnabled' => $isModuleEnabled(ModuleConstants::LMS),
            'isFaceAttendanceDeviceModuleEnabled' => $isModuleEnabled(ModuleConstants::FACE_ATTENDANCE_DEVICE),
            'isFieldSalesModuleEnabled' => $isModuleEnabled(ModuleConstants::FIELD_MANAGER),
            'isAgoraCallModuleEnabled' => $isModuleEnabled(ModuleConstants::AGORA_CALL),
            'isLocationManagementModuleEnabled' => $isModuleEnabled(ModuleConstants::LOCATION_MANAGEMENT),
            'isOcConnectModuleEnabled' => $isModuleEnabled(ModuleConstants::OC_CONNECT),
        ];

        return Success::response($response);
    }
}
