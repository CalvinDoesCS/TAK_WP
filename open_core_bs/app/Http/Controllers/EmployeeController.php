<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Config\Constants;
use App\Enums\CommonStatus;
use App\Enums\Gender;
use App\Enums\Status;
use App\Enums\TerminationType;
use App\Enums\UserAccountStatus;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\BankAccount;
use App\Models\Designation;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\Settings;
use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Employee\EmployeeTerminatedNotification;
use App\Notifications\Employee\ProbationConfirmedNotification;
use App\Notifications\Employee\ProbationFailedNotification;
use App\Services\AddonService\AddonService;
use App\Services\AttendanceTypeService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use OwenIt\Auditing\Models\Audit;

class EmployeeController extends Controller
{
    private AddonService $addonService;

    private AttendanceTypeService $attendanceTypeService;

    public function __construct(AddonService $addonService, AttendanceTypeService $attendanceTypeService)
    {
        $this->addonService = $addonService;
        $this->attendanceTypeService = $attendanceTypeService;
    }

    /**
     * Generate new employee code by location
     */
    public function GetNewEmployeeCodeByLocationAjax($locationId)
    {
        try {
            // Check if LocationManagement module is enabled
            if ($this->addonService->isAddonEnabled('LocationManagement')) {
                $location = \Modules\LocationManagement\App\Models\Location::findOrFail($locationId);
                $prefix = strtoupper(substr($location->name, 0, 3));
            } else {
                // Fallback to generic prefix if LocationManagement is not enabled
                $prefix = 'EMP';
            }

            $year = date('Y');

            // Find last code with this prefix and year
            $lastCode = User::where('code', 'LIKE', "{$prefix}-{$year}-%")
                ->orderBy('code', 'desc')
                ->first();

            $nextNumber = 1;
            if ($lastCode) {
                $parts = explode('-', $lastCode->code);
                $nextNumber = ((int) end($parts)) + 1;
            }

            $newCode = sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);

            return response()->json(['success' => true, 'code' => $newCode]);
        } catch (Exception $e) {
            Log::error('EmployeeController@GetNewEmployeeCodeByLocationAjax: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate employee code',
            ], 500);
        }
    }

    /**
     * Add or update employee document
     */
    public function addOrUpdateDocument(Request $request)
    {
        // Check if DocumentManagement module is enabled
        if (! $this->addonService->isAddonEnabled('DocumentManagement')) {
            return response()->json([
                'success' => false,
                'message' => 'Document Management module is not enabled',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'document_type_id' => 'required|exists:document_types,id',
            'document' => 'required|file|max:10240', // 10MB
            'title' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);
            $this->authorize('update', $user);

            // Delegate to DocumentManagement module
            $documentController = app(\Modules\DocumentManagement\App\Http\Controllers\EmployeeDocumentController::class);

            return $documentController->store($request);
        } catch (Exception $e) {
            Log::error('EmployeeController@addOrUpdateDocument: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add document',
            ], 500);
        }
    }

    /**
     * Get user documents
     */
    public function getUserDocumentsAjax($userId)
    {
        if (! $this->addonService->isAddonEnabled('DocumentManagement')) {
            return response()->json(['success' => true, 'data' => []]);
        }

        try {
            $user = User::findOrFail($userId);
            $this->authorize('view', $user);

            $documents = \Modules\DocumentManagement\App\Models\EmployeeDocument::where('user_id', $userId)
                ->with('documentType')
                ->latest()
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getUserDocumentsAjax: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch documents',
            ], 500);
        }
    }

    /**
     * Download user document
     */
    public function downloadUserDocument($userDocumentId)
    {
        if (! $this->addonService->isAddonEnabled('DocumentManagement')) {
            abort(403, 'Document Management module is not enabled');
        }

        try {
            $document = \Modules\DocumentManagement\App\Models\EmployeeDocument::findOrFail($userDocumentId);
            $this->authorize('view', $document->user);

            $filePath = storage_path('app/'.$document->file_path);

            if (! file_exists($filePath)) {
                abort(404, 'Document file not found');
            }

            return response()->download($filePath, $document->original_filename);
        } catch (Exception $e) {
            Log::error('EmployeeController@downloadUserDocument: '.$e->getMessage());
            abort(500, 'Failed to download document');
        }
    }

    /**
     * Update emergency contact information
     */
    public function updateEmergencyContactInfo(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:20',
            'emergency_contact_relationship' => 'required|string|max:100',
            'emergency_contact_address' => 'nullable|string',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);
            $this->authorize('update', $user);

            $user->update([
                'emergency_contact_name' => $validated['emergency_contact_name'],
                'emergency_contact_phone' => $validated['emergency_contact_phone'],
                'emergency_contact_relationship' => $validated['emergency_contact_relationship'],
                'emergency_contact_address' => $validated['emergency_contact_address'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Emergency contact updated successfully',
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@updateEmergencyContactInfo: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update emergency contact',
            ], 500);
        }
    }

    /**
     * Search employees for dropdown/autocomplete
     */
    public function search(Request $request)
    {

        try {
            $query = $request->get('q', '');
            $page = $request->get('page', 1);
            $perPage = 20;

            $employees = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['client', 'tenant']);
            })
                ->where('status', UserAccountStatus::ACTIVE)
                ->when($query, function ($q) use ($query) {
                    $q->where(function ($subQuery) use ($query) {
                        $subQuery->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhere('name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%")
                            ->orWhere('code', 'like', "%{$query}%");
                    });
                })
                ->select('id', 'first_name', 'last_name', 'name', 'code', 'email')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->paginate($perPage, ['*'], 'page', $page);

            // Format the response with proper full names
            $formattedEmployees = $employees->getCollection()->map(function ($employee) {
                // Try to build full name from first_name and last_name
                $fullName = trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''));

                // If that's empty, try the name column
                if (empty($fullName)) {
                    $fullName = $employee->name;
                }

                // If still empty, use email or 'Unknown'
                if (empty($fullName)) {
                    $fullName = $employee->email ?: 'Unknown';
                }

                return [
                    'id' => $employee->id,
                    'name' => $fullName,
                    'code' => $employee->code ?? 'N/A',
                    'email' => $employee->email,
                ];
            });

            return response()->json([
                'data' => $formattedEmployees->toArray(),
                'has_more' => $employees->hasMorePages(),
                'total' => $employees->total(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'data' => [],
                'has_more' => false,
                'total' => 0,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function addOrUpdateBankAccount(Request $request)
    {
        $validated = $request->validate([
            'userId' => 'required|exists:users,id',
            'bankName' => 'required|string|max:255',
            'bankCode' => 'required|string|max:255',
            'accountName' => 'required|string|max:255',
            'accountNumber' => 'required|string|max:255',
            'branchName' => 'required|string|max:255',
            'branchCode' => 'required|string|max:255',
        ]);

        $user = User::find($validated['userId']);

        $bank = BankAccount::where('user_id', $user->id)
            ->first();

        if ($bank) {
            $bank->bank_name = $validated['bankName'];
            $bank->bank_code = $validated['bankCode'];
            $bank->account_name = $validated['accountName'];
            $bank->account_number = $validated['accountNumber'];
            $bank->branch_name = $validated['branchName'];
            $bank->branch_code = $validated['branchCode'];
            $bank->save();
        } else {
            $user->bankAccount()->create([
                'bank_name' => $validated['bankName'],
                'bank_code' => $validated['bankCode'],
                'account_name' => $validated['accountName'],
                'account_number' => $validated['accountNumber'],
                'branch_name' => $validated['branchName'],
                'branch_code' => $validated['branchCode'],
            ]);
        }

        return redirect()->back()->with('success', 'Bank account added/updated successfully');
    }

    public function create()
    {
        if (isSaaSMode()) {
            $tenant = tenant();
            if ($tenant && ! $tenant->canAddEmployee()) {
                $maxEmployees = $tenant->getMaxEmployees();

                return redirect()->back()->with('error', __('Employee limit exceeded. Your plan allows :max employees.', ['max' => $maxEmployees]));
            }
        }

        $shifts = Shift::where('status', Status::ACTIVE)
            ->select('id', 'name', 'code')
            ->get();

        $teams = Team::where('status', Status::ACTIVE)
            ->select('id', 'name', 'code')
            ->get();

        $designations = Designation::where('status', Status::ACTIVE)
            ->select('id', 'name', 'code')
            ->get();

        $users = User::where('status', UserAccountStatus::ACTIVE)
            ->select('id', 'first_name', 'last_name', 'code')
            ->get();

        $roles = Role::get();

        // Check module availability for attendance types
        $enabledModules = [
            'GeofenceSystem' => $this->addonService->isAddonEnabled('GeofenceSystem'),
            'IpAddressAttendance' => $this->addonService->isAddonEnabled('IpAddressAttendance'),
            'QrAttendance' => $this->addonService->isAddonEnabled('QrAttendance'),
            'DynamicQrAttendance' => $this->addonService->isAddonEnabled('DynamicQrAttendance'),
            'SiteAttendance' => $this->addonService->isAddonEnabled('SiteAttendance'),
            'FaceAttendance' => $this->addonService->isAddonEnabled('FaceAttendance'),
        ];

        return view('employees.create', [
            'shifts' => $shifts,
            'teams' => $teams,
            'designations' => $designations,
            'users' => $users,
            'roles' => $roles,
            'enabledModules' => $enabledModules,
        ]);
    }

    public function removeDevice(Request $request)
    {
        // Check if FieldManager module is enabled
        if (! $this->addonService->isAddonEnabled('FieldManager')) {
            return redirect()->back()->with('error', 'FieldManager module is not enabled');
        }

        $validated = $request->validate([
            'userId' => 'required|exists:users,id',
        ]);

        $userDeviceClass = \Modules\FieldManager\App\Models\UserDevice::class;
        $device = $userDeviceClass::where('user_id', $validated['userId'])
            ->first();

        if ($device) {
            $device->delete();
        }

        return redirect()->back()->with('success', 'Device removed successfully');
    }

    public function getReportingToUsersAjax()
    {
        $users = User::where('status', UserAccountStatus::ACTIVE)
            ->select('id', 'first_name', 'last_name', 'code')
            ->get();

        return Success::response($users);
    }

    public function updateWorkInformation(Request $request)
    {

        $validated = $request->validate([
            'id' => 'required|exists:users,id',
            'doj' => 'required|date',
            'teamId' => 'required|exists:teams,id',
            'shiftId' => 'required|exists:shifts,id',
            'designationId' => 'required|exists:designations,id',
            'role' => 'required|exists:roles,name',
            'reportingToId' => 'required|exists:users,id',
            'attendanceType' => 'required|in:open,geofence,ipAddress,staticqr,site,dynamicqr,face',
            'geofenceGroupId' => 'nullable|required_if:attendanceType,geofence|exists:geofence_groups,id',
            'ipGroupId' => 'nullable|required_if:attendanceType,ipAddress|exists:ip_address_groups,id',
            'qrGroupId' => 'nullable|required_if:attendanceType,staticqr|exists:qr_groups,id',
            'siteId' => 'nullable|required_if:attendanceType,site|exists:sites,id',
            'dynamicQrId' => 'nullable|required_if:attendanceType,dynamicqr|exists:dynamic_qr_devices,id',
        ]);

        $user = User::find($validated['id']);

        if ($user->date_of_joining != $validated['doj']) {
            $user->date_of_joining = $validated['doj'];
        }

        if ($user->team_id != $validated['teamId']) {
            $user->team_id = $validated['teamId'];
        }

        if ($user->shift_id != $validated['shiftId']) {
            $user->shift_id = $validated['shiftId'];
        }

        if ($user->designation_id != $validated['designationId']) {
            $user->designation_id = $validated['designationId'];
        }

        if ($user->reporting_to_id != $validated['reportingToId']) {
            $user->reporting_to_id = $validated['reportingToId'];
        }

        $user->save();

        // Assign attendance type using service
        $this->attendanceTypeService->assignAttendanceType(
            $user,
            $validated['attendanceType'],
            [
                'geofenceGroupId' => $validated['geofenceGroupId'] ?? null,
                'ipGroupId' => $validated['ipGroupId'] ?? null,
                'qrGroupId' => $validated['qrGroupId'] ?? null,
                'siteId' => $validated['siteId'] ?? null,
                'dynamicQrId' => $validated['dynamicQrId'] ?? null,
            ]
        );

        // Update user role
        $role = Role::where('name', $validated['role'])->first();
        $user->roles()->sync($role->id);

        return redirect()->back()->with('success', 'Work information updated successfully');
    }

    public function updateBasicInfo(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:users,id',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string',
            'dob' => 'required|date',
            'gender' => ['required', Rule::in(array_column(Gender::cases(), 'value'))],
            'bloodGroup' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'phone' => 'required|string|max:10',
            'altPhone' => 'nullable|string|max:10',
            'email' => 'required|email',
            'address' => 'nullable|string|max:255',
            'emergencyContactName' => 'nullable|string|max:100',
            'emergencyContactRelationship' => 'nullable|string|max:50',
            'emergencyContactPhone' => 'nullable|string|max:20',
            'emergencyContactAddress' => 'nullable|string|max:500',
        ]);

        $user = User::find($validated['id']);

        if ($user->first_name != $validated['firstName']) {
            $user->first_name = $validated['firstName'];
        }

        if ($user->last_name != $validated['lastName']) {
            $user->last_name = $validated['lastName'];
        }

        if ($user->dob != $validated['dob']) {
            $user->dob = $validated['dob'];
        }

        if ($user->gender != $validated['gender']) {
            $user->gender = Gender::from($validated['gender']);
        }

        if ($user->blood_group != $validated['bloodGroup']) {
            $user->blood_group = $validated['bloodGroup'];
        }

        if ($user->phone != $validated['phone']) {
            $user->phone = $validated['phone'];
        }

        if ($user->alternate_number != $validated['altPhone']) {
            $user->alternate_number = $validated['altPhone'];
        }

        if ($user->email != $validated['email']) {
            $user->email = $validated['email'];
        }

        if ($user->address != $validated['address']) {
            $user->address = $validated['address'];
        }

        if ($user->emergency_contact_name != $validated['emergencyContactName']) {
            $user->emergency_contact_name = $validated['emergencyContactName'];
        }

        if ($user->emergency_contact_relationship != $validated['emergencyContactRelationship']) {
            $user->emergency_contact_relationship = $validated['emergencyContactRelationship'];
        }

        if ($user->emergency_contact_phone != $validated['emergencyContactPhone']) {
            $user->emergency_contact_phone = $validated['emergencyContactPhone'];
        }

        if ($user->emergency_contact_address != $validated['emergencyContactAddress']) {
            $user->emergency_contact_address = $validated['emergencyContactAddress'];
        }

        $user->save();

        return redirect()->back()->with('success', 'Basic info updated successfully');
    }

    /**
     * Initiate the termination process for an employee.
     */
    public function initiateTermination(Request $request, User $user)
    {
        /*// --- Authorization & Pre-condition Check ---
        if (!Auth::user()->can('terminate employees')) { // Example permission
          return Error::response('Permission denied.', 403);
        }*/
        if ($user->status == UserAccountStatus::TERMINATED) { // Use Enum comparison
            return Error::response('Employee is already terminated.', 409);
        }

        // --- Validation ---
        $validator = Validator::make($request->all(), [
            'exitDate' => 'required|date_format:Y-m-d',
            'lastWorkingDay' => 'required|date_format:Y-m-d|after_or_equal:exitDate',
            'terminationType' => ['required', new Enum(TerminationType::class)], // Use Enum validation if created
            // 'terminationType' => 'required|string|in:resignation,terminated_with_cause,...', // Alternative if not using Enum model cast
            'exitReason' => 'required|string|max:1000',
            'isEligibleForRehire' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // --- Update User ---
        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();
            $adminUserId = Auth::id();

            $user->update([
                'status' => UserAccountStatus::TERMINATED, // Set status
                'exit_date' => $validatedData['exitDate'],
                'last_working_day' => $validatedData['lastWorkingDay'],
                'termination_type' => $validatedData['terminationType'],
                'exit_reason' => $validatedData['exitReason'],
                'is_eligible_for_rehire' => filter_var($validatedData['isEligibleForRehire'], FILTER_VALIDATE_BOOLEAN),
                'updated_by_id' => $adminUserId,
                // Maybe clear tokens, disable login? Depends on setup.
            ]);

            // Log lifecycle event
            $user->logTermination([
                'exit_date' => $validatedData['exitDate'],
                'exit_reason' => $validatedData['exitReason'],
                'last_working_day' => $validatedData['lastWorkingDay'],
                'termination_type' => $validatedData['terminationType'],
                'is_eligible_for_rehire' => filter_var($validatedData['isEligibleForRehire'], FILTER_VALIDATE_BOOLEAN),
            ]);

            // Trigger Offboarding Checklist if Recruitment module is enabled
            if ($this->addonService->isAddonEnabled('Recruitment')
                && class_exists(\Modules\Recruitment\App\Services\OffboardingService::class)) {
                try {
                    $offboardingService = app(\Modules\Recruitment\App\Services\OffboardingService::class);
                    $offboardingService->startOffboarding($user);
                    Log::info("Offboarding checklist started for User ID {$user->id}");
                } catch (Exception $e) {
                    Log::warning("Failed to start offboarding checklist for User ID {$user->id}: ".$e->getMessage());
                }
            }

            // Prepare termination data for notification
            $terminationData = [
                'exit_date' => $validatedData['exitDate'],
                'exit_reason' => $validatedData['exitReason'],
                'last_working_day' => $validatedData['lastWorkingDay'],
                'termination_type' => $validatedData['terminationType'],
            ];

            // Send notification to employee
            try {
                $user->notify(new EmployeeTerminatedNotification($user, $terminationData));

                // Notify HR team
                $hrUsers = User::role('hr')->get();
                foreach ($hrUsers as $hrUser) {
                    $hrUser->notify(new EmployeeTerminatedNotification($user, $terminationData));
                }

                // Notify manager if exists
                if ($user->reportingManager) {
                    $user->reportingManager->notify(new EmployeeTerminatedNotification($user, $terminationData));
                }
            } catch (Exception $notificationException) {
                Log::warning("Failed to send termination notification for User ID {$user->id}: ".$notificationException->getMessage());
            }

            // Log this action (using a generic activity logger or specific audit)
            Log::info("User ID {$user->id} terminated by User ID {$adminUserId}. Reason: {$validatedData['exitReason']}");

            DB::commit();

            // Return structure consistent with your Success::response wrapper if applicable
            return response()->json([
                'success' => true,
                'message' => 'Employee termination process initiated successfully.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error terminating employee ID {$user->id}: ".$e->getMessage());

            return response()->json(['success' => false, 'message' => 'An error occurred during termination.'], 500);
        }
    }

    /**
     * Start onboarding process for an employee
     */
    public function startOnboarding(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        // Update user status to onboarding
        $user->update(['status' => UserAccountStatus::ONBOARDING]);

        // If Recruitment module enabled, trigger checklist
        if ($this->addonService->isAddonEnabled('Recruitment')
            && class_exists(\Modules\Recruitment\App\Services\OnboardingService::class)) {
            try {
                $onboardingService = app(\Modules\Recruitment\App\Services\OnboardingService::class);
                $onboardingService->startChecklist($user);
                Log::info("Onboarding checklist started for User ID {$user->id}");
            } catch (Exception $e) {
                Log::warning("Failed to start onboarding checklist for User ID {$user->id}: ".$e->getMessage());
            }
        }

        return response()->json(['success' => true, 'message' => 'Onboarding started successfully']);
    }

    /**
     * Confirm the successful completion of an employee's probation.
     *
     * @param  User  $user  The employee whose probation is being confirmed (Route Model Binding)
     */
    public function confirmProbation(Request $request, User $user)
    {
        /* // --- Authorization Check ---
        // Example: Replace with your actual permission check
        if (!Auth::user()->can('manage_probation')) {
          if ($request->expectsJson()) {
              return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
          }
          return redirect()->back()->with('error', 'Permission denied.');
        }*/

        // --- Pre-condition Check ---
        // Check if user exists and is actually eligible for probation confirmation
        // (e.g., has a probation end date, isn't already confirmed, isn't terminated)
        // Using the accessor assumes it checks for null end_date and null confirmed_at
        // Add more checks if needed based on your exact logic for eligibility
        if ($user->probation_confirmed_at !== null) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Probation has already been confirmed for this employee.'], 409);
            }

            return redirect()->back()->with('error', 'Probation has already been confirmed for this employee.');
        }
        if (is_null($user->probation_end_date)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'This employee does not have a probation period defined.'], 400);
            }

            return redirect()->back()->with('error', 'This employee does not have a probation period defined.');
        }
        // Optional: Check if probation period actually ended? Or allow early confirmation?
        // if (Carbon::parse($user->probation_end_date)->isFuture()) {
        //    return response()->json(['success' => false, 'message' => 'Probation period has not ended yet.'], 400);
        // }

        // --- Validation ---
        $validator = Validator::make($request->all(), [
            'probationRemarks' => 'nullable|string|max:2000', // Optional remarks
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        // --- Update User ---
        DB::beginTransaction(); // Optional: Use transaction if other actions occur
        try {
            $adminUser = Auth::user();
            $remarks = $request->input('probationRemarks');
            $confirmationTimestamp = now();

            // Construct remarks entry
            $remarkEntry = "Probation confirmed by {$adminUser->getFullName()} on ".$confirmationTimestamp->format('Y-m-d H:i').'.';
            if (! empty($remarks)) {
                $remarkEntry .= "\nRemarks: ".$remarks;
            }

            $user->probation_confirmed_at = $confirmationTimestamp;
            // Append remarks or set them - decide on your preferred logic
            $user->probation_remarks = ($user->probation_remarks ? $user->probation_remarks."\n\n---\n\n" : '').$remarkEntry;
            // Optional: Update user status if needed, though likely already ACTIVE
            // $user->status = UserAccountStatus::ACTIVE;
            $user->save();

            // Log lifecycle event
            $user->logProbationConfirmed($remarkEntry);

            Log::info("Probation confirmed for User ID {$user->id} by Admin ID {$adminUser->id}.");

            // Send notification to employee
            try {
                $user->notify(new ProbationConfirmedNotification($user, $confirmationTimestamp->format('d-m-Y')));

                // Notify manager if exists
                if ($user->reportingManager) {
                    $user->reportingManager->notify(new ProbationConfirmedNotification($user, $confirmationTimestamp->format('d-m-Y')));
                }
            } catch (Exception $notificationException) {
                Log::warning("Failed to send probation confirmation notification for User ID {$user->id}: ".$notificationException->getMessage());
            }

            DB::commit(); // Commit transaction if used

            // Return success response
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee probation confirmed successfully.',
                ]);
            }

            return redirect()->back()->with('success', 'Employee probation confirmed successfully.');
        } catch (Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            Log::error("Error confirming probation for User ID {$user->id}: ".$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while confirming probation.',
                ], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while confirming probation.');
        }
    }

    /**
     * Extend the probation period for an employee.
     *
     * @param  User  $user  The employee whose probation is being extended
     */
    public function extendProbation(Request $request, User $user)
    {
        /* // --- Authorization Check ---
        if (!Auth::user()->can('manage_probation')) { // Example permission
          if ($request->expectsJson()) {
              return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
          }
          return redirect()->back()->with('error', 'Permission denied.');
        }*/

        // --- Pre-condition Check ---
        if ($user->probation_confirmed_at !== null) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Probation has already been confirmed.'], 409);
            }

            return redirect()->back()->with('error', 'Probation has already been confirmed.');
        }
        if (is_null($user->probation_end_date)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No probation period defined for extension.'], 400);
            }

            return redirect()->back()->with('error', 'No probation period defined for extension.');
        }
        if ($user->status !== UserAccountStatus::ACTIVE) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Employee must be active to extend probation.'], 400);
            }

            return redirect()->back()->with('error', 'Employee must be active to extend probation.');
        }

        // --- Validation ---
        $currentEndDate = Carbon::parse($user->probation_end_date);
        $validator = Validator::make($request->all(), [
            // New end date must be after the current probation end date
            'newProbationEndDate' => ['required', 'date_format:Y-m-d', 'after:'.$currentEndDate->toDateString()],
            'probationRemarks' => 'required|string|max:2000', // Reason for extension is required
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        // --- Update User ---
        DB::beginTransaction();
        try {
            $adminUser = Auth::user();
            $validatedData = $validator->validated();
            $newEndDate = $validatedData['newProbationEndDate'];
            $reason = $validatedData['probationRemarks'];
            $extensionTimestamp = now();

            // Construct remark entry for extension
            $remarkEntry = "Probation extended by {$adminUser->getFullName()} on ".$extensionTimestamp->format('Y-m-d H:i')." to {$newEndDate}.";
            $remarkEntry .= "\nReason: ".$reason;

            $user->probation_end_date = $newEndDate;
            $user->is_probation_extended = true; // Mark as extended
            $user->probation_remarks = ($user->probation_remarks ? $user->probation_remarks."\n\n---\n\n" : '').$remarkEntry;
            // Ensure confirmation date is null if extending
            $user->probation_confirmed_at = null;
            $user->save();

            // Log lifecycle event
            $oldEndDate = $currentEndDate->toDateString();
            $extensionMonths = $currentEndDate->diffInMonths(Carbon::parse($newEndDate));
            $user->logProbationExtended($extensionMonths, $reason);

            Log::info("Probation extended for User ID {$user->id} to {$newEndDate} by Admin ID {$adminUser->id}.");

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee probation extended successfully.',
                ]);
            }

            return redirect()->back()->with('success', 'Employee probation extended successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error extending probation for User ID {$user->id}: ".$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'An error occurred while extending probation.'], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while extending probation.');
        }
    }

    /**
     * Fail the probation period for an employee, initiating termination.
     *
     * @param  User  $user  The employee failing probation
     */
    public function failProbation(Request $request, User $user)
    {
        // --- Authorization Check ---
        // Failing probation often leads to termination, might require termination permission
        /* if (!Auth::user()->can('manage_probation') || !Auth::user()->can('terminate_employees')) {
          if ($request->expectsJson()) {
              return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
          }
          return redirect()->back()->with('error', 'Permission denied.');
        }*/

        // --- Pre-condition Check ---
        if ($user->probation_confirmed_at !== null) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Probation has already been confirmed.'], 409);
            }

            return redirect()->back()->with('error', 'Probation has already been confirmed.');
        }
        if ($user->status !== UserAccountStatus::ACTIVE) { // Must be active to fail probation (not already terminated etc.)
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Employee is not currently active.'], 400);
            }

            return redirect()->back()->with('error', 'Employee is not currently active.');
        }
        if (is_null($user->probation_end_date)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No probation period defined to fail.'], 400);
            }

            return redirect()->back()->with('error', 'No probation period defined to fail.');
        }

        // --- Validation ---
        $validator = Validator::make($request->all(), [
            'probationRemarks' => 'required|string|max:2000', // Reason for failure is required
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        // --- Update User (Terminate due to Probation Failure) ---
        DB::beginTransaction();
        try {
            $adminUser = Auth::user();
            $validatedData = $validator->validated();
            $reason = $validatedData['probationRemarks'];
            $terminationTimestamp = now();

            // Construct remark entry for failure
            $remarkEntry = "Probation failed by {$adminUser->getFullName()} on ".$terminationTimestamp->format('Y-m-d H:i').'.';
            $remarkEntry .= "\nReason: ".$reason;

            // Update user record to reflect termination due to probation failure
            $user->status = UserAccountStatus::TERMINATED; // Or UserAccountStatus::PROBATION_FAILED if using specific status
            $user->exit_date = $terminationTimestamp->toDateString();
            $user->last_working_day = $terminationTimestamp->toDateString(); // Or set differently if needed
            $user->termination_type = TerminationType::PROBATION_FAILED->value; // Use Enum
            $user->exit_reason = 'Probation Failed: '.$reason;
            $user->is_eligible_for_rehire = false; // Typically not eligible after probation failure
            $user->probation_remarks = ($user->probation_remarks ? $user->probation_remarks."\n\n---\n\n" : '').$remarkEntry;
            // Ensure confirmation date is null
            $user->probation_confirmed_at = null;
            $user->updated_by_id = $adminUser->id;
            $user->save();

            // Log lifecycle event
            $user->logProbationFailed($reason);

            Log::info("Probation failed for User ID {$user->id}. Terminated by Admin ID {$adminUser->id}. Reason: {$reason}");

            // Send notification to employee
            try {
                $user->notify(new ProbationFailedNotification(
                    $user,
                    $reason,
                    $terminationTimestamp->format('d-m-Y')
                ));

                // Notify HR team
                $hrUsers = User::role('hr')->get();
                foreach ($hrUsers as $hrUser) {
                    $hrUser->notify(new ProbationFailedNotification(
                        $user,
                        $reason,
                        $terminationTimestamp->format('d-m-Y')
                    ));
                }
            } catch (Exception $notificationException) {
                Log::warning("Failed to send probation failure notification for User ID {$user->id}: ".$notificationException->getMessage());
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Employee probation failed and termination process initiated.',
                ]);
            }

            return redirect()->back()->with('success', 'Employee probation failed and termination process initiated.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error failing probation for User ID {$user->id}: ".$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'An error occurred while failing probation.'], 500);
            }

            return redirect()->back()->with('error', 'An error occurred while failing probation.');
        }
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        // Exclude tenant and client roles from employee counts
        $active = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['client', 'tenant']);
        })->where('status', UserAccountStatus::ACTIVE)->count();

        $inactive = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['client', 'tenant']);
        })->where('status', UserAccountStatus::INACTIVE)->count();

        $relieved = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['client', 'tenant']);
        })->where('status', UserAccountStatus::RELIEVED)->count();

        $roles = Role::select('id', 'name')
            ->get();

        $teams = Team::where('status', Status::ACTIVE)
            ->select('id', 'name', 'code')
            ->get();

        $designations = Designation::where('status', Status::ACTIVE)
            ->select('id', 'name', 'code')
            ->get();

        // Determine page title based on status filter
        $pageTitle = __('Employees');
        if ($status) {
            $pageTitle = match ($status) {
                'onboarding' => __('Onboarding Employees'),
                'probation' => __('Employees on Probation'),
                'inactive' => __('Inactive Employees'),
                'terminated' => __('Terminated Employees'),
                default => __('Employees'),
            };
        }

        return view('employees.index', [
            'totalUser' => $active + $inactive + $relieved,
            'active' => $active,
            'inactive' => $inactive,
            'relieved' => $relieved,
            'roles' => $roles,
            'teams' => $teams,
            'designations' => $designations,
            'statusFilter' => $status,
            'pageTitle' => $pageTitle,
        ]);
    }

    public function changeEmployeeProfilePicture(Request $request)
    {
        $rules = [
            'userId' => 'required|exists:users,id',
            'file' => 'required|image|mimes:jpeg,png,jpg|max:5096',
        ];

        $validatedData = $request->validate($rules);

        try {
            $user = User::find($request->input('userId'));

            if (! $user) {
                return Error::response('User not found');
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = $user->code.'_'.time().'.'.$file->getClientOriginalExtension();

                // Delete Old File
                $oldProfilePicture = $user->profile_picture;
                if (! is_null($oldProfilePicture)) {
                    $oldProfilePicturePath = Storage::disk('public')->path(Constants::BaseFolderEmployeeProfileWithSlash.$oldProfilePicture);
                    if (file_exists($oldProfilePicturePath)) {
                        Storage::delete($oldProfilePicturePath);
                    }
                }

                // Create Directory if not exists
                if (! Storage::disk('public')->exists(Constants::BaseFolderEmployeeProfile)) {
                    Storage::disk('public')->makeDirectory(Constants::BaseFolderEmployeeProfile);
                }

                Storage::disk('public')->putFileAs(Constants::BaseFolderEmployeeProfileWithSlash, $file, $fileName);

                $user->profile_picture = $fileName;
                $user->save();
            }

            return redirect()->back()->with('success', 'Profile picture updated successfully');
        } catch (Exception $e) {
            Log::error('EmployeeController@changeEmployeeProfilePicture: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to update profile picture');
        }
    }

    public function userListAjax(Request $request)
    {
        try {
            $columns = [
                1 => 'id',
                2 => 'first_name',
                3 => 'email',
                4 => 'email_verified_at',
                5 => 'status',
                6 => 'code',
                7 => 'phone',
                8 => 'role',
                9 => 'profile_picture',
            ];

            $search = [];

            // Exclude tenant and client roles from count
            $totalData = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['client', 'tenant']);
            })->count();

            $totalFiltered = $totalData;

            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            // Exclude tenant and client roles from the query
            $query = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['client', 'tenant']);
            });

            // Status filter (from URL parameter)
            if ($request->has('statusFilter') && ! empty($request->input('statusFilter'))) {
                $statusFilter = $request->input('statusFilter');

                switch ($statusFilter) {
                    case 'onboarding':
                        $query->where('status', UserAccountStatus::ONBOARDING);
                        break;

                    case 'probation':
                        // Employees on probation: active status, has probation_end_date, not yet confirmed
                        $query->where('status', UserAccountStatus::ACTIVE)
                            ->whereNotNull('probation_end_date')
                            ->whereNull('probation_confirmed_at');
                        break;

                    case 'inactive':
                        $query->where('status', UserAccountStatus::INACTIVE);
                        break;

                    case 'terminated':
                        $query->where('status', UserAccountStatus::TERMINATED);
                        break;
                }
            }

            if ($request->has('roleFilter') && ! empty($request->input('roleFilter'))) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->input('roleFilter'));
                });
            }

            if ($request->has('teamFilter') && ! empty($request->input('teamFilter'))) {
                $query->where('team_id', $request->input('teamFilter'));
            }

            if ($request->has('designationFilter') && ! empty($request->input('designationFilter'))) {
                $query->where('designation_id', $request->input('designationFilter'));
            }

            if (empty($request->input('search.value'))) {
                $users = $query->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
            } else {
                $search = $request->input('search.value');

                $users = $query->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();

                $totalFiltered = $query->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->count();
            }

            $data = [];

            $baseUrlProfilePicture = asset('storage/'.Constants::BaseFolderEmployeeProfile);
            if (! empty($users)) {
                foreach ($users as $user) {
                    $nestedData['id'] = $user->id;
                    $nestedData['name'] = $user->getFullName();
                    $nestedData['attendance_type'] = $user->attendance_type;
                    $nestedData['team'] = $user->team->name ?? null;
                    $nestedData['email'] = $user->email;
                    $nestedData['email_verified_at'] = $user->email_verified_at;
                    $nestedData['status'] = $user->status;
                    $nestedData['code'] = $user->code;
                    $nestedData['phone'] = $user->phone;
                    $nestedData['role'] = $user->roles()->first() != null ? $user->roles()->first()->name : null;
                    $nestedData['profile_picture'] = $user->profile_picture != null ? $baseUrlProfilePicture.'/'.$user->profile_picture : null;

                    $data[] = $nestedData;
                }
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'code' => 200,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@userListAjax: '.$e->getMessage());

            return Error::response($e->getMessage());
        }
    }

    public function deleteEmployeeAjax($id)
    {
        if (config('app.demo')) {
            return Error::response('This feature is disabled in the demo.');
        }

        try {
            $user = User::find($id);

            if (! $user) {
                return Error::response('User not found');
            }

            $user->delete();

            return Success::response('User deleted successfully');
        } catch (Exception $e) {
            Log::error('EmployeeController@deleteEmployeeAjax: '.$e->getMessage());

            return Error::response('Failed to delete user');
        }
    }

    public function show($id)
    {
        validator(['id' => $id], ['id' => 'required|exists:users,id'])->validate();

        $query = User::where('id', $id)
            ->with('team')
            ->with('userAvailableLeaves')
            ->with('shift')
            ->with('designation')
            ->with('bankAccount')
            ->with('createdBy')
            ->with('reportingTo');

        // Conditionally load userDevice relationship only if FieldManager module is enabled
        if ($this->addonService->isAddonEnabled('FieldManager')) {
            $query->with('userDevice');
        }

        $user = $query->first();

        // Load document types only if DocumentManagement module is enabled
        $documentTypes = collect();
        if ($this->addonService->isAddonEnabled('DocumentManagement')) {
            $documentTypeClass = \Modules\DocumentManagement\App\Models\DocumentType::class;
            $documentTypes = $documentTypeClass::where('status', CommonStatus::ACTIVE)
                ->get();
        }

        $leaveTypes = LeaveType::where('status', Status::ACTIVE)
            ->select('id', 'name', 'code')
            ->get();

        // Calculate probation days remaining if employee is under probation
        $probationDaysRemaining = null;
        if ($user->isUnderProbation() && $user->probation_end_date) {
            $probationDaysRemaining = Carbon::now()->diffInDays(Carbon::parse($user->probation_end_date), false);
            // Ensure positive days remaining
            $probationDaysRemaining = max(0, ceil($probationDaysRemaining));
        }

        // Prepare overview tab data
        $stats = [
            'totalLeave' => 0,
            'attendancePercentage' => 0,
            'pendingApprovals' => 0,
            'activeWarnings' => 0,
        ];

        // Calculate total approved leave days for current year
        $stats['totalLeave'] = $user->leaveRequests()
            ->where('status', \App\Enums\LeaveRequestStatus::APPROVED)
            ->whereYear('from_date', now()->year)
            ->sum('total_days') ?? 0;

        // Calculate attendance percentage for current month
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $workingDaysInMonth = Carbon::now()->startOfMonth()->diffInWeekdays(Carbon::now()->endOfMonth()) + 1;

        $presentStatuses = ['checked_in', 'checked_out', 'half_day'];
        $presentDays = \App\Models\Attendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereIn('status', $presentStatuses)
            ->count();

        $stats['attendancePercentage'] = $workingDaysInMonth > 0
            ? round(($presentDays / $workingDaysInMonth) * 100, 1)
            : 0;

        // Get pending approvals count (leave requests + expense requests if module enabled)
        $stats['pendingApprovals'] = $user->leaveRequests()
            ->where('status', \App\Enums\LeaveRequestStatus::PENDING)
            ->count();

        if ($this->addonService->isAddonEnabled('ExpenseManagement')
            && class_exists(\Modules\ExpenseManagement\App\Models\ExpenseRequest::class)) {
            $stats['pendingApprovals'] += \Modules\ExpenseManagement\App\Models\ExpenseRequest::where('user_id', $user->id)
                ->where('status', \App\Enums\ExpenseRequestStatus::PENDING)
                ->count();
        }

        // Get active warnings count if DisciplinaryActions module is enabled
        if ($this->addonService->isAddonEnabled('DisciplinaryActions')
            && class_exists(\Modules\DisciplinaryActions\App\Models\Warning::class)) {
            $stats['activeWarnings'] = \Modules\DisciplinaryActions\App\Models\Warning::where('user_id', $user->id)
                ->active()
                ->count();
        }

        // Get employment status info
        $tenure = 'N/A';
        if ($user->date_of_joining) {
            $joiningDate = Carbon::parse($user->date_of_joining);
            $years = (int) $joiningDate->diffInYears(Carbon::now());
            $months = (int) $joiningDate->copy()->addYears($years)->diffInMonths(Carbon::now());

            if ($years > 0 && $months > 0) {
                $tenure = "{$years} ".__('years').", {$months} ".__('months');
            } elseif ($years > 0) {
                $tenure = "{$years} ".($years == 1 ? __('year') : __('years'));
            } elseif ($months > 0) {
                $tenure = "{$months} ".($months == 1 ? __('month') : __('months'));
            } else {
                $days = (int) $joiningDate->diffInDays(Carbon::now());
                $tenure = "{$days} ".($days == 1 ? __('day') : __('days'));
            }
        }

        $employmentInfo = [
            'status' => $user->status->value,
            'designation' => $user->designation?->name ?? 'N/A',
            'team' => $user->team?->name ?? 'N/A',
            'reportingTo' => $user->reportingTo?->getFullName() ?? 'N/A',
            'joiningDate' => $user->date_of_joining ? Carbon::parse($user->date_of_joining)->format('d M Y') : 'N/A',
            'tenure' => $tenure,
        ];

        // Get recent activity (last 5 lifecycle events)
        $recentActivity = $user->lifecycleEvents()
            ->orderBy('event_date', 'desc')
            ->limit(5)
            ->get();

        // Check module availability (only for features used in view)
        $enabledModules = [
            'Recruitment' => $this->addonService->isAddonEnabled('Recruitment'),
            'LocationManagement' => $this->addonService->isAddonEnabled('LocationManagement'),
            // Attendance-related modules for dropdown options
            'GeofenceSystem' => $this->addonService->isAddonEnabled('GeofenceSystem'),
            'IpAddressAttendance' => $this->addonService->isAddonEnabled('IpAddressAttendance'),
            'QrAttendance' => $this->addonService->isAddonEnabled('QrAttendance'),
            'DynamicQrAttendance' => $this->addonService->isAddonEnabled('DynamicQrAttendance'),
            'SiteAttendance' => $this->addonService->isAddonEnabled('SiteAttendance'),
            'FaceAttendance' => $this->addonService->isAddonEnabled('FaceAttendance'),
        ];

        // Check if employee has exited (relieved, retired, or terminated)
        $isExitedEmployee = ! is_null($user->relieved_at) || ! is_null($user->retired_at) || ! is_null($user->exit_date);

        return view('employees.view', [
            'user' => $user,
            'documentTypes' => $documentTypes,
            'leaveTypes' => $leaveTypes,
            'probationDaysRemaining' => $probationDaysRemaining,
            'stats' => $stats,
            'employmentInfo' => $employmentInfo,
            'recentActivity' => $recentActivity,
            'enabledModules' => $enabledModules,
            'isExitedEmployee' => $isExitedEmployee,
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        // Validation is handled automatically by StoreEmployeeRequest

        try {
            $user = new User;
            $user->first_name = $request->input('firstName');
            $user->last_name = $request->input('lastName');
            $user->gender = Gender::from($request->input('gender'));
            $user->phone = $request->input('phone');
            $user->alternate_number = $request->input('altPhone');
            $user->email = $request->input('email');
            $user->dob = $request->input('dob');
            $user->blood_group = $request->input('bloodGroup');
            $user->address = $request->input('address');
            $user->emergency_contact_name = $request->input('emergencyContactName');
            $user->emergency_contact_relationship = $request->input('emergencyContactRelationship');
            $user->emergency_contact_phone = $request->input('emergencyContactPhone');
            $user->emergency_contact_address = $request->input('emergencyContactAddress');

            if ($request->has('useDefaultPassword') && $request->input('useDefaultPassword') == 'on') {
                $user->password = bcrypt(Settings::first()->default_password ?? 12345678);
            } else {
                $user->password = bcrypt($request->input('password'));
            }

            $user->code = $request->input('code');
            $user->date_of_joining = $request->input('doj');
            $user->team_id = $request->input('teamId');
            $user->shift_id = $request->input('shiftId');
            $user->reporting_to_id = $request->input('reportingToId');
            $user->designation_id = $request->input('designationId');
            $user->status = UserAccountStatus::ACTIVE;

            if ($request->hasFile('file')) {

                $file = $request->file('file');
                $fileName = $user->code.'_'.time().'.'.$file->getClientOriginalExtension();

                // Create Directory if not exists
                if (! Storage::disk('public')->exists(Constants::BaseFolderEmployeeProfile)) {
                    Storage::disk('public')->makeDirectory(Constants::BaseFolderEmployeeProfile);
                }

                Storage::disk('public')->putFileAs(Constants::BaseFolderEmployeeProfileWithSlash, $file, $fileName);

                $user->profile_picture = $fileName;
            }

            // Handle probation period if provided
            if ($request->filled('probationPeriodMonths')) {
                $months = (int) $request->input('probationPeriodMonths');
                $probationEndDate = Carbon::parse($user->date_of_joining)->addMonths($months);
                $user->probation_end_date = $probationEndDate;

                if ($request->filled('probationRemarks')) {
                    $user->probation_remarks = $request->input('probationRemarks');
                }
            }

            $user->created_by_id = auth()->id();
            $user->save();

            // Assign attendance type using service
            $this->attendanceTypeService->assignAttendanceType(
                $user,
                $request->input('attendanceType'),
                [
                    'geofenceGroupId' => $request->input('geofenceGroupId'),
                    'ipGroupId' => $request->input('ipGroupId'),
                    'qrGroupId' => $request->input('qrGroupId'),
                    'siteId' => $request->input('siteId'),
                    'dynamicQrId' => $request->input('dynamicQrId'),
                ]
            );

            $user->assignRole($request->input('role'));

            return redirect()->route('employees.index')->with('success', 'Employee created successfully');
        } catch (Exception $e) {
            Log::error('EmployeeController@store: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to create employee');
        }
    }

    public function checkEmailValidationAjax(Request $request)
    {
        $email = $request->input('email');

        if (! $email) {
            return response()->json([
                'valid' => false,
            ]);
        }

        // Edit case handling
        if ($request->has('id')) {
            $id = $request->input('id');
            if (User::where('email', $email)->where('id', '!=', $id)->exists()) {
                return response()->json([
                    'valid' => false,
                ]);
            } else {
                return response()->json([
                    'valid' => true,
                ]);
            }
        }

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'valid' => false,
            ]);
        }

        return response()->json([
            'valid' => true,
        ]);
    }

    public function checkPhoneValidationAjax(Request $request)
    {

        $phone = $request->input('phone');

        if (! $phone) {
            return response()->json([
                'valid' => false,
            ]);
        }

        // Edit Case Handling
        if ($request->has('id')) {
            $id = $request->input('id');
            if (User::where('phone', $phone)->where('id', '!=', $id)->withTrashed()->exists()) {
                return response()->json([
                    'valid' => false,
                ]);
            } else {
                return response()->json([
                    'valid' => true,
                ]);
            }
        }

        if (User::where('phone', $phone)->withTrashed()->exists()) {
            return response()->json([
                'valid' => false,
            ]);
        }

        return response()->json([
            'valid' => true,
        ]);
    }

    public function checkEmployeeCodeValidationAjax(Request $request)
    {
        $code = $request->input('code');

        if (! $code) {
            return response()->json([
                'valid' => false,
            ]);
        }

        // Edit Case Handling
        if ($request->has('id')) {
            $id = $request->input('id');
            if (User::where('code', $code)->where('id', '!=', $id)->withTrashed()->exists()) {
                return response()->json([
                    'valid' => false,
                ]);
            } else {
                return response()->json([
                    'valid' => true,
                ]);
            }
        }

        if (User::where('code', $code)->withTrashed()->exists()) {
            return response()->json([
                'valid' => false,
            ]);
        }

        return response()->json([
            'valid' => true,
        ]);
    }

    public function getGeofenceGroups()
    {
        if (! $this->addonService->isAddonEnabled('GeofenceSystem')) {
            return response()->json([]);
        }

        $geofenceGroupClass = \Modules\GeofenceSystem\App\Models\GeofenceGroup::class;
        $geofenceGroups = $geofenceGroupClass::where('status', '=', 'active')
            ->select('id', 'name')
            ->get();

        return response()->json($geofenceGroups);
    }

    public function getIpGroups()
    {
        if (! $this->addonService->isAddonEnabled('IpAddressAttendance')) {
            return response()->json([]);
        }

        $ipGroupClass = \Modules\IpAddressAttendance\App\Models\IpAddressGroup::class;
        $ipGroups = $ipGroupClass::where('status', '=', 'active')
            ->select('id', 'name')
            ->get();

        return response()->json($ipGroups);
    }

    public function getQrGroups()
    {
        if (! $this->addonService->isAddonEnabled('QrAttendance')) {
            return response()->json([]);
        }

        $qrGroupClass = \Modules\QRAttendance\App\Models\QrGroup::class;
        $qrGroups = $qrGroupClass::where('status', '=', 'active')
            ->select('id', 'name')
            ->get();

        return response()->json($qrGroups);
    }

    public function getDynamicQrDevices()
    {
        if (! $this->addonService->isAddonEnabled('DynamicQrAttendance')) {
            return response()->json([]);
        }

        $deviceClass = \Modules\DynamicQrAttendance\App\Models\DynamicQrDevice::class;
        $devices = $deviceClass::where('user_id', null)
            ->where('site_id', null)
            ->get();

        return response()->json($devices);
    }

    public function getSites()
    {
        if (! $this->addonService->isAddonEnabled('SiteAttendance')) {
            return response()->json([]);
        }

        $siteClass = \Modules\SiteAttendance\App\Models\Site::class;
        $sites = $siteClass::where('status', '=', 'active')
            ->select('id', 'name')
            ->get();

        return response()->json($sites);
    }

    public function toggleStatus($id)
    {
        if (config('app.demo')) {
            return Error::response('This feature is disabled in the demo.');
        }

        $user = User::find($id);

        if ($user->status == UserAccountStatus::ACTIVE) {
            $user->status = UserAccountStatus::INACTIVE;
        } else {
            $user->status = UserAccountStatus::ACTIVE;
        }

        $user->save();

        return Success::response('Status updated successfully');
    }

    public function relieveEmployee($id)
    {
        if (config('app.demo')) {
            return Error::response('This feature is disabled in the demo.');
        }

        $user = User::find($id);

        if ($user) {
            $user->status = UserAccountStatus::RELIEVED;
            $user->relieved_at = now();
            $user->save();
        }

        return Success::response('Employee relieved successfully');
    }

    public function retireEmployee(Request $request, User $user)
    {
        try {
            $oldStatus = $user->status->value;
            $user->status = UserAccountStatus::RETIRED;
            $user->retired_at = now();
            $user->save();

            // Log lifecycle event
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::RETIRED,
                oldValue: ['status' => $oldStatus],
                newValue: ['status' => 'retired'],
                notes: 'Employee marked as retired'
            );

            return response()->json([
                'success' => true,
                'message' => __('Employee marked as retired successfully'),
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@retireEmployee: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to mark employee as retired'),
            ], 500);
        }
    }

    public function myProfile()
    {
        $user = User::find(auth()->user()->id);

        $auditLogs = Audit::where('user_id', auth()->user()->id)
            ->where('auditable_type', 'App\Models\User')
            ->orderBy('created_at', 'desc')
            ->get();

        $role = $user->roles()->first();

        return view('account.my-profile', [
            'user' => $user,
            'auditLogs' => $auditLogs,
            'role' => $role,
        ]);
    }

    /**
     * Self-service profile page for employees
     * Alias for myProfile() method to support hrcore.my.profile route
     */
    public function selfServiceProfile()
    {
        return $this->myProfile();
    }

    /**
     * Update self-service profile
     * Alias for updateBasicInfo() method to support hrcore.my.profile.update route
     */
    public function updateSelfProfile(Request $request)
    {
        return $this->updateBasicInfo($request);
    }

    /**
     * Update profile photo via self-service
     * Alias for changeEmployeeProfilePicture() method to support hrcore.my.profile.photo route
     */
    public function updateProfilePhoto(Request $request)
    {
        return $this->changeEmployeeProfilePicture($request);
    }

    /**
     * Change password via self-service
     * Delegates to AccountController's changePassword method
     */
    public function changePassword(Request $request)
    {
        return app(AccountController::class)->changePassword($request);
    }

    /**
     * Get employee lifecycle timeline via AJAX
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeeTimelineAjax($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            // TODO: Add authorization check later
            // if (Auth::id() !== $user->id && ! Auth::user()->can('view_employee')) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => __('Unauthorized to view this employee timeline'),
            //     ], 403);
            // }

            $timeline = $user->getLifecycleTimeline();

            return response()->json([
                'success' => true,
                'timeline' => $timeline,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeeTimelineAjax: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to retrieve employee timeline'),
            ], 500);
        }
    }

    /**
     * Get employee overview data for the overview tab
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeeOverview($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            // Calculate stats
            $stats = [
                'totalLeave' => $user->available_leave_count ?? 0,
                'attendancePercentage' => 0, // To be calculated based on actual attendance
                'pendingApprovals' => 0, // To be calculated based on module
                'activeWarnings' => 0, // To be calculated if DisciplinaryActions module is enabled
            ];

            // Calculate attendance percentage for current month if Attendance module is enabled
            if ($this->addonService->isAddonEnabled('Attendance')) {
                $currentMonth = Carbon::now()->month;
                $currentYear = Carbon::now()->year;
                // Add actual attendance calculation logic here
                $stats['attendancePercentage'] = 95; // Placeholder
            }

            // Employment status HTML
            $employmentStatus = '<ul class="list-unstyled mb-0">';
            $employmentStatus .= '<li class="mb-2"><strong>'.__('Current Status').':</strong> <span class="badge bg-label-success">'.__('Active').'</span></li>';
            $employmentStatus .= '<li class="mb-2"><strong>'.__('Employment Type').':</strong> '.__('Full Time').'</li>';
            $employmentStatus .= '<li class="mb-2"><strong>'.__('Joining Date').':</strong> '.Carbon::parse($user->date_of_joining)->format('d M Y').'</li>';

            if ($user->probation_end_date && ! $user->probation_confirmed_at) {
                $employmentStatus .= '<li class="mb-2"><strong>'.__('Probation').':</strong> '.__('In Progress').'</li>';
            } elseif ($user->probation_confirmed_at) {
                $employmentStatus .= '<li class="mb-2"><strong>'.__('Probation').':</strong> '.__('Confirmed on').' '.Carbon::parse($user->probation_confirmed_at)->format('d M Y').'</li>';
            }

            $employmentStatus .= '</ul>';

            // Recent activity HTML
            $recentActivity = '<div class="timeline">';
            $recentActivity .= '<div class="timeline-item"><span class="timeline-point timeline-point-primary"></span><div class="timeline-event"><div class="timeline-header mb-1"><h6 class="mb-0">'.__('No recent activity').'</h6></div></div></div>';
            $recentActivity .= '</div>';

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'employmentStatus' => $employmentStatus,
                    'recentActivity' => $recentActivity,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeeOverview: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to retrieve employee overview'),
            ], 500);
        }
    }

    /**
     * Get employee documents tab content
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeeDocuments($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $html = '<p class="text-muted">'.__('Documents functionality will be loaded here.').'</p>';

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeeDocuments: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('Failed to load documents')], 500);
        }
    }

    /**
     * Get employee attendance tab content
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeeAttendance($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $html = '<p class="text-muted">'.__('Attendance records will be loaded here.').'</p>';

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeeAttendance: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('Failed to load attendance data')], 500);
        }
    }

    /**
     * Get employee leave tab content
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeeLeave($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $html = '<p class="text-muted">'.__('Leave balance and history will be loaded here.').'</p>';

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeeLeave: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('Failed to load leave data')], 500);
        }
    }

    /**
     * Get employee performance tab content
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeePerformance($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $html = '<p class="text-muted">'.__('Performance reviews will be loaded here.').'</p>';

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeePerformance: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('Failed to load performance data')], 500);
        }
    }

    /**
     * Get employee assets tab content
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeeAssets($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $html = '<p class="text-muted">'.__('Assigned assets will be loaded here.').'</p>';

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeeAssets: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('Failed to load assets data')], 500);
        }
    }

    /**
     * Get employee loans tab content
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeeLoans($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $html = '<p class="text-muted">'.__('Loan history will be loaded here.').'</p>';

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeeLoans: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('Failed to load loans data')], 500);
        }
    }

    /**
     * Get employee disciplinary tab content
     *
     * @param  int  $userId  User ID
     */
    public function getEmployeeDisciplinary($userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $html = '<p class="text-muted">'.__('Disciplinary records will be loaded here.').'</p>';

            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@getEmployeeDisciplinary: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('Failed to load disciplinary data')], 500);
        }
    }

    /**
     * Suspend employee
     */
    public function suspendEmployee(Request $request, User $user)
    {
        $validated = $request->validate([
            'suspensionDate' => 'required|date',
            'suspensionDuration' => 'nullable|integer|min:1',
            'suspensionReason' => 'required|string|max:1000',
            'notifyEmployee' => 'nullable|in:on,1,true',
        ]);

        try {
            $user->status = UserAccountStatus::SUSPENDED;
            $user->suspension_date = $validated['suspensionDate'];
            $user->suspension_reason = $validated['suspensionReason'];
            $user->suspension_duration_days = $validated['suspensionDuration'] ?? null;
            $user->save();

            // Log lifecycle event
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::SUSPENDED,
                oldValue: ['status' => 'active'],
                newValue: ['status' => 'suspended'],
                metadata: [
                    'suspension_date' => $validated['suspensionDate'],
                    'duration_days' => $validated['suspensionDuration'] ?? null,
                ],
                notes: 'Employee suspended: '.$validated['suspensionReason']
            );

            return response()->json([
                'success' => true,
                'message' => __('Employee suspended successfully'),
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@suspendEmployee: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to suspend employee'),
            ], 500);
        }
    }

    /**
     * Reactivate employee
     */
    public function reactivateEmployee(Request $request, User $user)
    {
        try {
            $oldStatus = $user->status->value;
            $user->status = UserAccountStatus::ACTIVE;
            $user->suspension_date = null;
            $user->suspension_reason = null;
            $user->suspension_duration_days = null;
            $user->save();

            // Log lifecycle event
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::ACTIVATED,
                oldValue: ['status' => $oldStatus],
                newValue: ['status' => 'active'],
                notes: 'Employee reactivated'
            );

            return response()->json([
                'success' => true,
                'message' => __('Employee reactivated successfully'),
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@reactivateEmployee: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to reactivate employee'),
            ], 500);
        }
    }

    /**
     * Mark terminated employee as relieved
     */
    public function markAsRelieved(Request $request, User $user)
    {
        try {
            if ($user->status !== UserAccountStatus::TERMINATED) {
                return response()->json([
                    'success' => false,
                    'message' => __('Only terminated employees can be marked as relieved'),
                ], 400);
            }

            $user->status = UserAccountStatus::RELIEVED;
            $user->save();

            // Log lifecycle event
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::RELIEVED,
                oldValue: ['status' => 'terminated'],
                newValue: ['status' => 'relieved'],
                notes: 'Employee marked as relieved'
            );

            return response()->json([
                'success' => true,
                'message' => __('Employee marked as relieved successfully'),
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@markAsRelieved: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to mark employee as relieved'),
            ], 500);
        }
    }

    /**
     * Mark employee as inactive
     */
    public function markAsInactive(Request $request, User $user)
    {
        try {
            if ($user->status !== UserAccountStatus::ACTIVE) {
                return response()->json([
                    'success' => false,
                    'message' => __('Only active employees can be marked as inactive'),
                ], 400);
            }

            $oldStatus = $user->status->value;
            $user->status = UserAccountStatus::INACTIVE;
            $user->save();

            // Log lifecycle event
            $user->logLifecycleEvent(
                \App\Enums\LifecycleEventType::DEACTIVATED,
                oldValue: ['status' => $oldStatus],
                newValue: ['status' => 'inactive'],
                notes: 'Employee marked as inactive'
            );

            return response()->json([
                'success' => true,
                'message' => __('Employee marked as inactive successfully'),
            ]);
        } catch (Exception $e) {
            Log::error('EmployeeController@markAsInactive: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to mark employee as inactive'),
            ], 500);
        }
    }

    /**
     * Tab AJAX methods - returning tab content
     */
    public function overviewTab(User $user)
    {
        // Calculate quick stats
        $stats = [
            'totalLeave' => 0,
            'attendancePercentage' => 0,
            'pendingApprovals' => 0,
            'activeWarnings' => 0,
        ];

        // Calculate total approved leave days for current year
        $stats['totalLeave'] = $user->leaveRequests()
            ->where('status', \App\Enums\LeaveRequestStatus::APPROVED)
            ->whereYear('from_date', now()->year)
            ->sum('total_days') ?? 0;

        // Calculate attendance percentage for current month
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $workingDaysInMonth = Carbon::now()->startOfMonth()->diffInWeekdays(Carbon::now()->endOfMonth()) + 1;

        $presentStatuses = ['checked_in', 'checked_out', 'half_day'];
        $presentDays = \App\Models\Attendance::where('user_id', $user->id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->whereIn('status', $presentStatuses)
            ->count();

        $stats['attendancePercentage'] = $workingDaysInMonth > 0
            ? round(($presentDays / $workingDaysInMonth) * 100, 1)
            : 0;

        // Get pending approvals count (leave requests + expense requests if module enabled)
        $stats['pendingApprovals'] = $user->leaveRequests()
            ->where('status', \App\Enums\LeaveRequestStatus::PENDING)
            ->count();

        if ($this->addonService->isAddonEnabled('ExpenseManagement')
            && class_exists(\Modules\ExpenseManagement\App\Models\ExpenseRequest::class)) {
            $stats['pendingApprovals'] += \Modules\ExpenseManagement\App\Models\ExpenseRequest::where('user_id', $user->id)
                ->where('status', \App\Enums\ExpenseRequestStatus::PENDING)
                ->count();
        }

        // Get active warnings count if DisciplinaryActions module is enabled
        if ($this->addonService->isAddonEnabled('DisciplinaryActions')
            && class_exists(\Modules\DisciplinaryActions\App\Models\Warning::class)) {
            $stats['activeWarnings'] = \Modules\DisciplinaryActions\App\Models\Warning::where('user_id', $user->id)
                ->active()
                ->count();
        }

        // Get employment status info
        $tenure = 'N/A';
        if ($user->date_of_joining) {
            $joiningDate = Carbon::parse($user->date_of_joining);
            $years = (int) $joiningDate->diffInYears(Carbon::now());
            $months = (int) $joiningDate->copy()->addYears($years)->diffInMonths(Carbon::now());

            if ($years > 0 && $months > 0) {
                $tenure = "{$years} ".__('years').", {$months} ".__('months');
            } elseif ($years > 0) {
                $tenure = "{$years} ".($years == 1 ? __('year') : __('years'));
            } elseif ($months > 0) {
                $tenure = "{$months} ".($months == 1 ? __('month') : __('months'));
            } else {
                $days = (int) $joiningDate->diffInDays(Carbon::now());
                $tenure = "{$days} ".($days == 1 ? __('day') : __('days'));
            }
        }

        $employmentInfo = [
            'status' => $user->status->value,
            'designation' => $user->designation?->name ?? 'N/A',
            'team' => $user->team?->name ?? 'N/A',
            'reportingTo' => $user->reportingTo?->getFullName() ?? 'N/A',
            'joiningDate' => $user->date_of_joining ? Carbon::parse($user->date_of_joining)->format('d M Y') : 'N/A',
            'tenure' => $tenure,
        ];

        // Get recent activity (last 5 lifecycle events)
        $recentActivity = $user->lifecycleEvents()
            ->orderBy('event_date', 'desc')
            ->limit(5)
            ->get();

        return view('employees.tabs.overview', compact('user', 'stats', 'employmentInfo', 'recentActivity'))->render();
    }

    public function documentsTab(User $user)
    {
        if ($this->addonService->isAddonEnabled('DocumentManagement')) {
            $documentTypes = \Modules\DocumentManagement\App\Models\DocumentType::where('status', \App\Enums\CommonStatus::ACTIVE)->get();
        } else {
            $documentTypes = collect();
        }

        return response()->json([
            'success' => true,
            'html' => view('employees.tabs.documents', compact('user', 'documentTypes'))->render(),
        ]);
    }

    public function attendanceTab(User $user)
    {
        // Load attendance data for the last 30 days
        $attendanceLogs = $user->attendances()
            ->with('shift')
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'html' => view('employees.tabs.attendance', compact('user', 'attendanceLogs'))->render(),
        ]);
    }

    public function leaveTab(User $user)
    {
        // LeaveType is part of core app, not a module
        $leaveTypes = LeaveType::where('status', \App\Enums\Status::ACTIVE)->get();

        return response()->json([
            'success' => true,
            'html' => view('employees.tabs.leave', compact('user', 'leaveTypes'))->render(),
        ]);
    }

    /**
     * Load employee lifecycle timeline tab via AJAX
     */
    public function timelineTab(User $user)
    {
        // Get all lifecycle events for the user
        $events = $user->lifecycleEvents()
            ->orderBy('event_date', 'desc')
            ->get()
            ->map(function ($event) {
                return $event->toTimelineFormat();
            });

        return response()->json([
            'success' => true,
            'timeline' => $events,
        ]);
    }
}
