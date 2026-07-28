<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Device token registration for push delivery (FR-21).
 *
 * The driver app calls this after sign-in and whenever Firebase rotates the
 * token. Storing it on the user (users.fcm_token) means a device that is handed
 * to another driver stops receiving the previous driver's notifications, since
 * the new sign-in overwrites the token on the new user and the stale one is
 * cleared from anybody else holding it.
 */
class FcmTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:255'],
        ]);

        // One device, one owner: drop this token from any other account first.
        User::query()
            ->where('fcm_token', $validated['fcm_token'])
            ->whereKeyNot($request->user()->id)
            ->update(['fcm_token' => null]);

        $request->user()->update(['fcm_token' => $validated['fcm_token']]);

        return response()->json(['data' => ['registered' => true]]);
    }

    /** Called on sign-out so a shared handset stops receiving this user's pushes. */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->update(['fcm_token' => null]);

        return response()->json(['data' => ['registered' => false]]);
    }
}
