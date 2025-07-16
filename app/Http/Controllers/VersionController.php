<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    use ApiResponseTrait;

    public function checkVersion(Request $request)
    {
        try {
            $currentVersion = $request->header('X-App-Version', '1.9.0');
            $latestVersion = '2.9.0';
            $minVersion = '1.9.0';
            
            $currentVersionSupported = version_compare($currentVersion, $minVersion, '>=');
            $forceUpdate = version_compare($currentVersion, $minVersion, '<');
            
            $updateMessage = $forceUpdate 
                ? 'This version is no longer supported. Please update to continue using the app.'
                : 'New features available! Update now for better experience.';
            
            return $this->successResponse(
                data: [
                    'latest_version' => $latestVersion,
                    'min_version' => $minVersion,
                    'current_version_supported' => $currentVersionSupported,
                    'force_update' => $forceUpdate,
                    'update_message' => $updateMessage,
                    'download_url' => 'https://shorturl.mitrakab.go.id/eabsen',
                    'release_notes' => 'Bug fixes and performance improvements'
                ],
                message: 'Version check completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check version', 500);
        }
    }
}