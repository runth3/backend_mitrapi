<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceController extends Controller
{
    /**
     * Display the current month's attendance for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
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

    public function checkin(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Get user details from the DataPegawaiAbsen model
        $dataPegawai = $user->dataPegawaiAbsen; // Assuming the relationship is defined in the User model

        if (!$dataPegawai) {
            return response()->json(['message' => 'User data not found in DataPegawaiAbsen'], 404);
        }

        $currentDate = Carbon::now('Asia/Makassar')->format('Y-m-d'); // GMT+7
        $currentTime = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s'); // Full datetime

        // Generate a unique id_checkinout
        $idCheckinout = $this->generateIdCheckinout($currentTime);

        // Check if the user has already checked in today
        $existingCheckin = Attendance::where('nip_pegawai', $user->username)
            ->where('date', $currentDate)
            ->exists();

        if ($existingCheckin) {
            return response()->json(['message' => 'You have already checked in today'], 400);
        }

        // Validate required fields
        $request->validate([
            'coordinate' => 'required|string', // Coordinate is mandatory
            'altitude' => 'required|numeric', // Altitude is mandatory
        ]);

        // Create a new attendance record
        $attendance = Attendance::create([
            'id_checkinout' => $idCheckinout,
            'nip_pegawai' => $user->username,
            'id_instansi' => $dataPegawai->id_instansi,
            'id_unit_kerja' => $dataPegawai->id_unit_kerja,
            'id_profile' => $dataPegawai->id_pegawai,
            'date' => $currentDate,
            'checktime' => $currentTime, // Full datetime
            'checktype' => 'auto', // Automatic check-in
            'iplog' => $request->ip(), // Automatically capture IP address
            'coordinate' => $request->coordinate, // GPS coordinates
            'altitude' => $request->altitude, // Altitude
            'jenis_absensi' => 'checkin', // Default type
            'user_platform' => $request->header('User-Agent'), // Capture user agent
            'browser_name' => 'Android App', // Android app name
            'browser_version' => $request->header('App-Version', '1.0.0'), // App version (default to 1.0.0)
            'aprv_stats' => 'Y', // Automatic check-in
        ]);

        return response()->json(['message' => 'Check-in successful', 'attendance' => $attendance], 201);
    }

    public function manualCheckin(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Get user details from the DataPegawaiAbsen model
        $dataPegawai = $user->dataPegawaiAbsen; // Assuming the relationship is defined in the User model

        if (!$dataPegawai) {
            return response()->json(['message' => 'User data not found in DataPegawaiAbsen'], 404);
        }

        // Validate required fields
        $request->validate([
            'date' => 'required|date',
            'checktime' => 'required|date_format:Y-m-d H:i:s', // Full datetime
            'coordinate' => 'required|string', // Coordinate is mandatory
            'altitude' => 'required|numeric', // Altitude is mandatory
        ]);

        // Generate a unique id_checkinout
        $idCheckinout = $this->generateIdCheckinout($request->checktime);

        // Check if the user has already checked in on the specified date
        $existingCheckin = Attendance::where('nip_pegawai', $user->username)
            ->where('date', $request->date)
            ->exists();

        if ($existingCheckin) {
            return response()->json(['message' => 'You have already checked in on this date'], 400);
        }

        // Create a new attendance record
        $attendance = Attendance::create([
            'id_checkinout' => $idCheckinout,
            'nip_pegawai' => $user->username,
            'id_instansi' => $dataPegawai->id_instansi,
            'id_unit_kerja' => $dataPegawai->id_unit_kerja,
            'id_profile' => $dataPegawai->id_pegawai,
            'date' => $request->date,
            'checktime' => $request->checktime, // Full datetime
            'checktype' => 'manual', // Manual check-in
            'iplog' => $request->ip(), // Automatically capture IP address
            'coordinate' => $request->coordinate, // GPS coordinates
            'altitude' => $request->altitude, // Altitude
            'jenis_absensi' => 'checkin', // Default type
            'user_platform' => $request->header('User-Agent'), // Capture user agent
            'browser_name' => 'Android App', // Android app name
            'browser_version' => $request->header('App-Version', '1.0.0'), // App version (default to 1.0.0)
            'aprv_stats' => 'N', // Manual check-in
        ]);

        return response()->json(['message' => 'Manual check-in successful', 'attendance' => $attendance], 201);
    }

    public function approveOrReject(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Y,N', // Status must be either 'Y' (approved) or 'N' (rejected)
        ]);

        $attendance = Attendance::findOrFail($id);

        // Ensure the attendance is a manual check-in
        if ($attendance->checktype !== 'manual') {
            return response()->json(['message' => 'Only manual check-ins can be approved or rejected'], 400);
        }

        // Update approval/rejection fields
        if ($request->status === 'Y') {
            $attendance->update([
                'aprv_stats' => 'Y',
                'aprv_by' => auth()->user()->username, // Admin approving the attendance
                'aprv_on' => Carbon::now('Asia/Makassar'), // Approval timestamp
            ]);

            return response()->json(['message' => 'Manual attendance approved successfully']);
        } else {
            $attendance->update([
                'aprv_stats' => 'N',
                'reject_by' => auth()->user()->username, // Admin rejecting the attendance
                'reject_on' => Carbon::now('Asia/Makassar'), // Rejection timestamp
            ]);

            return response()->json(['message' => 'Manual attendance rejected successfully']);
        }
    }

    public function listManualAttendance(Request $request)
    {
        $request->validate([
            'status' => 'required|in:Y,N', // Status must be either 'Y' (approved) or 'N' (rejected)
        ]);

        $manualAttendances = Attendance::where('checktype', 'manual') // Only manual check-ins
            ->where('aprv_stats', $request->status) // Filter by approval status
            ->orderBy('date', 'desc') // Sort by date
            ->get();

        return response()->json($manualAttendances);
    }

    public function uploadPhoto(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Validate the uploaded file
            'checktype' => 'required|in:auto,manual', // Ensure checktype is either 'auto' or 'manual'
        ]);

        $currentDate = Carbon::now('Asia/Makassar')->format('Y-m-d'); // GMT+7
        $timestamp = Carbon::now('Asia/Makassar')->timestamp; // Current timestamp
        $nip = $user->username; // Get the user's NIP (username)
        $checkTypeCode = $request->checktype === 'manual' ? 'M' : 'A'; // Use 'M' for manual, 'A' for auto

        // Define the file path
        $fileExtension = $request->file('photo')->getClientOriginalExtension();
        $fileName = "{$nip}-{$checkTypeCode}-{$timestamp}.{$fileExtension}";
        $filePath = "absen/{$currentDate}/{$fileName}";

        // Store the file in the private disk
        $request->file('photo')->storeAs("private/absen/{$currentDate}", $fileName);

        return response()->json([
            'message' => 'Photo uploaded successfully',
            'file_path' => $filePath,
        ]);
    }

    private function generateIdCheckinout($checktime)
    {
        $timestamp = strtotime($checktime);
        $encodedTime = base_convert($timestamp, 10, 36); // Convert timestamp to Base36
        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6); // Random string
        return $randomString . $encodedTime;
    }
}
