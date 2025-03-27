<?php

namespace App\Http\Controllers;

class ProfileController extends Controller
{
    public function me()
    {

        return response()->json(auth()->user());
    }
    public function show($id)
    {
        $user = \App\Models\User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $profileData = [
            'id' => $user->id,
            'name' => $user->name,
            'dob' => $user->dob,
            'address' => $user->address,
        ];

        return response()->json($profileData);
    }

}