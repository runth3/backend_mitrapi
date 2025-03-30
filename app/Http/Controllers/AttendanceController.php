<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceController extends Controller
{
   
     

    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $currentMonth = Carbon::now()->format('Y-m');
        $cacheKey = "attendance_{$user->username}_{$currentMonth}";

        // Retrieve attendance data from cache or query the database if not cached
        $monthlyAttendance = Cache::remember($cacheKey, 300, function () use ($user, $currentMonth) {
            return Attendance::where('nip_pegawai', $user->username)
                ->where('date', 'like', "$currentMonth%")
                ->orderBy('checktime', 'asc') // Sort by checktime
                ->get(); // Retrieve all fields allowed in the model's $fillable
        });

        return response()->json([
            'current_month' => [
                'month' => $currentMonth,
                'attendances' => $monthlyAttendance,
            ],
        ]);
    }
}
