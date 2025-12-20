<?php

namespace App\Http\Controllers;

use App\Factories\UserFactory;
use App\Services\InputValidationService;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

/**
 * Authentication Controller
 * Handles login, logout, registration, and password reset
 */
class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        try {
            // Basic validation
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Get remember me value (defaults to false if not checked)
            $remember = $request->boolean('remember');

            // Authentication Service: Authenticate user with remember option
            if (AuthenticationService::authenticate($credentials, $remember)) {
                $request->session()->regenerate();
                
                return redirect()->intended('/dashboard')
                    ->with('success', 'Welcome back!');
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('email', 'remember'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())
                ->withInput($request->only('email', 'remember'));
        }
    }

    /**
     * Show registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle registration request
     * Only Students can self-register
     */
    public function register(Request $request)
    {
        try {
            // Force role to Student for self-registration
            $requestData = $request->all();
            $requestData['role'] = 'Student';

            // Input Validation
            $validatedData = InputValidationService::validateRegistration($requestData);

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $request->validate([
                    'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
                ]);
                $validatedData['profile_image'] = $this->compressImage($image->getRealPath(), 64);
            }

            // Factory Pattern: Create Student user
            $user = UserFactory::createStudent($validatedData);

            // Send email verification notification
            $user->sendEmailVerificationNotification();

            // Authentication: Log in the newly registered user
            Auth::login($user);

            return redirect()->route('verification.notice')
                ->with('success', 'Registration successful! Please verify your email address.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Registration failed. Please try again.')
                ->withInput();
        }
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // Authentication Service: Logout user
        AuthenticationService::logout();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show password reset request form
     */
    public function showPasswordResetRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send password reset link
     */
    public function sendPasswordResetLink(Request $request)
    {
        try {
            // Input Validation
            $validatedData = InputValidationService::validatePasswordResetRequest($request->all());

            // Authentication Service: Send reset link
            $message = AuthenticationService::sendPasswordResetLink($validatedData['email']);

            return back()->with('success', $message);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Unable to send reset link. Please try again.');
        }
    }

    /**
     * Show password reset form
     */
    public function showPasswordResetForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        try {
            // Input Validation
            $validatedData = InputValidationService::validatePasswordReset($request->all());

            // Authentication Service: Reset password
            $message = AuthenticationService::resetPassword($validatedData);

            return redirect()->route('login')
                ->with('success', $message);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Unable to reset password. Please try again.');
        }
    }

    /**
     * Show dashboard
     */
    public function dashboard()
    {
        return view('dashboard');
    }

    /**
     * Compress image to fit within specified size limit (in KB)
     */
    private function compressImage($imagePath, $maxSizeKB = 64)
    {
        // Get image info
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
        // Create image resource based on type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                throw new \Exception('Unsupported image type');
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Start with reasonable dimensions (max 400x400)
        $maxDimension = 400;
        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = intval($height * ($maxDimension / $width));
            } else {
                $newHeight = $maxDimension;
                $newWidth = intval($width * ($maxDimension / $height));
            }
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }
        
        // Create resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Try different quality levels to fit within size limit
        $quality = 85;
        $compressed = null;
        
        while ($quality > 10) {
            ob_start();
            imagejpeg($resizedImage, null, $quality);
            $compressed = ob_get_clean();
            
            $sizeKB = strlen($compressed) / 1024;
            
            if ($sizeKB <= $maxSizeKB) {
                break;
            }
            
            $quality -= 10;
            
            // If still too large, reduce dimensions
            if ($quality <= 20 && $sizeKB > $maxSizeKB) {
                $newWidth = intval($newWidth * 0.8);
                $newHeight = intval($newHeight * 0.8);
                
                imagedestroy($resizedImage);
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                $quality = 85;
            }
        }
        
        // Clean up
        imagedestroy($image);
        imagedestroy($resizedImage);
        
        return $compressed;
    }

    /**
     * Show email verification notice
     */
    public function showVerificationNotice()
    {
        return Auth::user()->hasVerifiedEmail()
            ? redirect('/dashboard')
            : view('auth.verify-email');
    }

    /**
     * Verify email address
     */
    public function verifyEmail(Request $request)
    {
        $user = \App\Models\User::findOrFail($request->route('id'));

        // Check if the hash matches
        if (!hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return redirect()->route('verification.notice')
                ->with('error', 'Invalid verification link.');
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return redirect('/dashboard')
                ->with('info', 'Email already verified.');
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            return redirect('/dashboard')
                ->with('success', 'Email verified successfully! Welcome to the library system.');
        }

        return redirect()->route('verification.notice')
            ->with('error', 'Verification failed. Please try again.');
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect('/dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent! Please check your email.');
    }
}
