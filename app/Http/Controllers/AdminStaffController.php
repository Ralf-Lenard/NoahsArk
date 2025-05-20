<?php

namespace App\Http\Controllers;

use App\Models\AnimalProfile;
use Illuminate\Http\Request;
use App\Models\AnimalAbuseReport;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class AdminStaffController extends Controller
{

    public function index(Request $request)
    {
        $year = $request->input('year', now()->year); // Default: current year

        $totalAnimals = AnimalProfile::count();
        $adoptedAnimals = AnimalProfile::where('is_adopted', true)->count();
        $pendingReports = AnimalAbuseReport::where('status', 'pending')->count();

        // Animal species distribution
        $speciesData = AnimalProfile::select('species')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('species')
            ->get();

        // Adoptions per month for selected year
        $adoptionsPerMonth = DB::table('animal_profiles')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('is_adopted', true)
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Abuse reports per month for selected year
        $reportsPerMonth = DB::table('animal_abuse_reports')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Unique years for dropdown filter
        $years = AnimalProfile::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return Inertia::render('Admin/Dashboard', [
            'year' => $year,
            'availableYears' => $years,
            'totalAnimals' => $totalAnimals,
            'adoptedAnimals' => $adoptedAnimals,
            'pendingReports' => $pendingReports,
            'speciesChart' => [
                'labels' => $speciesData->pluck('species'),
                'data' => $speciesData->pluck('count'),
            ],
            'adoptionChart' => [
                'labels' => $adoptionsPerMonth->pluck('month'),
                'data' => $adoptionsPerMonth->pluck('count'),
            ],
            'reportChart' => [
                'labels' => $reportsPerMonth->pluck('month'),
                'data' => $reportsPerMonth->pluck('count'),
            ],
        ]);
    }
}
