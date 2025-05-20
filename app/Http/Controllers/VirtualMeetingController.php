<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

use Illuminate\Http\Request;

class VirtualMeetingController extends Controller
{
    public function createMeeting(Request $request)
    {
        $METERED_DOMAIN = env('METERED_DOMAIN');
        $METERED_SECRET_KEY = env('METERED_SECRET_KEY');

        $response = Http::post("https://{$METERED_DOMAIN}/api/v1/room?secretKey={$METERED_SECRET_KEY}", [
            'autoJoin' => true
        ]);

        $roomName = $response->json("roomName");

        // ✅ Return JSON instead of redirect
        return response()->json(['roomName' => $roomName]);
    }

    public function validateMeeting(Request $request)
    {
        $METERED_DOMAIN = env('METERED_DOMAIN');
        $METERED_SECRET_KEY = env('METERED_SECRET_KEY');
        $meetingId = $request->input('meetingId');

        $response = Http::get("https://{$METERED_DOMAIN}/api/v1/room/{$meetingId}?secretKey={$METERED_SECRET_KEY}");

        if ($response->successful()) {
            $roomName = $response->json("roomName");

            return response()->json([
                'success' => true,
                'roomName' => $roomName,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid Meeting ID',
        ], 404);
    }
}
