<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function scan(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'qr' => 'required|string',
        ]);

        $qr = $request->input('qr');

        // Find the guest by QR code
        $guest = Guest::where('qr', $qr)->first();

        if ($guest) {
            if ($guest->uses != 0) {
                $guest->uses -= 1;
                $guest->save();

                return response()->json([
                    'statusCode' => 200,
                    'message' => 'success',
                    'data' => [
                        'guestName' => $guest->name,
                        'inviteType' => $guest->guest_type,
                    ],
                ]);
            } else {
                return response()->json([
                    'statusCode' => 500,
                    'message' => 'Max Usage',
                ]);
            }
        } else {
            return response()->json([
                'statusCode' => 500,
                'message' => 'Guest not found',
            ]);
        }
    }
}
