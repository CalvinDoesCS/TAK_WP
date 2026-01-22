<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\Tenant;

class SupportController extends Controller
{
    /**
     * Display support page
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->first();
        
        if (!$tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }
        
        // Get support information from settings
        $supportEmail = config('app.support_email', 'support@example.com');
        $supportPhone = config('app.support_phone');
        $supportHours = config('app.support_hours', '9 AM - 5 PM EST');
        
        return view('multitenancycore::tenant.support.index', compact(
            'tenant',
            'supportEmail',
            'supportPhone',
            'supportHours'
        ));
    }
}