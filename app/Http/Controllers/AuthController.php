<?php

namespace App\Http\Controllers;

use App\Factories\UserFactory;
use App\Services\InputValidationService;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

            // Authentication Service: Authenticate user
            if (AuthenticationService::authenticate($credentials)) {
                $request->session()->regenerate();
                
                return redirect()->intended('/dashboard')
                    ->with('success', 'Welcome back!');
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->withInput($request->only('email'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())
                ->withInput($request->only('email'));
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

            // Factory Pattern: Create Student user
            $user = UserFactory::createStudent($validatedData);

            // Authentication: Log in the newly registered user
            auth()->login($user);

            return redirect('/dashboard')
                ->with('success', 'Registration successful! Welcome to the library system.');
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

        return redirect('/login')
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
}
