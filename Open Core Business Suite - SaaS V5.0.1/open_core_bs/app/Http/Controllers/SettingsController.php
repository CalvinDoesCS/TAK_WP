<?php

namespace App\Http\Controllers;

use App\Mail\TestEmail;
use App\Models\Settings;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Settings::first();

        // Mask sensitive data in demo mode
        if (config('app.demo')) {
            $settings->map_api_key = '************************************';
            $settings->mail_host = 'smtp.example.com';
            $settings->mail_username = 'demo@example.com';
            $settings->mail_password = '********************';
            $settings->mail_from_address = 'demo@example.com';
            $settings->agora_app_id = '********************************';
            $settings->agora_app_certificate = '********************************';
        }

        return view('settings.index', [
            'settings' => $settings,
        ]);
    }

    public function updateCompanySettings(Request $request)
    {
        if (config('app.demo')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This feature is disabled in the demo.'),
                ], 403);
            }

            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        $request->validate([
            'company_name' => 'required',
            'company_logo' => 'nullable|image|max:2048',
            'company_address' => 'nullable',
            'company_phone' => 'nullable',
            'company_email' => 'nullable|email',
            'company_website' => 'nullable',
            'company_country' => 'nullable',
            'company_city' => 'nullable',
            'company_zipcode' => 'nullable',
            'company_state' => 'nullable',
        ]);

        $settings = Settings::first();
        $settings->fill($request->except('company_logo'));

        if ($request->hasFile('company_logo')) {

            if ($settings->company_logo && Storage::disk('public')->exists('images/'.$settings->company_logo)) {
                Storage::disk('public')->delete('images/'.$settings->company_logo);
            }

            Storage::disk('public')->putFileAs('images/', $request->file('company_logo'), 'app_logo.png');
            $settings->company_logo = 'app_logo.png';
        }

        $settings->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Company settings updated successfully'),
            ]);
        }

        return redirect()->back()->with('success', 'Company settings updated successfully');
    }

    public function updateGeneralSettings(Request $request)
    {
        if (config('app.demo')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This feature is disabled in the demo.'),
                ], 403);
            }

            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        $request->validate([
            'appName' => 'required',
            'country' => 'required',
            'phoneCountryCode' => 'required',
            'currency' => 'required',
            'currencySymbol' => 'required',
            'distanceUnit' => 'required',
            'isHelperTextEnabled' => 'nullable',
        ]);

        $settings = Settings::first();

        $settings->app_name = $request->appName;
        $settings->country = $request->country;
        $settings->phone_country_code = $request->phoneCountryCode;
        $settings->currency = $request->currency;
        $settings->currency_symbol = $request->currencySymbol;
        $settings->distance_unit = $request->distanceUnit;
        $settings->is_helper_text_enabled = $request->isHelperTextEnabled == 'on';

        $settings->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Settings updated successfully'),
            ]);
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    public function updateEmployeeSettings(Request $request)
    {
        if (config('app.demo')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This feature is disabled in the demo.'),
                ], 403);
            }

            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        $request->validate([
            'defaultPassword' => 'required|min:8',
        ]);

        $settings = Settings::first();
        $settings->default_password = $request->defaultPassword;
        $settings->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Settings updated successfully'),
            ]);
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    public function updateMapSettings(Request $request)
    {
        if (config('app.demo')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This feature is disabled in the demo.'),
                ], 403);
            }

            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        $request->validate([
            'mapProvider' => 'required',
            'mapApiKey' => 'required',
            'mapZoomLevel' => 'required|integer|min:1|max:20',
            'centerLatitude' => 'required',
            'centerLongitude' => 'required',
        ]);

        $settings = Settings::first();

        $settings->map_provider = $request->mapProvider;
        $settings->map_api_key = $request->mapApiKey;
        $settings->map_zoom_level = $request->mapZoomLevel;
        $settings->center_latitude = $request->centerLatitude;
        $settings->center_longitude = $request->centerLongitude;

        $settings->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Settings updated successfully'),
            ]);
        }

        return redirect()->back()->with('success', 'Settings updated successfully');
    }

    public function updateMailSettings(Request $request)
    {
        if (config('app.demo')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This feature is disabled in the demo.'),
                ], 403);
            }

            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        try {
            $validatedData = $request->validate([
                'mail_driver' => 'nullable|string|max:50',
                'mail_host' => 'required|string|max:255',
                'mail_port' => 'required|integer|min:1|max:65535',
                'mail_username' => 'nullable|string|max:255',
                'mail_password' => 'nullable|string|max:255',
                'mail_encryption' => 'nullable|string|in:tls,ssl,none',
                'mail_from_address' => 'required|email|max:255',
                'mail_from_name' => 'required|string|max:255',
            ]);

            $settings = Settings::first();

            // Convert 'none' to null for encryption
            if (isset($validatedData['mail_encryption']) && $validatedData['mail_encryption'] === 'none') {
                $validatedData['mail_encryption'] = null;
            }

            $settings->update($validatedData);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Mail settings updated successfully!'),
                ]);
            }

            return redirect()->back()->with('success', 'Mail settings updated successfully!');
        } catch (Exception $e) {
            Log::error('Error updating mail settings: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to update mail settings.'),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update mail settings.');
        }
    }

    public function sendTestEmail(Request $request)
    {
        if (config('app.demo')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This feature is disabled in the demo.'),
                ], 403);
            }

            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        try {
            $validatedData = $request->validate([
                'test_email' => 'required|email',
            ]);

            // Send test email
            Mail::to($validatedData['test_email'])->send(new TestEmail);

            return response()->json([
                'success' => true,
                'message' => __('Test email sent successfully to :email', ['email' => $validatedData['test_email']]),
            ]);
        } catch (Exception $e) {
            Log::error('Error sending test email: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to send test email: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function updateBrandingSettings(Request $request)
    {
        if (config('app.demo')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('This feature is disabled in the demo.'),
                ], 403);
            }

            return redirect()->back()->with('error', 'This feature is disabled in the demo.');
        }

        try {
            $request->validate([
                'app_logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
                'app_favicon' => 'nullable|image|mimes:png,jpg,jpeg,ico|max:512',
            ]);

            $settings = Settings::first();

            // Handle App Logo upload
            if ($request->hasFile('app_logo')) {
                // Delete old logo if exists
                if ($settings->app_logo && file_exists(public_path('assets/img/'.$settings->app_logo))) {
                    unlink(public_path('assets/img/'.$settings->app_logo));
                }

                // Store new logo
                $logoFile = $request->file('app_logo');
                $logoName = 'logo.png';
                $logoFile->move(public_path('assets/img'), $logoName);
                $settings->app_logo = $logoName;
            }

            // Handle Favicon upload
            if ($request->hasFile('app_favicon')) {
                // Delete old favicon if exists
                if ($settings->app_favicon && file_exists(public_path('assets/img/favicon/'.$settings->app_favicon))) {
                    unlink(public_path('assets/img/favicon/'.$settings->app_favicon));
                }

                // Store new favicon
                $faviconFile = $request->file('app_favicon');
                $faviconName = 'favicon.ico';
                $faviconFile->move(public_path('assets/img/favicon'), $faviconName);
                $settings->app_favicon = $faviconName;
            }

            $settings->save();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Branding updated successfully'),
                    'data' => [
                        'logo_url' => $settings->app_logo ? asset('assets/img/'.$settings->app_logo) : null,
                        'favicon_url' => $settings->app_favicon ? asset('assets/img/favicon/'.$settings->app_favicon) : null,
                    ],
                ]);
            }

            return redirect()->back()->with('success', 'Branding updated successfully');
        } catch (Exception $e) {
            Log::error('Error updating branding settings: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to update branding settings.'),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update branding settings.');
        }
    }
}
