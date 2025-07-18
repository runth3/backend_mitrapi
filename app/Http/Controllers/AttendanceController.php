<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\DataPegawaiAbsen;
use App\Models\DataSelfie;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    use ApiResponseTrait;

    private const CACHE_DURATION = 300; // 5 menit dalam detik

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
                $perPage,
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

    public function uploadPhotoPublic(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'checktype' => 'required|in:I,O',
                'jenis_absensi' => 'required|integer|in:1,2,3'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $currentDateTime = Carbon::now('Asia/Makassar');
            $dateFolder = $currentDateTime->format('Y/m/d');
            $timestamp = $currentDateTime->format('YmdHis');
            $fileExtension = $request->file('photo')->getClientOriginalExtension();
            $fileName = "{$user->username}_{$timestamp}.{$fileExtension}";
            $publicPath = "attendance/{$dateFolder}/{$fileName}";

            $request->file('photo')->storeAs("attendance/{$dateFolder}", $fileName, 'public');
            $publicUrl = asset("storage/attendance/{$dateFolder}/{$fileName}");

            // Generate unique ID for selfie record
            $idDataSelfie = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 13));

            // Save to vd_data_selfie table
            DataSelfie::create([
                'id_data_selfie' => $idDataSelfie,
                'nip' => $user->username,
                'nama_file' => $publicUrl,
                'tgl_selfie' => $currentDateTime,
                'checktype' => $request->checktype,
                'jenis_absensi' => $request->jenis_absensi
            ]);

            Log::info('Public photo uploaded and metadata saved', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'file_path' => $publicPath,
                'public_url' => $publicUrl,
                'id_data_selfie' => $idDataSelfie
            ]);

            return $this->successResponse(
                data: [
                    'id_data_selfie' => $idDataSelfie,
                    'file_path' => $publicPath,
                    'public_url' => $publicUrl,
                    'nama_file' => $fileName
                ],
                message: 'Photo uploaded and metadata saved successfully',
                meta: null,
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to upload public photo', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to upload photo', 500, $e->getMessage());
        }
    }

    public function getPhoto(Request $request, $filePath)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            // Decode the file path
            $decodedPath = base64_decode($filePath);
            $fullPath = storage_path('app/private/' . $decodedPath);

            // Check if file exists
            if (!file_exists($fullPath)) {
                return $this->errorResponse('Photo not found', 404);
            }

            // Check if user owns this photo (basic security)
            if (!str_contains($decodedPath, $user->username)) {
                return $this->errorResponse('Unauthorized access to photo', 403);
            }

            return response()->file($fullPath);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve photo', [
                'user_id' => $user->id,
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve photo', 500);
        }
    }

    public function listPhotos(Request $request)
    {
        Log::info('listPhotos method called', [
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
        ]);
        
        $user = auth()->user();
        if (!$user) {
            Log::error('User not authenticated in listPhotos');
            return $this->errorResponse('User not authenticated', 401);
        }
        
        Log::info('User authenticated in listPhotos', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        try {
            $validator = Validator::make($request->all(), [
                'date' => 'nullable|date',
                'checktype' => 'nullable|in:I,O,auto,manual'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            // Get photos from vd_data_selfie table
            $query = \DB::connection('mysql_absen')
                ->table('vd_data_selfie')
                ->where('nip', $user->username);

            if ($request->date) {
                $query->whereDate('tgl_selfie', $request->date);
            }

            if ($request->checktype) {
                $query->where('checktype', $request->checktype);
            }

            $photos = $query->orderBy('tgl_selfie', 'desc')->get();

            return $this->successResponse(
                data: $photos->map(function ($photo) {
                    // Check if photo_url is just filename or full URL
                    $photoUrl = $photo->nama_file;
                    if ($photoUrl && !str_starts_with($photoUrl, 'http')) {
                        $photoUrl = 'https://absen.mitrakab.go.id/upload_files/' . $photoUrl;
                    }
                    
                    return [
                        'id' => $photo->id_data_selfie,
                        'photo_url' => $photoUrl,
                        'date' => $photo->tgl_selfie,
                        'checktype' => $photo->checktype,
                        'jenis_absensi' => $photo->jenis_absensi
                    ];
                }),
                message: 'Photos retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to list photos', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve photos', 500);
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
        $encodedTime = strtoupper(base_convert($timestamp, 10, 36));
        $randomString = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        return $randomString . $encodedTime;
    }
}