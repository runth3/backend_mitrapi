<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataOfficeSimpeg;
use App\Models\DataOfficeAbsen;
use App\Models\DataOfficeEkinerja;
use App\Models\DataOfficeKoordinat;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
use App\Models\DataPegawaiSimpeg;
use App\Http\Resources\DataOfficeKoordinatResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OfficeController extends Controller
{
    // Endpoint /office/me (tetap sama)
    public function me(Request $request)
    {
        try {
            $user = $request->user('sanctum');
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            Log::info('Fetching office data for user', ['username' => $user->username]);

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

            return response()->json([
                'status' => 'success',
                'data' => [
                    'office_absen' => $dataOfficeAbsen ? $dataOfficeAbsen->toArray() : null,
                    'office_simpeg' => $dataOfficeSimpeg ? $dataOfficeSimpeg->toArray() : null,
                    'office_ekinerja' => $dataOfficeEkinerja ? $dataOfficeEkinerja->toArray() : null,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching office data', [
                'username' => $user->username ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch office data'
            ], 500);
        }
    }

    // Endpoint baru /office/{id_instansi}
    public function showByInstansi($id_instansi)
    {
        try {
            // Gunakan cache dengan kunci unik berdasarkan id_instansi
            $cacheKey = "office_instansi_{$id_instansi}";
            $cacheDuration = 60 * 60 * 24; // 24 jam dalam detik

            $officeData = Cache::remember($cacheKey, $cacheDuration, function () use ($id_instansi) {
                // Ambil data dari tiga sumber
                $dataOfficeAbsen = DataOfficeAbsen::where('id_instansi', $id_instansi)->first();
                $dataOfficeSimpeg = DataOfficeSimpeg::where('id_instansi', $id_instansi)->first();
                $dataOfficeEkinerja = DataOfficeEkinerja::where('id', $id_instansi)->first();

                // Jika tidak ada data sama sekali
                if (!$dataOfficeAbsen && !$dataOfficeSimpeg && !$dataOfficeEkinerja) {
                    return null;
                }

                return [
                    'office_absen' => $dataOfficeAbsen ? $dataOfficeAbsen->toArray() : null,
                    'office_simpeg' => $dataOfficeSimpeg ? $dataOfficeSimpeg->toArray() : null,
                    'office_ekinerja' => $dataOfficeEkinerja ? $dataOfficeEkinerja->toArray() : null,
                ];
            });

            if (!$officeData) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Office data not found for this instansi'
                ], 404);
            }

            Log::info('Fetched office data from cache or DB', ['id_instansi' => $id_instansi]);

            return response()->json([
                'status' => 'success',
                'data' => $officeData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching office by instansi', [
                'id_instansi' => $id_instansi,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch office data'
            ], 500);
        }
    }
    public function getKoordinatByInstansi(Request $request, $id_instansi)
    {
        try {
            $cacheKey = "ref_instansi_koordinat_{$id_instansi}";
            $cacheDuration = 60 * 60 * 24;
    
            $koordinat = Cache::remember($cacheKey, $cacheDuration, function () use ($id_instansi) {
                return DataOfficeKoordinat::where('id_instansi', $id_instansi)
                    ->where('aktif', 0)
                    ->first();
            });
    
            if (!$koordinat) {
                Log::info('Koordinat not found for instansi', ['id_instansi' => $id_instansi]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Koordinat not found for this instansi'
                ], 404);
            }
    
            Log::info('Fetched koordinat data', ['id_instansi' => $id_instansi]);
    
            return response()->json([
                'status' => 'success',
                'data' => new DataOfficeKoordinatResource($koordinat)
            ], 200);
    
        } catch (\Exception $e) {
            Log::error('Error fetching koordinat data', [
                'id_instansi' => $id_instansi,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch koordinat data'
            ], 500);
        }
    }
}