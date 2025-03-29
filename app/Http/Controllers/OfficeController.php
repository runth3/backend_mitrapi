<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataOfficeSimpeg;
use App\Models\DataOfficeAbsen;
use App\Models\DataOfficeEkinerja;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
use App\Models\DataPegawaiSimpeg;

class OfficeController extends Controller
{
    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Fetch related IDs from UserAbsen, UserEkinerja, and DataPegawaiSimpeg
        $userAbsen = UserAbsen::where('name', $user->username)->first();
        $userEkinerja = UserEkinerja::where('UID', $user->username)->first();
        $dataPegawaiSimpeg = DataPegawaiSimpeg::where('nip', $user->username)->first();

        // Extract id_instansi or opd_id from the fetched data
        $idInstansiAbsen = $userAbsen ? $userAbsen->id_instansi : null;
        $idInstansiSimpeg = $dataPegawaiSimpeg ? $dataPegawaiSimpeg->id_instansi : null;
        $opdIdEkinerja = $userEkinerja ? $userEkinerja->opd_id : null;

        // Fetch data from DataOffice models using the extracted IDs
        $dataOfficeAbsen = $idInstansiAbsen ? DataOfficeAbsen::where('id_instansi', $idInstansiAbsen)->first() : null;
        $dataOfficeSimpeg = $idInstansiSimpeg ? DataOfficeSimpeg::where('id_instansi', $idInstansiSimpeg)->first() : null;
        $dataOfficeEkinerja = $opdIdEkinerja ? DataOfficeEkinerja::where('id', $opdIdEkinerja)->first() : null;

        // Prepare the response data
        $response = [
            //'userEkinerja' => $userEkinerja ? $userEkinerja->toArray() : null,
            'dataOfficeAbsen' => $dataOfficeAbsen ? $dataOfficeAbsen->toArray() : null,
            'dataOfficeSimpeg' => $dataOfficeSimpeg ? $dataOfficeSimpeg->toArray() : null,
            'dataOfficeEkinerja' => $dataOfficeEkinerja ? $dataOfficeEkinerja->toArray() : null,
        ];

        return response()->json($response);
    }
}
