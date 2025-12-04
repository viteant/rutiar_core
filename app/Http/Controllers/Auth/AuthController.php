<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $email = $request->input('email');
        $password = $request->input('password');
        $companyCode = $request->input('company_code');
        $deviceName = $request->input('device_name');

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request));

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if (! $user->is_active) {
            RateLimiter::hit($this->throttleKey($request));

            return response()->json([
                'message' => 'User is inactive.',
            ], 403);
        }

        // Lógica multi-tenant:
        if ($user->isSuperAdmin()) {
            // SUPERADMIN: puede entrar sin company_code, company_id = null
            // Si quieres exigir que NO mande company_code, aquí validas.
        } else {
            // Usuarios normales: requieren company_code
            if (! $companyCode) {
                RateLimiter::hit($this->throttleKey($request));

                return response()->json([
                    'message' => 'company_code is required.',
                    'errors' => [
                        'company_code' => ['The company_code field is required.'],
                    ],
                ], 422);
            }

            $company = Company::query()
                ->where('code', $companyCode)
                ->where('is_active', true)
                ->first();

            if (! $company || $company->id !== $user->company_id) {
                RateLimiter::hit($this->throttleKey($request));

                return response()->json([
                    'message' => 'Invalid company_code for this user.',
                    'errors' => [
                        'company_code' => ['The given company_code is invalid.'],
                    ],
                ], 422);
            }
        }

        RateLimiter::clear($this->throttleKey($request));

        // Opcional: revocar tokens previos para ese device o todos
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        $company = $user->company;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name,
                    'code' => $company->code,
                ] : null,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $request->attributes->get('tenant');

        return response()->json([
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            // Remove all personal access tokens for this user
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        abort(429, 'Too many login attempts. Please try again later.');
    }

    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'current_password' => ['The provided password does not match our records.'],
                ],
            ], 422);
        }

        $user->password = $request->input('password');
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
