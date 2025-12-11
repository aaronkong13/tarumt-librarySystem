<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * Authentication and Password Management Service
 * Handles user authentication, password verification, and password reset
 */
class AuthenticationService
{
    /**
     * Authenticate user with credentials
     * 
     * @param array $credentials
     * @return bool
     */
    public static function authenticate(array $credentials): bool
    {
        // Check if user exists and is active
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            return false;
        }

        // Check if user is active
        if ($user->status !== 'Active') {
            throw new \Exception('Your account has been deactivated. Please contact administrator.');
        }

        // Attempt authentication
        if (Auth::attempt($credentials)) {
            session()->regenerate();
            return true;
        }

        return false;
    }

    /**
     * Verify user password
     * 
     * @param User $user
     * @param string $password
     * @return bool
     */
    public static function verifyPassword(User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    /**
     * Change user password
     * 
     * @param User $user
     * @param string $newPassword
     * @return bool
     */
    public static function changePassword(User $user, string $newPassword): bool
    {
        $user->password = Hash::make($newPassword);
        return $user->save();
    }

    /**
     * Send password reset link to user
     * 
     * @param string $email
     * @return string Status message
     */
    public static function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);
        
        return $status === Password::RESET_LINK_SENT
            ? 'Password reset link sent to your email.'
            : 'Unable to send password reset link.';
    }

    /**
     * Reset user password with token
     * 
     * @param array $credentials
     * @return string Status message
     */
    public static function resetPassword(array $credentials): string
    {
        $status = Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? 'Password has been reset successfully.'
            : 'Unable to reset password.';
    }

    /**
     * Logout user
     * 
     * @return void
     */
    public static function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Check if current user has specific role
     * 
     * @param string $role
     * @return bool
     */
    public static function hasRole(string $role): bool
    {
        $user = Auth::user();
        return $user && $user->role === $role;
    }

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    public static function isAuthenticated(): bool
    {
        return Auth::check();
    }
}
