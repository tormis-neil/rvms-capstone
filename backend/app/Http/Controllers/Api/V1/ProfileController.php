<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * PATCH /api/v1/me/profile — self-edit name/email/password (FR-04).
     * Users can only ever update their own account through this endpoint.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->fill($request->only('name', 'email'));

        if ($request->filled('password')) {
            $user->password = $request->input('password');
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => new UserResource($user->load('agency')),
        ]);
    }
}
