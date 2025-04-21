<?php

namespace App\Http\Controllers;

use App\Models\DataPegawaiSimpeg;
use App\Models\DataPegawaiAbsen;
use App\Models\DataPegawaiEkinerja;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
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
            Log::info('Profile fetch failed: User not authenticated');
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $dataPegawaiSimpeg = DataPegawaiSimpeg::where('nip', $user->username)->first();
            $dataPegawaiAbsen = DataPegawaiAbsen::where('nip', $user->username)->first();
            $dataPegawaiEkinerja = DataPegawaiEkinerja::where('nip', $user->username)->first();

            $response = [
                'user' => [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'dob' => $user->dob,
                    'address' => $user->address,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'dataPegawaiSimpeg' => $dataPegawaiSimpeg ? $this->formatPegawaiData($dataPegawaiSimpeg, 'officeSimpeg') : null,
                'dataPegawaiAbsen' => $dataPegawaiAbsen ? $this->formatPegawaiData($dataPegawaiAbsen, 'officeAbsen') : null,
                'dataPegawaiEkinerja' => $dataPegawaiEkinerja ? $this->formatPegawaiData($dataPegawaiEkinerja, 'officeEkinerja') : null,
            ];

            Log::info('Profile fetched successfully', ['user_id' => $user->id]);
            return $this->successResponse($response, 'Profile retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Profile fetch failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
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
            Log::info('Apps data fetch failed: User not authenticated');
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $userAbsen = UserAbsen::where('name', $user->username)->first();
            $userEkinerja = UserEkinerja::where('UID', $user->username)->first();

            $response = [
                'userAbsen' => $userAbsen ? $userAbsen->toArray() : null,
                'userEkinerja' => $userEkinerja ? $userEkinerja->toArray() : null,
            ];

            Log::info('Apps data fetched successfully', ['user_id' => $user->id]);
            return $this->successResponse($response, 'Apps data retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Apps data fetch failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    /**
     * Format pegawai data with office information.
     *
     * @param \Illuminate\Database\Eloquent\Model $pegawai
     * @param string $officeRelation
     * @return array
     */
    protected function formatPegawaiData($pegawai, $officeRelation)
    {
        $data = $pegawai->toArray();
        $office = $pegawai->$officeRelation;
        $data['office'] = $office ? [
            'id_instansi' => $office->id_instansi ?? $office->id,
            'nama_instansi' => $office->nama_instansi ?? $office->nama,
        ] : null;
        return $data;
    }
}