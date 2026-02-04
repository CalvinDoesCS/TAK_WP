<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\Tenant;

class OrganizationController extends Controller
{
    /**
     * Look up organization by code/subdomain
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'organization_code' => 'required|string',
        ]);

        $tenant = Tenant::where('subdomain', strtolower($request->organization_code))
            ->where('status', 'active')
            ->first();

        if (! $tenant) {
            return response()->json([
                'success' => false,
                'message' => __('Organization not found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'logo' => $tenant->logo_url ?? null,
            ],
        ]);
    }
}
