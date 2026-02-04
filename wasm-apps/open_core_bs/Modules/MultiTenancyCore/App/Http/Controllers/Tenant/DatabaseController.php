<?php

namespace Modules\MultiTenancyCore\App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MultiTenancyCore\App\Models\Tenant;

class DatabaseController extends Controller
{
    /**
     * Display database information
     */
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::where('email', $user->email)->with('database')->first();
        
        if (!$tenant) {
            return redirect()->route('login')->with('error', 'Tenant record not found.');
        }
        
        // Check if database is provisioned
        $database = $tenant->database;
        $isProvisioned = $database && $database->isProvisioned();
        
        return view('multitenancycore::tenant.database.index', compact(
            'tenant',
            'database',
            'isProvisioned'
        ));
    }
}