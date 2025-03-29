<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\Performance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PerformanceController extends Controller
{
    

    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $currentMonth = Carbon::now()->format('Y-m');
        $cacheKey = "performance_{$user->username}_{$currentMonth}";

        // Retrieve performance data from cache or query the database if not cached
        $monthlyPerformance = Cache::remember($cacheKey, 300, function () use ($user, $currentMonth) {
            return Performance::where('NIP', $user->username)
                ->where('tglKinerja', 'like', "$currentMonth%")
                ->orderBy('tglKinerja', 'asc') // Sort by performance date
                ->get(); // Retrieve all fields allowed in the model's $fillable
        });

        return response()->json([
            'current_month' => [
                'month' => $currentMonth,
                'performances' => $monthlyPerformance,
            ],
        ]);
    }
}
