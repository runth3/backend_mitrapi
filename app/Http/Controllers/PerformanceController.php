<?php

namespace App\Http\Controllers;

use App\Http\Resources\PerformanceResource;
use App\Models\Attendance;
use App\Models\Performance;
use App\Models\UserEkinerja;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PerformanceController extends Controller
{
    use ApiResponseTrait;

    private const CACHE_DURATION = 300; // 5 menit dalam detik

    /**
     * List all performances for the authenticated user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Performance list failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $perPage = $request->input('per_page', 20);
            $performances = Performance::where('NIP', $user->username)
                ->where('stsDel', 0)
                ->orderBy('tglKinerja', 'desc')
                ->paginate($perPage);

            Log::info('Performance list retrieved', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'per_page' => $perPage,
            ]);

            return $this->successResponse(
                data: PerformanceResource::collection($performances),
                message: 'Performance list retrieved successfully',
                meta: [
                    'current_page' => $performances->currentPage(),
                    'per_page' => $performances->perPage(),
                    'total' => $performances->total(),
                    'last_page' => $performances->lastPage(),
                    'from' => $performances->firstItem(),
                    'to' => $performances->lastItem(),
                ],
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve performance list', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $user->username ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve performance list', 500, $e->getMessage());
        }
    }

    /**
     * Create a new performance record.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Performance creation failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:255',
                'penjelasan' => 'nullable|string',
                'tglKinerja' => 'required|date',
                'durasiKinerjaMulai' => 'required|date_format:H:i',
                'durasiKinerjaSelesai' => 'required|date_format:H:i|after:durasiKinerjaMulai',
                'tupoksi' => 'nullable|string',
                'periodeKinerja' => 'nullable|string',
                'target' => 'nullable|integer',
                'satuanTarget' => 'nullable|string',  
            ]);

            if ($validator->fails()) {
                Log::info('Performance creation failed: Validation error', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 422, $validator->errors()->toArray());
            }

            if (Carbon::parse($request->tglKinerja)->isFuture()) {
                Log::info('Performance creation failed: Future date', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'tglKinerja' => $request->tglKinerja,
                ]);
                return $this->errorResponse('tglKinerja cannot be in the future', 422);
            }

            $attendanceExists = Attendance::where('nip_pegawai', $user->username)
                ->where('date', $request->tglKinerja)
                ->exists();

            if (!$attendanceExists) {
                Log::info('Performance creation failed: No attendance record', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'tglKinerja' => $request->tglKinerja,
                ]);
                return $this->errorResponse('tglKinerja must match an attendance record', 422);
            }

            $start = Carbon::createFromFormat('H:i', $request->durasiKinerjaMulai);
            $end = Carbon::createFromFormat('H:i', $request->durasiKinerjaSelesai);
            $durationInMinutes = $start->diffInMinutes($end);
            $durationString = $start->diff($end)->format('%H:%I');

            // Get apvId from UserEkinerja
            $userEkinerja = UserEkinerja::where('NIP', $user->username)->first();
            $apvId = $userEkinerja?->apvId;

            $performance = Performance::create([
                'nama' => $request->nama,
                'penjelasan' => $request->penjelasan,
                'tglKinerja' => $request->tglKinerja,
                'durasiKinerjaMulai' => $request->durasiKinerjaMulai,
                'durasiKinerjaSelesai' => $request->durasiKinerjaSelesai,
                'durasiKinerja' => $durationString,
                'menitKinerja' => $durationInMinutes,
                'apv' => 'P',
                'apvId' => $apvId,
                'tupoksi' => $request->tupoksi,
                'periodeKinerja' =>  Carbon::parse($request->tglKinerja)->format('Ym'),    
                'target' => $request->target,
                'satuanTarget' => $request->satuanTarget,
                'NIP' => $user->username,
                'stsDel' => 0,
                'tglInput' => Carbon::now(),

            ]);

            Log::info('Performance created successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'performance_id' => $performance->id,
            ]);

            return $this->successResponse(
                data: new PerformanceResource($performance),
                message: 'Performance created successfully',
                meta: null,
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create performance', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $user->username ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create performance', 500, $e->getMessage());
        }
    }

    /**
     * Show a specific performance record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Performance retrieval failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $performance = Performance::where('id', $id)
                ->where('stsDel', 0)
                ->first();

            if (!$performance) {
                Log::info('Performance retrieval failed: Not found', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'performance_id' => $id,
                ]);
                return $this->errorResponse('Performance not found', 404);
            }

            Log::info('Performance retrieved successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'performance_id' => $id,
            ]);

            return $this->successResponse(
                data: new PerformanceResource($performance),
                message: 'Performance retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve performance', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'performance_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve performance', 500, $e->getMessage());
        }
    }

    /**
     * Update an existing performance record.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Performance update failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $performance = Performance::where('id', $id)
                ->where('stsDel', 0)
                ->first();

            if (!$performance) {
                Log::info('Performance update failed: Not found', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'performance_id' => $id,
                ]);
                return $this->errorResponse('Performance not found', 404);
            }

            if (in_array($performance->apv, ['A', 'R'])) {
                Log::info('Performance update failed: Invalid apv status', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'performance_id' => $id,
                    'apv' => $performance->apv,
                ]);
                return $this->errorResponse('Cannot update performance with apv status A or R', 403);
            }

            $validator = Validator::make($request->all(), [
                'nama' => 'sometimes|required|string|max:255',
                'penjelasan' => 'nullable|string',
                'durasiKinerjaMulai' => 'nullable|date_format:H:i',
                'durasiKinerjaSelesai' => 'nullable|date_format:H:i|after:durasiKinerjaMulai',
                'tupoksi' => 'nullable|string',
                'periodeKinerja' => 'nullable|string',
                'target' => 'nullable|integer',
                'satuanTarget' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::info('Performance update failed: Validation error', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'performance_id' => $id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 422, $validator->errors()->toArray());
            }

            $data = $request->only([
                'nama',
                'penjelasan',
                'durasiKinerjaMulai',
                'durasiKinerjaSelesai',
                'tupoksi',
                'periodeKinerja',
                'target',
                'satuanTarget',
            ]);

            if ($request->has('durasiKinerjaMulai') && $request->has('durasiKinerjaSelesai')) {
                $start = Carbon::createFromFormat('H:i', $request->durasiKinerjaMulai);
                $end = Carbon::createFromFormat('H:i', $request->durasiKinerjaSelesai);
                $data['durasiKinerja'] = $end->diff($start)->format('%H:%I');
                $data['menitKinerja'] = $end->diffInMinutes($start);
            }

            $performance->update($data);

            Log::info('Performance updated successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'performance_id' => $id,
            ]);

            return $this->successResponse(
                data: new PerformanceResource($performance),
                message: 'Performance updated successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to update performance', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'performance_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update performance', 500, $e->getMessage());
        }
    }

    /**
     * Delete a performance record (soft delete).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Performance deletion failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $performance = Performance::where('id', $id)
                ->where('stsDel', 0)
                ->first();

            if (!$performance) {
                Log::info('Performance deletion failed: Not found', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'performance_id' => $id,
                ]);
                return $this->errorResponse('Performance not found', 404);
            }

            $performance->update(['stsDel' => 1]);

            Log::info('Performance soft deleted successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'performance_id' => $id,
            ]);

            return $this->successResponse(
                data: null,
                message: 'Performance soft deleted successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to delete performance', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'performance_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete performance', 500, $e->getMessage());
        }
    }

    /**
     * Retrieve the authenticated user's monthly performance.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Monthly performance failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $currentMonth = Carbon::now()->format('Y-m');
            $perPage = $request->input('per_page', 20);
            $cacheKey = "performance_{$user->username}_{$currentMonth}_{$perPage}";

            $monthlyPerformance = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user, $currentMonth, $perPage) {
                return Performance::where('NIP', $user->username)
                    ->where('stsDel', 0)
                    ->where('tglKinerja', 'like', "$currentMonth%")
                    ->orderBy('tglKinerja', 'asc')
                    ->paginate($perPage);
            });

            Log::info('Monthly performance retrieved', [
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
                    'performances' => PerformanceResource::collection($monthlyPerformance),
                ],
                message: 'Monthly performance retrieved successfully',
                meta: [
                    'current_page' => $monthlyPerformance->currentPage(),
                    'per_page' => $monthlyPerformance->perPage(),
                    'total' => $monthlyPerformance->total(),
                    'last_page' => $monthlyPerformance->lastPage(),
                    'from' => $monthlyPerformance->firstItem(),
                    'to' => $monthlyPerformance->lastItem(),
                ],
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve monthly performance', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $user->username ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve monthly performance', 500, $e->getMessage());
        }
    }

    /**
     * Filter performances by approval status, month, and year.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterByApv(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            Log::info('Performance filter failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
                'apvId' => 'nullable|string|in:P,A,R',
                'NIP' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                Log::info('Performance filter failed: Validation error', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 422, $validator->errors()->toArray());
            }

            $month = str_pad($request->month, 2, '0', STR_PAD_LEFT);
            $year = $request->year;
            $perPage = $request->input('per_page', 20);

            $performances = Performance::where('stsDel', 0)
                ->whereYear('tglKinerja', $year)
                ->whereMonth('tglKinerja', $month)
                ->when($request->apvId, function ($query) use ($request) {
                    $query->where('apv', $request->apvId);
                })
                ->when($request->NIP, function ($query) use ($request) {
                    $query->where('NIP', $request->NIP);
                })
                ->orderBy('tglKinerja', 'desc')
                ->paginate($perPage);

            Log::info('Performance filter retrieved', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'month' => $month,
                'year' => $year,
                'apvId' => $request->apvId,
                'NIP' => $request->NIP,
                'per_page' => $perPage,
            ]);

            return $this->successResponse(
                data: PerformanceResource::collection($performances),
                message: 'Filtered performances retrieved successfully',
                meta: [
                    'current_page' => $performances->currentPage(),
                    'per_page' => $performances->perPage(),
                    'total' => $performances->total(),
                    'last_page' => $performances->lastPage(),
                    'from' => $performances->firstItem(),
                    'to' => $performances->lastItem(),
                ],
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to filter performances', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to filter performances', 500, $e->getMessage());
        }
    }
}