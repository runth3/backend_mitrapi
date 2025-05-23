<?php

namespace App\Http\Controllers;

class VersionController extends Controller
{
    public function checkVersion()
    {

        return response()->json([
            'min_version' => '1.0.0',
            'update_url' => 'https://play.google.com/store/apps/details?id=s',
            'maintenance' => false,
        ]);
    } 
}