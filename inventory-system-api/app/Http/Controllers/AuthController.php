<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\{ChangeMyPasswordRequest, RegisterRequest, LoginRequest, UpdateProfileRequest, UpdateProfileRequestAdmin, ChangePasswordRequest};
use App\Models\User;
use App\Services\{AuthService, UserService};
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService, private UserService $userService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());
            return $this->success($user, 'Register berhasil.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function suggestId(Request $request): JsonResponse
    {
        $request->validate(['id_department' => 'required|string']);
        $suggestion = $this->authService->suggestId($request->id_department);
        return $this->success($suggestion);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->login($request->validated());
            return $this->success($user, 'Login berhasil.', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            return $this->success(null, 'Logout berhasil.', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->me($request->user());
            return $this->success($user, 'Berhasil memuat data pengguna.', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateMyProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        try {
            $updated = $this->authService->updateProfile($user, $request->validated());
            return $this->success($updated, 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateProfile(UpdateProfileRequestAdmin $request, string $id): JsonResponse
    {
        $user = $this->userService->find($id);
        if (!$user) {
            return $this->notFound('Pengguna tidak ditemukan.');
        }
        try {
            $updated = $this->authService->updateProfile($user, $request->validated());
            return $this->success($updated, 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function changeMyPassword(ChangeMyPasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        try {
            $this->authService->changePassword($user, $request->validated());
            return $this->success(null, 'Password berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function changePassword(ChangePasswordRequest $request, string $id): JsonResponse
    {
        $user = $this->userService->find($id);
        if (!$user) {
            return $this->notFound('Pengguna tidak ditemukan.');
        }
        try {
            $this->authService->changePassword($user, $request->validated());
            return $this->success(null, 'Password berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->error('Gagal mengirim link verifikasi.', 500);
        }
        if ($user->hasVerifiedEmail()) {
            return $this->error('Email sudah diverifikasi.', 400);
        }
        $key = 'resend-verification:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return $this->error(
                'Terlalu banyak permintaan. Silakan coba lagi dalam beberapa menit.',
                429
            );
        }
        RateLimiter::hit($key, 600);
        $user->sendEmailVerificationNotification();
        return $this->success(
            null,
            'Link verifikasi berhasil dikirim ulang ke email Anda.'
        );
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::find($id);
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        if (! $user) {
            return redirect()->away($frontendUrl . '/login?error=user_not_found');
        }
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->away($frontendUrl . '/login?error=invalid_hash');
        }
        if (! $request->hasValidSignature()) {
            return redirect()->away($frontendUrl . '/login?error=invalid_signature');
        }
        if ($user->hasVerifiedEmail()) {
            return redirect()->away($frontendUrl . '/login?status=already_verified');
        }
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
        return redirect()->away($frontendUrl . '/login?status=verified');
    }
}
