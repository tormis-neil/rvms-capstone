<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/v1/login — verify credentials and issue a Sanctum token (FR-01).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->isActive()) {
            $reason = $user->status === User::STATUS_PENDING
                ? 'Your account is pending approval by your agency administrator.'
                : 'Your account registration was rejected. Contact your agency administrator.';

            return response()->json(['message' => $reason], 403);
        }

        $token = $user->createToken($user->role.'-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user->load('agency')),
        ]);
    }

    /**
     * POST /api/v1/logout — revoke the token used on this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * GET /api/v1/me — the authenticated user with their agency.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('agency')),
        ]);
    }
}
