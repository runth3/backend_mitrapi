<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataOfficeKoordinatResource;
use App\Http\Resources\OfficeResource;
use App\Models\DataOfficeAbsen;
use App\Models\DataOfficeEkinerja;
use App\Models\DataOfficeKoordinat;
use App\Models\DataOfficeSimpeg;
use App\Models\DataPegawaiSimpeg;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OfficeController extends Controller
{
    use ApiResponseTrait;

    private const CACHE_DURATION = 86400; // 24 jam dalam detik

    /**
     * Get the authenticated user's office data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            Log::info('Office data fetch failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $cacheKey = "office_me_{$user->id}";
            $officeData = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($user) {
                $userAbsen = UserAbsen::where('name', $user->username)->first();
                $userEkinerja = UserEkinerja::where('UID', $user->username)->first();
                $userSimpeg = DataPegawaiSimpeg::where('nip', $user->username)->first();

                $dataOfficeAbsen = $userAbsen?->id_instansi
                    ? DataOfficeAbsen::where('id_instansi', $userAbsen->id_instansi)->first()
                    : null;
                $dataOfficeSimpeg = $userSimpeg?->id_instansi
                    ? DataOfficeSimpeg::where('id_instansi', $userSimpeg->id_instansi)->first()
                    : null;
                $dataOfficeEkinerja = $userEkinerja?->opd_id
                    ? DataOfficeEkinerja::where('id', $userEkinerja->opd_id)->first()
                    : null;

                return [
                    'office_absen' => $dataOfficeAbsen ? new OfficeResource($dataOfficeAbsen) : null,
                    'office_simpeg' => $dataOfficeSimpeg ? new OfficeResource($dataOfficeSimpeg) : null,
                    'office_ekinerja' => $dataOfficeEkinerja ? new OfficeResource($dataOfficeEkinerja) : null,
                ];
            });

            Log::info('Office data fetched successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse(
                data: $officeData,
                message: 'Office data retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch office data', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $user->username ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to fetch office data', 500, $e->getMessage());
        }
    }

    /**
     * Get office data by instansi ID.
     *
     * @param string $id_instansi
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByInstansi(Request $request, $id_instansi)
    {
        try {
            $cacheKey = "office_instansi_{$id_instansi}";
            $officeData = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($id_instansi) {
                $dataOfficeAbsen = DataOfficeAbsen::where('id_instansi', $id_instansi)->first();
                $dataOfficeSimpeg = DataOfficeSimpeg::where('id_instansi', $id_instansi)->first();
                $dataOfficeEkinerja = DataOfficeEkinerja::where('id', $id_instansi)->first();

                if (!$dataOfficeAbsen && !$dataOfficeSimpeg && !$dataOfficeEkinerja) {
                    return null;
                }

                return [
                    'office_absen' => $dataOfficeAbsen ? new OfficeResource($dataOfficeAbsen) : null,
                    'office_simpeg' => $dataOfficeSimpeg ? new OfficeResource($dataOfficeSimpeg) : null,
                    'office_ekinerja' => $dataOfficeEkinerja ? new OfficeResource($dataOfficeEkinerja) : null,
                ];
            });

            if (!$officeData) {
                Log::info('Office data not found', [
                    'id_instansi' => $id_instansi,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                return $this->errorResponse('Office data not found for this instansi', 404);
            }

            Log::info('Office data fetched successfully', [
                'id_instansi' => $id_instansi,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse(
                data: $officeData,
                message: 'Office data retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch office data by instansi', [
                'id_instansi' => $id_instansi,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to fetch office data', 500, $e->getMessage());
        }
    }

    /**
     * Get coordinate data by instansi ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id_instansi
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKoordinatByInstansi(Request $request, $id_instansi)
    {
        try {
            $cacheKey = "ref_instansi_koordinat_{$id_instansi}";
            $koordinat = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($id_instansi) {
                return DataOfficeKoordinat::where('id_instansi', $id_instansi)
                    ->where('aktif', 0)
                    ->first();
            });

            if (!$koordinat) {
                Log::info('Coordinate data not found', [
                    'id_instansi' => $id_instansi,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                return $this->errorResponse('Coordinate data not found for this instansi', 404);
            }

            Log::info('Coordinate data fetched successfully', [
                'id_instansi' => $id_instansi,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse(
                data: new DataOfficeKoordinatResource($koordinat),
                message: 'Coordinate data retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch coordinate data', [
                'id_instansi' => $id_instansi,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to fetch coordinate data', 500, $e->getMessage());
        }
    }
}