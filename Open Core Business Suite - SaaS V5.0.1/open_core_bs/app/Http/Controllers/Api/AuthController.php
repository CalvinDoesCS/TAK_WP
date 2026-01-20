<?php

namespace App\Http\Controllers\Api;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Config\Constants;
use App\Enums\UserAccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Models\Designation;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use App\Services\UserService\IUserService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Exception;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private IUserService $userService;

    public function __construct(IUserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(LoginRequest $request)
    {
        $user = $this->userService->findUserByEmail($request['employeeId']);

        if (is_null($user)) {
            return Error::response('User not found', 404);
        }

        $role = $user->roles()->first();

        if (! $role) {
            return Error::response('You do not have permission to access this resource', 403);
        }

        if (! $role->is_mobile_app_access_enabled) {
            return Error::response('You do not have permission to access this resource', 403);
        }

        if ($user->status != UserAccountStatus::ACTIVE && $user->status != UserAccountStatus::ONBOARDING) {
            return Error::response('User account is not active', 403);
        }

        if (! (new BcryptHasher)->check($request['password'], $user->password)) {
            return Error::response('Email or password is incorrect. Authentication failed.');
        }

        $credentials = ['email' => $user->email, 'password' => $request['password']];

        try {

            $token = $this->generateToken($credentials);
            if ($token == '') {
                return Error::response('Could not generate token, authentication failed');
            }

            $response = [
                'id' => $user->id,
                'firstName' => $user->first_name,
                'lastName' => $user->last_name,
                'employeeCode' => $user->code,
                'dob' => $user->dob != null ? $user->dob->format(Constants::DateFormat) : null,
                'gender' => $user->gender,
                'email' => $user->email,
                'phoneNumber' => $user->phone,
                'status' => $user->status,
                'role' => $role->name,
                'isLocationActivityTrackingEnabled' => (bool) $role->is_location_activity_tracking_enabled,
                'designation' => $user->designation ? $user->designation->name : null,
                'createdAt' => $user->created_at->format(Constants::DateTimeFormat),
                'avatar' => $user->profile_picture ? asset(Constants::BaseFolderEmployeeProfileWithSlash.$user->profile_picture) : null,
                'token' => $token,
                'expiresIn' => JWTAuth::factory()->getTTL(),
            ];

            return Success::response($response);

        } catch (JWTException $e) {
            Log::error($e->getMessage());

            return Error::response('Could not create token');
        }

    }

    private function generateToken($credentials)
    {
        if (! $token = JWTAuth::attempt($credentials)) {
            return '';
        }

        return $token;
    }

    public function refresh()
    {
        $token = JWTAuth::getToken();
        if (! $token) {
            return Error::response('Token not provided', 401);
        }

        try {

            $newToken = JWTAuth::refresh();

            return Success::response(['token' => $newToken, 'expiresIn' => JWTAuth::factory()->getTTL()]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return Error::response('Could not refresh token');
        }
    }

    public function logout()
    {
        $token = JWTAuth::getToken();

        if (! $token) {
            return Error::response('Token not provided', 401);
        }

        JWTAuth::setToken($token)->invalidate();

        return Success::response('Successfully logged out');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $valReq = $request->validated();

        $user = auth()->user();

        if (! (new BcryptHasher)->check($valReq['currentPassword'], $user->password)) {
            return Error::response('Current password is incorrect');
        }

        $user->password = (new BcryptHasher)->make($valReq['newPassword']);
        $user->save();

        return Success::response('Password changed successfully');
    }

    public function checkEmail(Request $request)
    {
        $userName = $request->all();

        if (! $userName) {
            return Error::response('Invalid request');
        }

        $userName = $userName[0];

        if ($this->userService->checkEmailExists($userName)) {
            return Success::response('Email exists');
        }

        return Error::response('Email does not exist');
    }

    /**
     * NOTE: loginWithUid has been moved to the UidLogin module
     * See: Modules/UidLogin/App/Http/Controllers/UidLoginController.php
     * Route: POST /api/V1/loginWithUid
     *
     * This is a paid module feature for device-based authentication
     */
    public function createDemoUser()
    {
        if (! config('app.demo')) {
            return Error::response('Demo mode is not enabled');
        }

        $randomEmail = 'demo'.rand(1, 1000).'@demo.com';
        while ($this->userService->checkEmailExists($randomEmail)) {
            $randomEmail = 'demo'.rand(1, 1000).'@demo.com';
        }

        $randomPhoneNumber = rand(100000000, 999999999);
        while (User::where('phone', $randomPhoneNumber)->exists()) {
            $randomPhoneNumber = rand(100000000, 999999999);
        }

        $randomCode = 'DEMO'.rand(1, 1000);
        while (User::where('code', $randomCode)->exists()) {
            $randomCode = 'DEMO'.rand(1, 1000);
        }

        $designation = Designation::first();

        $shift = Shift::first();

        $team = Team::first();

        $user = new User;
        $user->first_name = 'Demo';
        $user->last_name = rand(1, 1000);
        $user->email = $randomEmail;
        $user->password = (new BcryptHasher)->make('123456');
        $user->status = UserAccountStatus::ACTIVE;
        $user->code = $randomCode;
        $user->phone = $randomPhoneNumber;
        $user->shift_id = $shift->id;
        $user->designation_id = $designation->id;
        $user->team_id = $team->id;
        $user->dob = now();
        $user->date_of_joining = now();

        $user->save();

        $user->assignRole('field_employee');

        try {
            if (! $token = JWTAuth::fromUser($user, ['exp' => Carbon::now()->addDays(28)->timestamp])) {
                return Error::response('Unable to create token');
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $response = [
            'token' => $token,
            'id' => $user->id,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'employeeCode' => $user->code,
            'dob' => $user->dob ? $user->dob->format(Constants::DateFormat) : null,
            'gender' => $user->gender,
            'email' => $user->email,
            'phoneNumber' => $user->phone,
            'status' => $user->status,
            'role' => 'field_employee',
            'isLocationActivityTrackingEnabled' => true,
            'designation' => $user->designation ? $user->designation->name : null,
            'createdAt' => $user->created_at->format(Constants::DateTimeFormat),
            'avatar' => $user->profile_picture ? asset(Constants::BaseFolderEmployeeProfileWithSlash.$user->profile_picture) : null,
            'expiresIn' => JWTAuth::factory()->getTTL(),
        ];

        return Success::response($response);
    }

    /**
     * Create Demo User V2 - Compatible with Flutter AuthResponseModel
     * Returns same structure as loginV2 for consistent parsing
     */
    public function createDemoUserV2()
    {
        if (! config('app.demo')) {
            return ApiResponse::error('Demo mode is not enabled', 403);
        }

        $randomEmail = 'demo'.rand(1, 1000).'@demo.com';
        while ($this->userService->checkEmailExists($randomEmail)) {
            $randomEmail = 'demo'.rand(1, 1000).'@demo.com';
        }

        $randomPhoneNumber = rand(100000000, 999999999);
        while (User::where('phone', $randomPhoneNumber)->exists()) {
            $randomPhoneNumber = rand(100000000, 999999999);
        }

        $randomCode = 'DEMO'.rand(1, 1000);
        while (User::where('code', $randomCode)->exists()) {
            $randomCode = 'DEMO'.rand(1, 1000);
        }

        $designation = Designation::first();
        $shift = Shift::first();
        $team = Team::first();

        $user = new User;
        $user->first_name = 'Demo';
        $user->last_name = (string) rand(1, 1000);
        $user->email = $randomEmail;
        $user->password = (new BcryptHasher)->make('123456');
        $user->status = UserAccountStatus::ACTIVE;
        $user->code = $randomCode;
        $user->phone = $randomPhoneNumber;
        $user->shift_id = $shift->id;
        $user->designation_id = $designation->id;
        $user->team_id = $team->id;
        $user->dob = now();
        $user->date_of_joining = now();

        $user->save();
        $user->assignRole('field_employee');

        // Reload user with relationships
        $user->load(['designation.department']);
        $role = $user->roles()->first();

        try {
            if (! $token = JWTAuth::fromUser($user, ['exp' => Carbon::now()->addDays(28)->timestamp])) {
                return ApiResponse::serverError('Unable to create token');
            }
        } catch (JWTException $e) {
            Log::error('JWT Generation Error: '.$e->getMessage());

            return ApiResponse::serverError('Authentication token generation failed');
        }

        // Build response matching loginV2 structure
        // Note: All string fields must be cast to string as Flutter expects String types
        $response = [
            // User Basic Info
            'id' => $user->id,
            'firstName' => (string) $user->first_name,
            'lastName' => (string) $user->last_name,
            'fullName' => $user->first_name.' '.$user->last_name,
            'email' => (string) $user->email,
            'phone' => $user->phone ? (string) $user->phone : null,
            'alternateNumber' => $user->alternate_number ? (string) $user->alternate_number : null,
            'employeeCode' => (string) $user->code,

            // Personal Info
            'dateOfBirth' => $user->dob?->format('Y-m-d'),
            'gender' => $user->gender ?? null,
            'profilePicture' => method_exists($user, 'getProfilePicture') ? $user->getProfilePicture() : null,
            'language' => $user->language ?? 'en',

            // Work Details
            'dateOfJoining' => $user->date_of_joining?->format('Y-m-d'),
            'status' => $user->status instanceof UserAccountStatus ? $user->status->value : (string) $user->status,
            'baseSalary' => 0.0,
            'attendanceType' => $user->attendance_type ?? 'regular',

            // Designation Details
            'designation' => $user->designation ? [
                'id' => $user->designation->id,
                'name' => $user->designation->name,
                'code' => $user->designation->code ?? null,
                'department' => $user->designation->department ? [
                    'id' => $user->designation->department->id,
                    'name' => $user->designation->department->name,
                ] : null,
            ] : null,

            // Role & Permissions
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'displayName' => $role->name,
                'isMobileAppAccessEnabled' => (bool) ($role->is_mobile_app_access_enabled ?? true),
                'isLocationActivityTrackingEnabled' => (bool) ($role->is_location_activity_tracking_enabled ?? true),
            ] : null,

            // Authentication
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => JWTAuth::factory()->getTTL() * 60,

            // Multi-tenancy Info (not applicable for demo)
            'tenantId' => null,
            'organization' => null,
            'isSaaSMode' => false,
        ];

        return ApiResponse::success($response, 'Demo user created successfully');
    }

    /**
     * Login V2 - Comprehensive employee data for mobile apps
     * Simple email and password login with all necessary employee information
     * Supports multi-tenancy when MultiTenancyCore module is enabled
     */
    public function loginV2(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'organization_code' => 'nullable|string', // Optional for SaaS mode
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator);
        }

        // Check if MultiTenancyCore module is enabled (SaaS mode)
        $isSaaSMode = app(\App\Services\AddonService\AddonService::class)->isAddonEnabled('MultiTenancyCore');
        $tenant = null;

        // Handle tenant context in SaaS mode
        if ($isSaaSMode) {
            if (! $request->organization_code) {
                return ApiResponse::error(__('organization_code is required in SaaS mode'), 400);
            }

            // Find tenant by organization code (subdomain)
            $tenant = \Modules\MultiTenancyCore\App\Models\Tenant::where('subdomain', strtolower($request->organization_code))
                ->where('status', 'active')
                ->first();

            if (! $tenant) {
                return ApiResponse::notFound('Organization not found');
            }

            // Check if tenant database is provisioned
            if ($tenant->database_provisioning_status !== 'provisioned') {
                return ApiResponse::error(__('Organization is not yet ready. Please try again later.'), 503);
            }

            // Switch to tenant database
            tenantManager()->switchToTenant($tenant);
        }

        // Find user by email
        $user = User::where('email', $request->email)
            ->with(['designation.department'])
            ->first();

        if (! $user) {
            return ApiResponse::notFound('User not found');
        }

        // Check password
        if (! (new BcryptHasher)->check($request->password, $user->password)) {
            return ApiResponse::unauthorized('Email or password is incorrect');
        }

        // Get user role
        $role = $user->roles()->first();

        if (! $role) {
            return ApiResponse::forbidden('You do not have permission to access this resource');
        }

        if (! $role->is_mobile_app_access_enabled) {
            return ApiResponse::forbidden('Mobile app access is not enabled for your role');
        }

        // Check user status
        if (! in_array($user->status, [UserAccountStatus::ACTIVE, UserAccountStatus::ONBOARDING])) {
            return ApiResponse::error('Your account is not active', 403);
        }

        // Generate JWT token
        try {
            $token = JWTAuth::fromUser($user);
            if (! $token) {
                return ApiResponse::serverError('Could not generate authentication token');
            }
        } catch (JWTException $e) {
            Log::error('JWT Generation Error: '.$e->getMessage());

            return ApiResponse::serverError('Authentication token generation failed');
        }

        // Build response
        $response = [
            // User Basic Info
            'id' => $user->id,
            'firstName' => $user->first_name,
            'lastName' => $user->last_name,
            'fullName' => $user->getFullName(),
            'email' => $user->email,
            'phone' => $user->phone,
            'alternateNumber' => $user->alternate_number,
            'employeeCode' => $user->code,

            // Personal Info
            'dateOfBirth' => $user->dob?->format('Y-m-d'),
            'gender' => $user->gender,
            'profilePicture' => $user->getProfilePicture(),
            'language' => $user->language,

            // Work Details
            'dateOfJoining' => $user->date_of_joining?->format('Y-m-d'),
            'status' => $user->status->value,
            'baseSalary' => (float) $user->base_salary,
            'attendanceType' => $user->attendance_type,

            // Designation Details
            'designation' => $user->designation ? [
                'id' => $user->designation->id,
                'name' => $user->designation->name,
                'code' => $user->designation->code,
                'department' => $user->designation->department ? [
                    'id' => $user->designation->department->id,
                    'name' => $user->designation->department->name,
                ] : null,
            ] : null,

            // Role & Permissions
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'displayName' => $role->name,
                'isMobileAppAccessEnabled' => (bool) $role->is_mobile_app_access_enabled,
                'isLocationActivityTrackingEnabled' => (bool) $role->is_location_activity_tracking_enabled,
            ],

            // Authentication
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => JWTAuth::factory()->getTTL() * 60, // Convert to seconds

            // Multi-tenancy Info (SaaS mode)
            'tenantId' => $tenant?->id,
            'organization' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'code' => $tenant->subdomain,
                'logo' => $tenant->logo,
            ] : null,
            'isSaaSMode' => $isSaaSMode,
        ];

        return ApiResponse::success($response, 'Login successful');
    }
}
