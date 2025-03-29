<?php

namespace App\Http\Controllers;

use App\Models\DataPegawaiSimpeg;
use App\Models\DataPegawaiAbsen;
use App\Models\DataPegawaiEkinerja;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
class ProfileController extends Controller
{
    public function test()
    {
        return response()->json(auth()->user());
    }
    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Fetch data from the respective models
        $dataPegawaiSimpeg = DataPegawaiSimpeg::where('nip', $user->username)->first();
        $dataPegawaiAbsen = DataPegawaiAbsen::where('nip', $user->username)->first();
        $dataPegawaiEkinerja = DataPegawaiEkinerja::where('nip', $user->username)->first();

        // Prepare the response data
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
            'dataPegawaiSimpeg' => $dataPegawaiSimpeg ? $dataPegawaiSimpeg->toArray() : null,
            'dataPegawaiAbsen' => $dataPegawaiAbsen ? $dataPegawaiAbsen->toArray() : null,
            'dataPegawaiEkinerja' => $dataPegawaiEkinerja ? $dataPegawaiEkinerja->toArray() : null,
        ];

        return response()->json($response);
    }
    public function apps()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Fetch data from UserAbsen where user->username matches name
        $userAbsen = UserAbsen::where('name', $user->username)->first();

        // Fetch data from UserEkinerja where user->username matches UID
        $userEkinerja = UserEkinerja::where('UID', $user->username)->first();

        // Prepare the response data
        $response = [
           
            'userAbsen' => $userAbsen ? $userAbsen->toArray() : null,
            'userEkinerja' => $userEkinerja ? $userEkinerja->toArray() : null,
        ];

        return response()->json($response);
    } 
}