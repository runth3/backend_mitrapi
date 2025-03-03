<?php

namespace App\Http\Controllers;

class ProfileController extends Controller
{
    public function me()
    {
        return response()->json(auth()->user());
    }
}