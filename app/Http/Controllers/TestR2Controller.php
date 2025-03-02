<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestR2Controller extends Controller
{
    public function testConnection()
    {
        try {
            // List files in the R2 bucket
            $files = Storage::disk('r2')->allFiles();

            // Get R2 configuration
            $config = config('filesystems.disks.r2');

            return response()->json([
                'success' => true,
                'message' => 'Successfully connected to R2',
                'files_count' => count($files),
                'config' => [
                    'driver' => $config['driver'],
                    'bucket' => $config['bucket'],
                    'region' => $config['region'],
                    'endpoint' => $config['endpoint'],
                    'url' => $config['url'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to R2: ' . $e->getMessage(),
            ], 500);
        }
    }
}