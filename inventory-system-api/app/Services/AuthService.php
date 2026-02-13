<?php

namespace App\Services;

use App\Events\DataChanged;
use App\Helpers\IdGenerator;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $userId = IdGenerator::generateUserId($data['id_department'] ?? null);
            if (User::where('id_user', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'id_user' => ['Sistem sedang sibuk (ID Collision). Silakan coba lagi.']
                ]);
            }
            $data['email'] = strtolower($data['email']);
            $userData = array_merge($data, [
                'id_user'  => $userId,
                'password' => Hash::make($data['password']),
                'role'     => $data['role'] ?? 'employee',
            ]);
            $user = User::create($userData);
            event(new Registered($user));
            $token = $user->createToken('auth-token', ['*'], now()->addDay())->plainTextToken;
            DataChanged::dispatch('users', 'created');
            $user->load(['department', 'jobProfile']);
            return [
                'user'  => $user,
                'token' => $token
            ];
        });
    }

    public function suggestId(string $departmentId): array
    {
        return IdGenerator::suggestUserId($departmentId);
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }
        $user->tokens()->delete();
        $token = $user->createToken('auth-token', ['*'], now()->addDay())->plainTextToken;
        return compact('user', 'token');
    }

    public function logout(User $user)
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        } else {
            throw ValidationException::withMessages([
                'token' => ['Token tidak ditemukan.'],
            ]);
        }
    }

    public function me(User $user)
    {
        return $user->load(['department', 'jobProfile']);
    }

    public function updateMyProfile(User $user, array $data): User
    {
        if (!Hash::check($data['confirm_password'], $user->password)) {
            throw new \Exception('Password pengguna tidak valid/salah.');
        }
        return DB::transaction(function () use ($user, $data) {
            $allowed = [
                'name',
                'email',
                'position',
                'id_job_profile',
            ];
            $filteredData = array_intersect_key(
                $data,
                array_flip($allowed)
            );
            $emailChanged = false;
            if (
                isset($filteredData['email']) &&
                strtolower($filteredData['email']) !== strtolower($user->email)
            ) {
                $filteredData['email'] = strtolower($filteredData['email']);
                $filteredData['email_verified_at'] = null;
                $emailChanged = true;
            }
            $user->update($filteredData);
            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }
            DataChanged::dispatch('users', 'updated');
            return $user->fresh();
        });
    }

    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (isset($data['email'])) {
                $data['email'] = strtolower($data['email']);
            }
            $user->update($data);
            DataChanged::dispatch('users', 'updated');
            return $user->fresh();
        });
    }

    public function changeMyPassword(User $user, array $data)
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini salah.'],
            ]);
        }
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'password' => Hash::make($data['new_password']),
            ]);
            $user->tokens()->delete();
            return true;
        });
    }

    public function changePassword(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'password' => Hash::make($data['new_password']),
            ]);
            $user->tokens()->delete();
            return true;
        });
    }
}
