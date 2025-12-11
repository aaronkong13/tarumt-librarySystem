<?php

namespace App\Http\Controllers;

use App\Factories\UserFactory;
use App\Models\User;
use App\Services\InputValidationService;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * User Management Controller
 * Handles all user management operations including CRUD
 */
class UserController extends Controller
{
    /**
     * Display a listing of all users (Staff and Admin)
     * Admin is not displayed in the list (super root only manages others)
     */
    public function index()
    {
        try {
            // Access Control: Only Staff and Admin can view all users
            AccessControlService::authorize('view', 'all_users');

            // Exclude Admin from the user list
            $users = User::withTrashed()
                ->where('role', '!=', 'Admin')
                ->paginate(10);
            return view('users.index', compact('users'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new user (Staff only)
     */
    public function create()
    {
        try {
            // Access Control: Only Staff can create users
            AccessControlService::authorize('create', 'user');

            return view('users.create');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store a newly created user in database
     * Staff can create Students, Admin can create Students and Staff
     */
    public function store(Request $request)
    {
        try {
            // Access Control: Check if user can create users
            AccessControlService::authorize('create', 'user');

            // Check if user can create the requested role
            $requestedRole = $request->input('role');
            if (!AccessControlService::canCreateRole($requestedRole)) {
                throw new \Exception('You do not have permission to create users with role: ' . $requestedRole);
            }

            // Input Validation
            $validatedData = InputValidationService::validateRegistration($request->all());

            // Factory Pattern: Create user based on role
            if ($validatedData['role'] === 'Student') {
                $user = UserFactory::createStudent($validatedData);
            } elseif ($validatedData['role'] === 'Staff') {
                $user = UserFactory::createStaff($validatedData);
            } elseif ($validatedData['role'] === 'Admin') {
                $user = UserFactory::createAdmin($validatedData);
            } else {
                throw new \Exception('Invalid role specified.');
            }

            return redirect()->route('users.index')
                ->with('success', 'User created successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        try {
            // Access Control: Check if user can view this profile
            if (!AccessControlService::canViewUser($user)) {
                throw new \Exception('You do not have permission to view this profile.');
            }

            return view('users.show', compact('user'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the user
     */
    public function edit(User $user)
    {
        try {
            // Access Control: Check if user can edit this profile
            if (!AccessControlService::canEditUser($user)) {
                throw new \Exception('You do not have permission to edit this profile.');
            }

            return view('users.edit', compact('user'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified user in database
     */
    public function update(Request $request, User $user)
    {
        try {
            // Access Control: Check if user can edit this profile
            if (!AccessControlService::canEditUser($user)) {
                throw new \Exception('You do not have permission to edit this profile.');
            }

            // Input Validation
            $validatedData = InputValidationService::validateProfileUpdate(
                $request->all(),
                $user->id
            );

            // Factory Pattern: Update user
            UserFactory::update($user, $validatedData);

            return redirect()->route('users.show', $user)
                ->with('success', 'Profile updated successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Deactivate the specified user (soft delete)
     */
    public function destroy(User $user)
    {
        try {
            // Access Control: Check if user can deactivate
            if (!AccessControlService::canDeactivateUser($user)) {
                throw new \Exception('You do not have permission to deactivate this user.');
            }

            // Factory Pattern: Deactivate user
            UserFactory::deactivate($user);

            return redirect()->route('users.index')
                ->with('success', 'User deactivated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Restore a deactivated user
     */
    public function restore($id)
    {
        try {
            // Access Control: Only Staff can restore users
            AccessControlService::authorize('create', 'user');

            $user = User::withTrashed()->findOrFail($id);
            $user->restore();
            UserFactory::activate($user);

            return redirect()->route('users.index')
                ->with('success', 'User activated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
