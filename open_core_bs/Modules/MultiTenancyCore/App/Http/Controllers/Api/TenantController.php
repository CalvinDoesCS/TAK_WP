<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Get current tenant info
     */
    public function info(Request $request)
    {
        $tenant = $request->get('tenant');

        if (! $tenant) {
            return response()->json([
                'success' => false,
                'message' => __('No tenant context'),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'subdomain' => $tenant->subdomain,
                'status' => $tenant->status,
            ],
        ]);
    }
}
