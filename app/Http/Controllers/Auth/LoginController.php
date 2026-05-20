<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginController extends Controller
{


      public function showLoginForm()
    {
        $email = Cookie::get('remember_email');
        $passwordValue = Cookie::get('remember_password');
        $password = $passwordValue ? Crypt::decryptString($passwordValue) : null;
        $remember = Cookie::get('remember_checked') === 'true';

        return view('auth.login', compact('email', 'password', 'remember'));
    }

    /**
     * Handle an authentication attempt.
     */
    public function loginDashboard(Request $request)
    {
        $request->validate([
            'email' => 'required|email|string',
            'password' => 'required|string',
        ]);

        $remember = $request->has('remember');
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user && Schema::hasColumn('users', 'last_login')) {
                $user->last_login = now();
                $user->saveQuietly();
            }

            if ($remember) {
                Cookie::queue('remember_email', $request->email, 60 * 24 * 30);
                Cookie::queue('remember_password', Crypt::encryptString($request->password), 60 * 24 * 30);
                Cookie::queue('remember_checked', 'true', 60 * 24 * 30);
            } else {
                Cookie::queue(Cookie::forget('remember_email'));
                Cookie::queue(Cookie::forget('remember_password'));
                Cookie::queue(Cookie::forget('remember_checked'));
            }

            if ($user->role == 1) {
                return redirect()->route('site.summary');
            } elseif ($user->role == 2) {
                return redirect()->route('dashboard');
            } elseif ($user->role == 3) {
                // This line correctly redirects role 3 users to the 'dashboard' route.
                // The logic for displaying site 2 data for role 3 is handled in DashboardController.
                return redirect()->route('dashboard');
            }elseif ($user->role == 4) {
                 return back()->with('error', 'You are not authorized to login.')
                     ->withInput($request->only('email', 'remember'));
            }
            // Fallback redirection if a role is not explicitly handled above
            return redirect()->route('dashboard');
        }

        return back()->with('error', 'The provided credentials do not match our records.')
                     ->withInput($request->only('email', 'remember'));
    }


public function showSiteSummaryPage()
{
    $user = Auth::user();
    if ($user->role != 1) {
        return redirect()->route('dashboard')->with('error', 'You are not authorized to view this page.');
    }

    // --- Overall Stats ---
    $totalSites = DB::table('master_sites')->count(); // Count all sites
    $totalAreaOverall = DB::table('master_land')->sum('area_ha');
    $totalProductionOverall = DB::table('harvesting_update')
        ->whereYear('created_at', now()->year)
        ->sum(DB::raw('CAST(COALESCE(yield_mt, 0) AS DECIMAL(10,2))'));
    $totalMachinesOverall = DB::table('master_machine')->count();

    $stats = [
        'total_sites' => $totalSites,
        'total_area' => $totalAreaOverall,
        'total_production' => $totalProductionOverall,
        'total_machines' => $totalMachinesOverall,
    ];

    // --- Site-wise Data ---
    $sites_data_query = DB::table('master_sites as ms')
        ->leftJoin('users as u', 'ms.user_id', '=', 'u.id') // ✅ Join with users table for incharge name
        ->leftJoin('master_land as ml', 'ms.id', '=', 'ml.site_id') // Still left join to include all sites
        ->select(
            'ms.id as site_id', // Include site ID for link generation
            'ms.site_name',
            DB::raw('COALESCE(u.name, "-") as unit_incharge'), // ✅ Get incharge name from users
            DB::raw('COALESCE(SUM(ml.area_ha), 0) as area_acre'),
            DB::raw('(SELECT GROUP_CONCAT(DISTINCT mf.fertilizer_type SEPARATOR ", ")
                        FROM fertilizer_soil_record fsr
                        JOIN master_fertilizer mf ON fsr.fertilizer_id = mf.id
                        WHERE fsr.site_id = ms.id) as fertilizer_used'),
            DB::raw('MAX(YEAR(ml.created_at)) as year'),
            DB::raw('(SELECT SUM(CAST(COALESCE(hu.yield_mt, 0) AS DECIMAL(10,2)))
                        FROM harvesting_update hu
                        WHERE hu.site_id = ms.id
                        AND YEAR(hu.created_at) = YEAR(CURDATE())) as crop_production_mt'),
            DB::raw('(SELECT SUM(mf.stock_kg)
                        FROM master_fertilizer mf
                        WHERE mf.site_id = ms.id) as fertilizer_stock_kg'),
            DB::raw('(SELECT SUM(msd.seed_stock_kg)
                        FROM master_seed msd
                        WHERE msd.site_id = ms.id) as seed_stock_kg'),
            DB::raw('(SELECT COUNT(DISTINCT mm.id)
                        FROM master_machine mm
                        WHERE mm.site_id = ms.id) as machines_count'),
            DB::raw('(SELECT COUNT(DISTINCT mt.id)
                        FROM master_tractors mt
                        WHERE mt.site_id = ms.id) as tractors_count')
        )
        ->groupBy('ms.id', 'ms.site_name', 'u.name')
        ->orderBy('ms.site_name');

    $sites_data = $sites_data_query->get()->map(function ($item) {
        return [
            'site_id' => $item->site_id, // Include site ID for link generation
            'site_name' => $item->site_name,
            'unit_incharge' => $item->unit_incharge ?? '-',
            'area_acre' => $item->area_acre ?? 0,
            'fertilizer_used' => $item->fertilizer_used ?? '-',
            'year' => $item->year ?? date('Y'),
            'crop_production_mt' => $item->crop_production_mt ?? 0,
            'fertilizer_stock_kg' => $item->fertilizer_stock_kg ?? 0,
            'seed_stock_kg' => $item->seed_stock_kg ?? 0,
            'machines_count' => $item->machines_count ?? 0,
            'tractors_count' => $item->tractors_count ?? 0,
        ];
    })->all();

    // --- Fallback (for testing without DB data) ---
    if (empty($sites_data)) {
        $sites_data = [
            ['site_name' => 'Lucknow', 'unit_incharge' => 'Pawan Sir', 'area_acre' => 2345, 'fertilizer_used' => 'Urea, DAP', 'year' => 2024, 'crop_production_mt' => 5245, 'fertilizer_stock_kg' => 654, 'seed_stock_kg' => 455, 'machines_count' => 33, 'tractors_count' => 13],
            ['site_name' => 'Kanpur', 'unit_incharge' => 'Mr. B', 'area_acre' => 4345, 'fertilizer_used' => 'Potash', 'year' => 2024, 'crop_production_mt' => 5424, 'fertilizer_stock_kg' => 987, 'seed_stock_kg' => 654, 'machines_count' => 54, 'tractors_count' => 15],
        ];

        $stats['total_sites'] = count($sites_data);
        $stats['total_area'] = array_sum(array_column($sites_data, 'area_acre'));
        $stats['total_production'] = array_sum(array_column($sites_data, 'crop_production_mt'));
        $stats['total_machines'] = array_sum(array_column($sites_data, 'machines_count'));
    }

    return view('admin.site_summary', compact('stats', 'sites_data'));
}




    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Cookie::queue(Cookie::forget('remember_email'));
        Cookie::queue(Cookie::forget('remember_password'));
        Cookie::queue(Cookie::forget('remember_checked'));

        return redirect()->route('login.form');
    }
    
    public function welcomepage()
        {
            return view('welcomepage');
        }
        
        public function SmartDairy()
        {
            return view('SmartDairy');
        }
}
