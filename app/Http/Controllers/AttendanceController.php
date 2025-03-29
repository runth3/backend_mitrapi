<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceController extends Controller
{
   
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'nip' => 'required'
        ]);

        $year = $request->input('year');
        $month = $request->input('month');
        $nip = $request->input('nip');
        $attendances = DB::connection('mysql_absen')->table('vd_data_checkinout')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('nip_pegawai', $nip)
            ->get();

        return response()->json($attendances);
    }

    /**
     * Display a listing of the resource with pagination.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function indexPaginate(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
            'per_page' => 'nullable|integer|min:1',
            'username' => 'required'
        ]);

        $year = $request->input('year');
        $month = $request->input('month');
        $perPage = $request->input('per_page', 10); // Default to 10 items per page if not specified.
        $username = $request->input('username');
      
        $attendances = DB::connection('second_database')->table('attendances')
        //$attendances = Attendance::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('nip', $username)
            ->paginate($perPage);

        return response()->json($attendances);
    }

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
