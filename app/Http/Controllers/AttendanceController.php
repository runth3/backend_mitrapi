<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\DataPegawaiAbsen;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
/**
 * @OA\Tag(
 * name="Attendances",
 * description="Operations related to user attendance"
 * )
 */
class AttendanceController extends Controller
{
    use ApiResponseTrait;

    private const CACHE_DURATION = 300; // 5 menit dalam detik
    /**
    * @OA\Get(
    * path="/api/attendances/me",
    * summary="Get current month's attendance for authenticated user",
    * description="Retrieves the attendance records for the current month for the authenticated user. Requires authentication.",
    * tags={"Attendances"},
    * security={{"bearerAuth":{}}},
    * @OA\Parameter(
    * name="per_page",
    * in="query",
    * description="Number of items per page",
    * @OA\Schema(type="integer", default=20)
    * ),
    * @OA\Response(
    * response=200,
    * description="Successful operation",
    * @OA\JsonContent(
    * type="object",
    * @OA\Property(property="status", type="string", example="success"),
    * @OA\Property(property="data", type="object",
    * @OA\Property(property="month", type="string", example="2025-04"),
    * @OA\Property(property="attendances", type="array", @OA\Items(ref="#/components/schemas/AttendanceResource"))
    * ),
    * @OA\Property(property="message", type="string", example="Monthly attendance retrieved successfully"),
    * @OA\Property(property="meta", type="object",
    * @OA\Property(property="current_page", type="integer", example=1),
    * @OA\Property(property="per_page", type="integer", example=20),
    * @OA\Property(property="total", type="integer", example=100),
    * @OA\Property(property="last_page", type="integer", example=5),
    * @OA\Property(property="from", type="integer", example=1),
    * @OA\Property(property="to", type="integer", example=20)
    * )
    * )
    * ),
    * @OA\Response(
    * response=401,
    * description="User not authenticated",
    * @OA\JsonContent(
    * type="object",
    * @OA\Property(property="status", type="string", example="error"),
    * @OA\Property(property="message", type="string", example="User not authenticated")
    * )
    * ),
    * @OA\Response(
    * response=500,
    * description="Failed to retrieve monthly attendance",
    * @OA\JsonContent(
    * type="object",
    * @OA\Property(property="status", type="string", example="error"),
    * @OA\Property(property="message", type="string", example="Failed to retrieve monthly attendance"),
    * @OA\Property(property="details", type="string", example="Error message")
    * )
    * )
    * )
    */
    public function me(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Attendance fetch failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $currentMonth = Carbon::now()->format('Y-m');
            $perPage = $request->input('per_page', 20);
            $cacheKey = "attendance_{$user->username}_{$currentMonth}_{$perPage}";

            $monthlyAttendance = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user, $currentMonth, $perPage) {
                return Attendance::where('nip_pegawai', $user->username)
                    ->where('date', 'like', "$currentMonth%")
                    ->orderBy('checktime', 'asc')
                    ->paginate($perPage);
            });

            Log::info('Monthly attendance retrieved', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'month' => $currentMonth,
                'per_page' => $perPage,
            ]);

            return $this->successResponse(
                data: [
                    'month' => $currentMonth,
                    'attendances' => AttendanceResource::collection($monthlyAttendance),
                ],
                message: 'Monthly attendance retrieved successfully',
                meta: [
                    'current_page' => $monthlyAttendance->currentPage(),
                    'per_page' => $monthlyAttendance->perPage(),
                    'total' => $monthlyAttendance->total(),
                    'last_page' => $monthlyAttendance->lastPage(),
                    'from' => $monthlyAttendance->firstItem(),
                    'to' => $monthlyAttendance->lastItem(),
                ],
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve monthly attendance', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $user->username ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve monthly attendance', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     * path="/api/attendances/check-in",
     * summary="Record an automatic check-in",
     * description="Records an automatic check-in for the authenticated user. Requires authentication.",
     * tags={"Attendances"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"coordinate", "altitude", "checktype", "jenis_absensi"},
     * @OA\Property(property="coordinate", type="string", example="-6.175392,106.827153"),
     * @OA\Property(property="altitude", type="number", format="float", example=10.5),
     * @OA\Property(property="checktype", type="string", example="IN", enum={"IN", "OUT"}),
     * @OA\Property(property="jenis_absensi", type="string", example="REGULER", enum={"REGULER", "MANUAL"})
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Check-in successful",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", ref="#/components/schemas/AttendanceResource"),
     * @OA\Property(property="message", type="string", example="Check-in successful")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Invalid input or already checked in",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Invalid input"),
     * @OA\Property(property="details", type="object", @OA\Property(property="coordinate", type="array", @OA\Items(type="string")))
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="User not authenticated",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="User not authenticated")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="User data not found",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="User data not found in DataPegawaiAbsen")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Failed to process check-in",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Failed to process check-in"),
     * @OA\Property(property="details", type="string", example="Error message")
     * )
     * )
     * )
     */

    public function checkin(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Check-in failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $dataPegawai = DataPegawaiAbsen::where('nip', $user->username)->first();
            if (!$dataPegawai) {
                Log::info('Check-in failed: User data not found', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                ]);
                return $this->errorResponse('User data not found in DataPegawaiAbsen', 404);
            }

            $validator = Validator::make($request->all(), [
                'coordinate' => 'required|string',
                'altitude' => 'required|numeric',
                'checktype' => 'required|string',
                'jenis_absensi' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::info('Check-in failed: Validation error', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $currentDate = Carbon::now('Asia/Makassar')->format('Y-m-d');
            $currentTime = Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');

            $existingCheckin = Attendance::where('nip_pegawai', $user->username)
                ->where('date', $currentDate)
                ->where('checktype', $request->checktype)
                ->where('jenis_absensi', $request->jenis_absensi)
                ->exists();

            if ($existingCheckin) {
                Log::info('Check-in failed: Already checked in', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'date' => $currentDate,
                ]);
                return $this->errorResponse('You have already checked in today', 400);
            }

            $idCheckinout = $this->generateIdCheckinout($currentTime);

            $attendance = Attendance::create([
                'id_checkinout' => $idCheckinout,
                'nip_pegawai' => $user->username,
                'id_instansi' => $dataPegawai->id_instansi,
                'id_unit_kerja' => $dataPegawai->id_unit_kerja,
                'id_profile' => $dataPegawai->id_pegawai,
                'date' => $currentDate,
                'checktime' => $currentTime,
                'checktype' => $request->checktype,
                'iplog' => $request->ip(),
                'coordinate' => $request->coordinate,
                'altitude' => $request->altitude,
                'jenis_absensi' => $request->jenis_absensi,
                'user_platform' => $request->header('User-Agent'),
                'browser_name' => 'Android App',
                'browser_version' => $request->header('App-Version', '1.0.0'),
                'aprv_stats' => 'Y',
            ]);

            Log::info('Check-in successful', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'attendance_id' => $attendance->id,
            ]);

            return $this->successResponse(
                data: new AttendanceResource($attendance),
                message: 'Check-in successful',
                meta: null,
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to process check-in', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $user->username ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to process check-in', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     * path="/api/attendances/manual-check-in",
     * summary="Record a manual check-in",
     * description="Records a manual check-in for the authenticated user. Requires authentication.",
     * tags={"Attendances"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"date", "checktime", "coordinate", "altitude", "checktype", "jenis_absensi"},
     * @OA\Property(property="date", type="string", format="date", example="2025-04-25"),
     * @OA\Property(property="checktime", type="string", format="date-time", example="2025-04-25 09:00:00"),
     * @OA\Property(property="coordinate", type="string", example="-6.175392,106.827153"),
     * @OA\Property(property="altitude", type="number", format="float", example=10.5),
     * @OA\Property(property="checktype", type="string", example="IN", enum={"IN", "OUT"}),
     * @OA\Property(property="jenis_absensi", type="string", example="MANUAL", enum={"REGULER", "MANUAL"})
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Manual check-in successful",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", ref="#/components/schemas/AttendanceResource"),
     * @OA\Property(property="message", type="string", example="Manual check-in successful")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Invalid input or already checked in",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Invalid input"),
     * @OA\Property(property="details", type="object", @OA\Property(property="date", type="array", @OA\Items(type="string")))
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="User not authenticated",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="User not authenticated")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="User data not found",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="User data not found in DataPegawaiAbsen")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Failed to process manual check-in",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Failed to process manual check-in"),
     * @OA\Property(property="details", type="string", example="Error message")
     * )
     * )
     * )
     */
    public function manualCheckin(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Manual check-in failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $dataPegawai = DataPegawaiAbsen::where('nip', $user->username)->first();
            if (!$dataPegawai) {
                Log::info('Manual check-in failed: User data not found', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                ]);
                return $this->errorResponse('User data not found in DataPegawaiAbsen', 404);
            }

            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'checktime' => 'required|date_format:Y-m-d H:i:s',
                'coordinate' => 'required|string',
                'altitude' => 'required|numeric',
                'checktype' => 'required|string',
                'jenis_absensi' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::info('Manual check-in failed: Validation error', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $existingCheckin = Attendance::where('nip_pegawai', $user->username)
                ->where('date', $request->date)
                ->where('checktype', $request->checktype)
                ->where('jenis_absensi', $request->jenis_absensi)
                ->exists();

            if ($existingCheckin) {
                Log::info('Manual check-in failed: Already checked in', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'date' => $request->date,
                ]);
                return $this->errorResponse('You have already checked in on this date', 400);
            }

            $idCheckinout = $this->generateIdCheckinout($request->checktime);

            $attendance = Attendance::create([
                'id_checkinout' => $idCheckinout,
                'nip_pegawai' => $user->username,
                'id_instansi' => $dataPegawai->id_instansi,
                'id_unit_kerja' => $dataPegawai->id_unit_kerja,
                'id_profile' => $dataPegawai->id_pegawai,
                'date' => $request->date,
                'checktime' => $request->checktime,
                'checktype' => $request->checktype,
                'iplog' => $request->ip(),
                'coordinate' => $request->coordinate,
                'altitude' => $request->altitude,
                'jenis_absensi' => $request->jenis_absensi,
                'user_platform' => $request->header('User-Agent'),
                'browser_name' => 'Android App',
                'browser_version' => $request->header('App-Version', '1.0.0'),
                'aprv_stats' => 'N',
            ]);

            Log::info('Manual check-in successful', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'attendance_id' => $attendance->id,
            ]);

            return $this->successResponse(
                data: new AttendanceResource($attendance),
                message: 'Manual check-in successful',
                meta: null,
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to process manual check-in', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $user->username ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to process manual check-in', 500, $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     * path="/api/attendances/{id}/approve-or-reject",
     * summary="Approve or reject a manual attendance record",
     * description="Approves or rejects a manual attendance record by its ID. Requires authentication.",
     * tags={"Attendances"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID of the attendance record",
     * @OA\Schema(type="integer", format="int64")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"status"},
     * @OA\Property(property="status", type="string", example="Y", enum={"Y", "N"})
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Approval/rejection successful",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", ref="#/components/schemas/AttendanceResource"),
     * @OA\Property(property="message", type="string", example="Manual attendance approved successfully")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Invalid input or not a manual check-in",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Invalid input"),
     * @OA\Property(property="details", type="object", @OA\Property(property="status", type="array", @OA\Items(type="string")))
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="User not authenticated",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="User not authenticated")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Attendance not found",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Attendance not found")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Failed to process approval/rejection",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Failed to process approval/rejection"),
     * @OA\Property(property="details", type="string", example="Error message")
     * )
     * )
     * )
     */
    public function approveOrReject(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Approval/Rejection failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $attendance = Attendance::find($id);
            if (!$attendance) {
                Log::info('Approval/Rejection failed: Attendance not found', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'attendance_id' => $id,
                ]);
                return $this->errorResponse('Attendance not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:Y,N',
            ]);

            if ($validator->fails()) {
                Log::info('Approval/Rejection failed: Validation error', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            if ($attendance->aprv_stats !== 'N') {
                Log::info('Approval/Rejection failed: Not a manual check-in', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'attendance_id' => $id,
                ]);
                return $this->errorResponse('Only manual check-ins can be approved or rejected', 400);
            }

            $updateData = [
                'aprv_stats' => $request->status,
            ];

            if ($request->status === 'Y') {
                $updateData['aprv_by'] = $user->username;
                $updateData['aprv_on'] = Carbon::now('Asia/Makassar');
            } else {
                $updateData['reject_by'] = $user->username;
                $updateData['reject_on'] = Carbon::now('Asia/Makassar');
            }

            $attendance->update($updateData);

            $message = $request->status === 'Y' ? 'Manual attendance approved successfully' : 'Manual attendance rejected successfully';

            Log::info($message, [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'attendance_id' => $id,
                'status' => $request->status,
            ]);

            return $this->successResponse(
                data: new AttendanceResource($attendance),
                message: $message,
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to process approval/rejection', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'attendance_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to process approval/rejection', 500, $e->getMessage());
        }
    }

     /**
     * @OA\Get(
     * path="/api/attendances/manual",
     * summary="List manual attendance records by approval status",
     * description="Retrieves a list of manual attendance records filtered by their approval status. Requires authentication.",
     * tags={"Attendances"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="status",
     * in="query",
     * required=true,
       * description="Approval status to filter by (Y for approved, N for not approved)",
     * @OA\Schema(type="string", enum={"Y", "N"})
     * ),
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="Number of items per page",
     * @OA\Schema(type="integer", default=20)
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AttendanceResource")),
     * @OA\Property(property="message", type="string", example="Manual attendance list retrieved successfully"),
     * @OA\Property(property="meta", type="object",
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="per_page", type="integer", example=20),
     * @OA\Property(property="total", type="integer", example=50),
     * @OA\Property(property="last_page", type="integer", example=3),
     * @OA\Property(property="from", type="integer", example=1),
     * @OA\Property(property="to", type="integer", example=20)
     * )
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Invalid input",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Invalid input"),
     * @OA\Property(property="details", type="object", @OA\Property(property="status", type="array", @OA\Items(type="string")))
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="User not authenticated",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="User not authenticated")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Failed to retrieve manual attendance list",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Failed to retrieve manual attendance list"),
     * @OA\Property(property="details", type="string", example="Error message")
     * )
     * )
     * )
     */
    public function listManualAttendance(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Manual attendance list failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:Y,N',
            ]);

            if ($validator->fails()) {
                Log::info('Manual attendance list failed: Validation error', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $perPage = $request->input('per_page', 20);
            $manualAttendances = Attendance::where('checktype', 'manual')
                ->where('aprv_stats', $request->status)
                ->orderBy('date', 'desc')
                ->paginate($perPage);

            Log::info('Manual attendance list retrieved', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => $request->status,
                'per_page' => $perPage,
            ]);

            return $this->successResponse(
                data: AttendanceResource::collection($manualAttendances),
                message: 'Manual attendance list retrieved successfully',
                meta: [
                    'current_page' => $manualAttendances->currentPage(),
                    'per_page' => $manualAttendances->perPage(),
                    'total' => $manualAttendances->total(),
                    'last_page' => $manualAttendances->lastPage(),
                    'from' => $manualAttendances->firstItem(),
                    'to' => $manualAttendances->lastItem(),
                ],
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve manual attendance list', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve manual attendance list', 500, $e->getMessage());
        }
    }

   /**
     * @OA\Post(
     * path="/api/attendances/upload-photo",
     * summary="Upload a photo for attendance",
     * description="Uploads a photo related to an attendance record. Requires authentication.",
     * tags={"Attendances"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * required={"photo", "checktype"},
     * @OA\Property(property="photo", type="file", description="Image file to upload"),
     * @OA\Property(property="checktype", type="string", example="auto", enum={"auto", "manual"})
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Photo uploaded successfully",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", type="object", @OA\Property(property="file_path", type="string", example="absen/2025-04-25/12345-A-1650888000.jpg")),
     * @OA\Property(property="message", type="string", example="Photo uploaded successfully")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Invalid input",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Invalid input"),
     * @OA\Property(property="details", type="object", @OA\Property(property="photo", type="array", @OA\Items(type="string")))
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="User not authenticated",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="User not authenticated")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Failed to upload photo",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Failed to upload photo"),
     * @OA\Property(property="details", type="string", example="Error message")
     * )
     * )
     * )
     */
    public function uploadPhoto(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Photo upload failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'checktype' => 'required|in:auto,manual',
            ]);

            if ($validator->fails()) {
                Log::info('Photo upload failed: Validation error', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $currentDate = Carbon::now('Asia/Makassar')->format('Y-m-d');
            $timestamp = Carbon::now('Asia/Makassar')->timestamp;
            $nip = $user->username;
            $checkTypeCode = $request->checktype === 'manual' ? 'M' : 'A';

            $fileExtension = $request->file('photo')->getClientOriginalExtension();
            $fileName = "{$nip}-{$checkTypeCode}-{$timestamp}.{$fileExtension}";
            $filePath = "absen/{$currentDate}/{$fileName}";

            $request->file('photo')->storeAs("private/absen/{$currentDate}", $fileName);

            Log::info('Photo uploaded successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'file_path' => $filePath,
            ]);

            return $this->successResponse(
                data: ['file_path' => $filePath],
                message: 'Photo uploaded successfully',
                meta: null,
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to upload photo', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to upload photo', 500, $e->getMessage());
        }
    }

    /**
     * Generate a unique id_checkinout.
     *
     * @param string $checktime
     * @return string
     */
    private function generateIdCheckinout($checktime)
    {
        $timestamp = strtotime($checktime);
        $encodedTime = base_convert($timestamp, 10, 36);
        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        return $randomString . $encodedTime;
    }
}