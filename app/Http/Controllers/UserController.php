<?php

namespace App\Http\Controllers;

use App\Factories\UserFactory;
use App\Services\InputValidationService;
use App\Services\AccessControlService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

/**
 * User Management Controller (INTERNAL MODULE ACCESS)
 * 
 * This controller handles web-based user management operations.
 * Uses UserService for DIRECT database access (internal module).
 * 
 * External modules should NOT call this controller directly.
 * They should use UserApiClient -> UserApiController -> UserService.
 * 
 * Architecture:
 * - Internal: UserController -> UserService (Direct DB) -> User Model -> Database
 * - External: Other Modules -> UserApiClient (HTTP) -> /api/users/* -> UserApiController -> UserService
 */
class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of all users (Staff and Admin)
     */
    public function index(Request $request)
    {
        try {
            // Access Control: Only Staff and Admin can view all users
            AccessControlService::authorize('view', 'all_users');

            // Get filtered users from service (5 users per page)
            $users = $this->userService->getFilteredUsers($request, 5);

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'users' => $users->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'role' => $user->role,
                            'status' => $user->deleted_at ? 'Inactive' : 'Active',
                            'created_at' => $user->created_at->toISOString(),
                            'profile_image' => $user->profile_image ? base64_encode($user->profile_image) : null,
                            'can_view' => app(AccessControlService::class)->canViewUser($user),
                            'can_edit' => app(AccessControlService::class)->canEditUser($user),
                            'can_deactivate' => app(AccessControlService::class)->canDeactivateUser($user),
                        ];
                    }),
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ]);
            }

            return view('users.index', compact('users'));
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new user (Staff only)
     */
    public function create()
    {
        try {
            AccessControlService::authorize('create', 'user');
            return view('users.create');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Store a newly created user in database
     */
    public function store(Request $request)
    {
        try {
            AccessControlService::authorize('create', 'user');

            $requestedRole = $request->input('role');
            if (!AccessControlService::canCreateRole($requestedRole)) {
                throw new \Exception('You do not have permission to create users with role: ' . $requestedRole);
            }

            $validatedData = InputValidationService::validateRegistration($request->all());

            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $request->validate(['profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048']);
                $validatedData['profile_image'] = $this->compressImage($image->getRealPath(), 64);
            }

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

            // Mark email as verified immediately (no verification needed for Staff/Admin created users)
            $user->markEmailAsVerified();

            return redirect()->route('users.index')->with('success', 'User created successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified user
     */
    public function show($id)
    {
        try {
            // Use service to get user
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                throw new \Exception('User not found.');
            }

            if (!AccessControlService::canViewUser($user)) {
                throw new \Exception('You do not have permission to view this profile.');
            }

            // Get borrowing history if viewing a student (for Staff/Admin)
            $borrowingHistory = null;
            if ($user->isStudent() && (Auth::user()->isStaff() || Auth::user()->isAdmin())) {
                $borrowingService = app(\App\Services\BorrowingService::class);
                $borrowingHistory = $borrowingService->getUserBorrowingHistory($user->id);
            }

            return view('users.show', compact('user', 'borrowingHistory'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the user
     */
    public function edit($id)
    {
        try {
            // Use service to get user
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                throw new \Exception('User not found.');
            }

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
    public function update(Request $request, $id)
    {
        try {
            // Use service to get user
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                throw new \Exception('User not found.');
            }

            if (!AccessControlService::canEditUser($user)) {
                throw new \Exception('You do not have permission to edit this profile.');
            }

            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $request->validate(['profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048']);
                $imageData = $this->compressImage($image->getRealPath(), 64);
                $user->profile_image = $imageData;
            }

            $validatedData = InputValidationService::validateProfileUpdate($request->all(), $user->id);

            // Only admin can change role
            if (isset($validatedData['role']) && $validatedData['role'] !== $user->role) {
                if (!Auth::user() || Auth::user()->role !== 'Admin') {
                    throw new \Exception('Only administrators can change user roles.');
                }
            }

            // Factory Pattern: Update user
            UserFactory::update($user, $validatedData);
            
            if ($request->hasFile('profile_image')) {
                $user->save();
            }

            return redirect()->route('users.index')->with('success', 'User updated successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Deactivate the specified user (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Use service to get user
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                throw new \Exception('User not found.');
            }

            if (!AccessControlService::canDeactivateUser($user)) {
                throw new \Exception('You do not have permission to deactivate this user.');
            }

            UserFactory::deactivate($user);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User deactivated successfully.',
                    'user' => ['id' => $user->id, 'status' => 'Inactive']
                ]);
            }

            return redirect()->route('users.index')->with('success', 'User deactivated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Restore a deactivated user
     */
    public function restore(Request $request, $id)
    {
        try {
            AccessControlService::authorize('create', 'user');

            // Use service to restore user
            $user = $this->userService->getUserById($id);
            
            if (!$user) {
                throw new \Exception('User not found.');
            }

            $this->userService->restoreUser($id);
            UserFactory::activate($user);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User activated successfully.',
                    'user' => ['id' => $user->id, 'status' => 'Active']
                ]);
            }

            return redirect()->route('users.index')->with('success', 'User activated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show current user's profile (personal profile view)
     */
    public function showProfile()
    {
        $user = Auth::user();
        
        // Get borrowing history for students only
        $borrowingHistory = null;
        if ($user->isStudent()) {
            $borrowingService = app(\App\Services\BorrowingService::class);
            $borrowingHistory = $borrowingService->getUserBorrowingHistory($user->id);
        }
        
        return view('users.profile', compact('user', 'borrowingHistory'));
    }

    /**
     * Show form for editing current user's own profile
     */
    public function editProfile()
    {
        $user = Auth::user();
        return view('users.profile-edit', compact('user'));
    }

    /**
     * Update current user's own profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $request->validate(['profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048']);
                $imageData = $this->compressImage($image->getRealPath(), 64);
                $user->profile_image = $imageData;
            }

            // Validate other profile fields
            $validatedData = InputValidationService::validateProfileUpdate($request->all(), $user->id);

            // Factory Pattern: Update user
            UserFactory::update($user, $validatedData);
            
            if ($request->hasFile('profile_image')) {
                $user->save();
            }

            return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Compress image to fit within specified size limit (in KB)
     */
    private function compressImage($imagePath, $maxSizeKB = 64)
    {
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
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
        
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
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
            
            if ($quality <= 20 && $sizeKB > $maxSizeKB) {
                $newWidth = intval($newWidth * 0.8);
                $newHeight = intval($newHeight * 0.8);
                
                imagedestroy($resizedImage);
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                $quality = 85;
            }
        }
        
        imagedestroy($image);
        imagedestroy($resizedImage);
        
        return $compressed;
    }
}
