<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\MultiTenancyCore\App\Models\Tenant;

class ProfileController extends Controller
{
    /**
     * Display the company profile
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }

        return view('multitenancycore::tenant.profile.index', compact(
            'tenant'
        ));
    }

    /**
     * Update company profile
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();

        if (! $tenant) {
            return Error::response('Tenant not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ]);
        }

        try {
            $tenant->update($request->only([
                'name', 'phone', 'address', 'city', 'state',
                'country', 'postal_code', 'website', 'tax_id',
            ]));

            return Success::response([
                'message' => 'Company profile updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update company profile: '.$e->getMessage());

            return Error::response('Failed to update profile');
        }
    }
}
