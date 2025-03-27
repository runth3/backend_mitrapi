<?php

namespace App\Http\Controllers;

class VersionController extends Controller
{
    public function checkVersion()
    {

        return response()->json([
            'min_version' => '1.1.1',
            'update_url' => 'https://play.google.com/store/apps/details?id=com.example',
            'maintenance' => false,
        ]);
    } 
}