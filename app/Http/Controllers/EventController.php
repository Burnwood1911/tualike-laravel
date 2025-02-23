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

    public function getGuests(int $id)
    {
        $guests = Guest::where('event_id', $id)->get();


        $result = [
            'statusCode' => 200,
            'data' => $guests,
            'message' => 'Guests retrieved successfully',
        ];

        return response()->json($result, $result['statusCode']);
    }



    public function getCardPage($eventId, $guestId)
    {
        $guest = Guest::where('event_id', $eventId)
            ->where('id', $guestId)
            ->first();

        return view('card', [
            'guest' => $guest
        ]);
    }


    public function updateAttendance(Request $request, $eventId, $guestId)
    {
        try {
            $guest = Guest::where('event_id', $eventId)
                ->where('id', $guestId)
                ->firstOrFail();

            $guest->update([
                'attendance_status' => $request->attendance_status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating attendance status'
            ], 500);
        }
    }

}
