<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Resources\PegawaiResource;
use App\Http\Resources\UserAbsenResource;
use App\Http\Resources\UserEkinerjaResource;
use App\Models\DataPegawaiSimpeg;
use App\Models\DataPegawaiAbsen;
use App\Models\DataPegawaiEkinerja;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get the authenticated user's profile.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            Log::info('Profile fetch failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            // Cache profile data for 1 hour
            $cacheKey = "profile_me_{$user->id}";
            $response = Cache::remember($cacheKey, 3600, function () use ($user) {
                $data_pegawai_simpeg = DataPegawaiSimpeg::with('officeSimpeg')
                    ->where('nip', $user->username)
                    ->first();
                $data_pegawai_absen = DataPegawaiAbsen::with('officeAbsen')
                    ->where('nip', $user->username)
                    ->first();
                $data_pegawai_ekinerja = DataPegawaiEkinerja::with('officeEkinerja')
                    ->where('nip', $user->username)
                    ->first();

                return [
                    'user' => new UserResource($user),
                    'data_pegawai_simpeg' => $data_pegawai_simpeg
                        ? new PegawaiResource($data_pegawai_simpeg, 'officeSimpeg')
                        : null,
                    'data_pegawai_absen' => $data_pegawai_absen
                        ? new PegawaiResource($data_pegawai_absen, 'officeAbsen')
                        : null,
                    'data_pegawai_ekinerja' => $data_pegawai_ekinerja
                        ? new PegawaiResource($data_pegawai_ekinerja, 'officeEkinerja')
                        : null,
                ];
            });

            Log::info('Profile fetched successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse($response, 'Profile retrieved successfully', null, 200);
        } catch (\Exception $e) {
            Log::error('Profile fetch failed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    /**
     * Get the authenticated user's app-specific data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apps(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            Log::info('Apps data fetch failed: User not authenticated', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            // Cache apps data for 1 hour
            $cacheKey = "profile_apps_{$user->id}";
            $response = Cache::remember($cacheKey, 3600, function () use ($user) {
                $user_absen = UserAbsen::where('name', $user->username)->first();
                $user_ekinerja = UserEkinerja::where('UID', $user->username)->first();

                return [
                    'user_absen' => $user_absen ? new UserAbsenResource($user_absen) : null,
                    'user_ekinerja' => $user_ekinerja ? new UserEkinerjaResource($user_ekinerja) : null,
                ];
            });

            Log::info('Apps data fetched successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse($response, 'Apps data retrieved successfully', null, 200);
        } catch (\Exception $e) {
            Log::error('Apps data fetch failed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }
}